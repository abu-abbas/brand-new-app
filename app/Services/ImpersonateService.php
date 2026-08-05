<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\ImpersonateError;
use App\Jobs\SendImpersonationEndedNotification;
use App\Jobs\SendImpersonationStartedNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImpersonateService
{
    public function __construct(
        private readonly ErrorDefinitionReader $reader,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Memulai sesi impersonate Admin ke Target User.
     */
    public function start(User $admin, User $target, ?string $targetGroupId): User
    {
        // 1. Belum sedang impersonate (no nested)
        if (session()->has('impersonator_id')) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::NESTED_IMPERSONATION_PROHIBITED),
                context: ['admin_userid' => $admin->v_userid]
            );
        }

        // 2. Admin memiliki active group
        $adminActiveGroup = $admin->getActiveGroupId();
        if ($adminActiveGroup === null) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::IMPERSONATE_PERMISSION_DENIED),
                context: ['admin_userid' => $admin->v_userid, 'reason' => 'no_active_group']
            );
        }

        // 3. Active group Admin memiliki permission 'impersonate-pengguna'
        $adminPermissions = $admin->getPermissionsList();
        if (! in_array('impersonate-pengguna', $adminPermissions, true)) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::IMPERSONATE_PERMISSION_DENIED),
                context: ['admin_userid' => $admin->v_userid, 'active_group' => $adminActiveGroup]
            );
        }

        // 4. Target bukan Admin sendiri
        if ($admin->v_userid === $target->v_userid) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::SELF_IMPERSONATION_PROHIBITED),
                context: ['admin_userid' => $admin->v_userid]
            );
        }

        // 5. Scope check organisasi
        $isTargetInScope = User::query()
            ->where('v_userid', $target->v_userid)
            ->forUserScope($admin) // @phpstan-ignore method.notFound (scope via HasOrganizationScope trait)
            ->exists();

        if (! $isTargetInScope) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::TARGET_OUT_OF_SCOPE),
                context: ['admin_userid' => $admin->v_userid, 'target_userid' => $target->v_userid]
            );
        }

        // 6. Target aktif dan memiliki assignment group yang berlaku
        $targetRoles = $target->getRolesList();
        if (! $target->b_is_active || empty($targetRoles)) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::TARGET_INACTIVE_OR_NO_ASSIGNMENT),
                context: ['target_userid' => $target->v_userid]
            );
        }

        // 7. Penentuan group Target yang dipilih
        $chosenGroupId = null;
        if (count($targetRoles) > 1) {
            if ($targetGroupId === null || $targetGroupId === '') {
                throw new ApplicationException(
                    definition: $this->reader->read(ImpersonateError::TARGET_GROUP_REQUIRED),
                    context: ['target_userid' => $target->v_userid]
                );
            }
            $chosenGroupId = $targetGroupId;
        } else {
            if ($targetGroupId !== null && $targetGroupId !== '') {
                $chosenGroupId = $targetGroupId;
            } else {
                $chosenGroupId = $targetRoles[0];
            }
        }

        // 8. Group yang dipilih benar-benar milik Target dan masih berlaku
        if (! in_array($chosenGroupId, $targetRoles, true)) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::INVALID_TARGET_GROUP),
                context: ['target_userid' => $target->v_userid, 'chosen_group' => $chosenGroupId]
            );
        }

        // 9. Level active group Admin harus STRICTLY LEBIH BESAR daripada level group Target
        $adminUserRole = $admin->getActiveUserRole();
        $adminLevel = 0;
        if ($adminUserRole !== null && $adminUserRole->roleModel !== null) {
            $adminLevel = (int) $adminUserRole->roleModel->i_level;
        } else {
            $adminLevel = (int) $admin->role_level;
        }

        $targetUserRole = $target->getCurrentUserRoles()->firstWhere('v_role_code', $chosenGroupId);
        $targetLevel = 0;
        if ($targetUserRole !== null && $targetUserRole->roleModel !== null) {
            $targetLevel = (int) $targetUserRole->roleModel->i_level;
        }

        if ($adminLevel <= $targetLevel) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::INSUFFICIENT_ADMIN_LEVEL),
                context: [
                    'admin_userid' => $admin->v_userid,
                    'admin_level' => $adminLevel,
                    'target_userid' => $target->v_userid,
                    'target_level' => $targetLevel,
                ]
            );
        }

        // Jalankan Session Swap
        $startedAt = Carbon::now();

        // 1. Pre-login session state
        session([
            'impersonator_id' => $admin->v_userid,
            'impersonator_active_group' => $adminActiveGroup,
            'impersonated_active_group' => $chosenGroupId,
            'impersonate_started_at' => $startedAt->toIso8601String(),
            'active_group_id' => $chosenGroupId,
        ]);

        // 2. Auth swap
        /** @var SessionGuard $webGuard */
        $webGuard = Auth::guard('web');
        $webGuard->login($target);
        $target->forgetAuthorizationCache();
        session()->regenerate();

        // 3. Re-set session state setelah regenerate
        session([
            'impersonator_id' => $admin->v_userid,
            'impersonator_active_group' => $adminActiveGroup,
            'impersonated_active_group' => $chosenGroupId,
            'impersonate_started_at' => $startedAt->toIso8601String(),
            'active_group_id' => $chosenGroupId,
        ]);

        $requestId = null;
        $reqAttrId = request()->attributes->get('request_id');
        if (is_string($reqAttrId) && $reqAttrId !== '') {
            $requestId = $reqAttrId;
        }

        $ipAddress = request()->ip();
        $userAgent = request()->userAgent();

        // 4. Record Synchronous Audit Log dengan Kompensasi Rollback
        try {
            $this->activityLogger->record(
                subjectType: 'User',
                subjectId: $target->v_userid,
                event: 'impersonation_started',
                properties: [
                    'admin_active_group' => $adminActiveGroup,
                    'target_active_group' => $chosenGroupId,
                    'target_had_expired_password' => $target->mustChangePassword(),
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ],
                causerId: $target->v_userid,
                impersonatorId: $admin->v_userid,
            );
        } catch (Throwable $e) {
            // Rollback Kompensasi: restore login Admin
            /** @var SessionGuard $webGuard */
            $webGuard = Auth::guard('web');
            $webGuard->login($admin);
            session()->forget([
                'impersonator_id',
                'impersonator_active_group',
                'impersonated_active_group',
                'impersonate_started_at',
            ]);
            session(['active_group_id' => $adminActiveGroup]);
            $admin->forgetAuthorizationCache();
            session()->regenerate();

            throw $e;
        }

        // 5. Dispatch async email notification
        $expiresAtIso = $startedAt->copy()->addMinutes(60)->toIso8601String();
        $adminName = $admin->v_username;
        if (empty($adminName)) {
            $adminName = $admin->v_userid;
        }

        /** @var Role|null $roleModel */
        $roleModel = Role::query()->where('v_code', $chosenGroupId)->first();
        $targetGroupName = $roleModel !== null && ! empty($roleModel->v_name)
            ? toTitleCase($roleModel->v_name)
            : $chosenGroupId;

        try {
            SendImpersonationStartedNotification::dispatch(
                adminUserId: $admin->v_userid,
                adminName: $adminName,
                targetUserId: $target->v_userid,
                targetGroupId: $targetGroupName,
                startedAt: $startedAt->toIso8601String(),
                expiresAt: $expiresAtIso,
                requestId: $requestId,
            );
        } catch (Throwable $e) {
            Log::warning('Dispatch SendImpersonationStartedNotification gagal', [
                'error' => $e->getMessage(),
            ]);
        }

        return $target;
    }

    /**
     * Menghentikan sesi impersonate dan memulihkan identitas Admin.
     */
    public function stop(string $reason = 'manual_leave'): User
    {
        if (! session()->has('impersonator_id')) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::NOT_IMPERSONATING)
            );
        }

        $targetUser = Auth::user();
        $targetUserId = '';
        if ($targetUser instanceof User) {
            $targetUserId = $targetUser->v_userid;
        }

        $adminUserId = (string) session()->get('impersonator_id');
        $adminActiveGroup = (string) session()->get('impersonator_active_group');
        $startedAtIso = (string) session()->get('impersonate_started_at', '');

        /** @var User|null $admin */
        $admin = User::query()->where('v_userid', $adminUserId)->first();
        if ($admin === null) {
            throw new ApplicationException(
                definition: $this->reader->read(ImpersonateError::NOT_IMPERSONATING),
                context: ['reason' => 'admin_account_not_found', 'admin_userid' => $adminUserId]
            );
        }

        // Restore login Admin
        /** @var SessionGuard $webGuard */
        $webGuard = Auth::guard('web');
        $webGuard->login($admin);
        session()->forget([
            'impersonator_id',
            'impersonator_active_group',
            'impersonated_active_group',
            'impersonate_started_at',
        ]);
        session(['active_group_id' => $adminActiveGroup]);
        $admin->forgetAuthorizationCache();
        session()->regenerate();
        session(['active_group_id' => $adminActiveGroup]);

        $endedAtIso = Carbon::now()->toIso8601String();

        $event = 'impersonation_stopped';
        if ($reason === 'ttl_expired') {
            $event = 'impersonation_expired';
        } elseif ($reason === 'session_invalidated') {
            $event = 'impersonation_invalidated';
        }

        $requestId = null;
        $reqAttrId = request()->attributes->get('request_id');
        if (is_string($reqAttrId) && $reqAttrId !== '') {
            $requestId = $reqAttrId;
        }

        // Synchronous Audit Log
        try {
            $this->activityLogger->record(
                subjectType: 'User',
                subjectId: $targetUserId,
                event: $event,
                properties: [
                    'reason' => $reason,
                    'started_at' => $startedAtIso,
                    'ended_at' => $endedAtIso,
                ],
                causerId: $targetUserId,
                impersonatorId: $adminUserId,
            );
        } catch (Throwable $e) {
            Log::critical('Audit stop impersonate gagal dicatat, namun restore Admin tetap berjalan.', [
                'admin_userid' => $adminUserId,
                'target_userid' => $targetUserId,
                'error' => $e->getMessage(),
            ]);
        }

        // Dispatch async email notification
        $adminName = $admin->v_username;
        if (empty($adminName)) {
            $adminName = $admin->v_userid;
        }

        try {
            SendImpersonationEndedNotification::dispatch(
                adminUserId: $adminUserId,
                adminName: $adminName,
                targetUserId: $targetUserId,
                reason: $reason,
                startedAt: $startedAtIso,
                endedAt: $endedAtIso,
                requestId: $requestId,
            );
        } catch (Throwable $e) {
            Log::warning('Dispatch SendImpersonationEndedNotification gagal', [
                'error' => $e->getMessage(),
            ]);
        }

        return $admin;
    }
}
