<?php

namespace App\Services;

use App\Constants\RoleConstant;
use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\UserManagementError;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\InvitationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(
        private readonly ErrorDefinitionReader $errorDefinitionReader = new ErrorDefinitionReader,
    ) {}

    /**
     * @param array{
     *     page?: int|string,
     *     per_page?: int|string,
     *     search?: string|null,
     *     search_fields?: array<string>|null,
     *     sort_by?: string|null,
     *     sort_direction?: string|null,
     *     active?: string|null
     * } $params
     */
    public function getPaginatedUsers(array $params): LengthAwarePaginator
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        $query = User::query()
            ->with(['userRoles.roleModel.features'])
            ->whereNull('dt_deleted_at')
            ->forUserScope($currentUser);

        // Sembunyikan user tingkat Root (i_level >= ROOT_LEVEL) dari daftar pengguna
        $query->whereDoesntHave('currentUserRoles.roleModel', function ($q) {
            $q->where('i_level', '>=', RoleConstant::ROOT_LEVEL);
        });

        // Search multi-kolom
        if (! empty($params['search'])) {
            $search = trim($params['search']);
            $query->where(function ($q) use ($search) {
                $q->where('v_userid', 'like', "%{$search}%")
                    ->orWhere('v_username', 'like', "%{$search}%")
                    ->orWhere('v_email', 'like', "%{$search}%");
            });
        }

        // Filter status active
        if (isset($params['active']) && $params['active'] !== '') {
            $activeBool = in_array(strtolower((string) $params['active']), ['true', '1'], true);
            $query->where('b_is_active', $activeBool);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'dt_created_at';
        $sortDir = strtolower($params['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $columnMap = [
            'id' => 'i_id',
            'userid' => 'v_userid',
            'username' => 'v_username',
            'name' => 'v_username',
            'email' => 'v_email',
            'is_active' => 'b_is_active',
            'b_is_active' => 'b_is_active',
            'created_at' => 'dt_created_at',
        ];

        $orderColumn = $columnMap[$sortBy] ?? 'dt_created_at';

        return $query->orderBy($orderColumn, $sortDir)
            ->paginate((int) ($params['per_page'] ?? 15), ['*'], 'page', (int) ($params['page'] ?? 1));
    }

    public function getUserDetail(User $user): User
    {
        return $user->load(['userRoles.roleModel.features']);
    }

    private function validateRoleAssignments(array $rolesData, ?User $actor = null): void
    {
        if (! $actor) {
            /** @var User|null $actor */
            $actor = Auth::user();
        }

        if (! $actor || $actor->isRoot()) {
            return;
        }

        $actorLevel = $actor->role_level;
        $roleCodes = [];

        foreach ($rolesData as $roleItem) {
            if (array_key_exists('role_code', $roleItem)) {
                $roleCodes[] = $roleItem['role_code'];
            } elseif (array_key_exists('v_role_code', $roleItem)) {
                $roleCodes[] = $roleItem['v_role_code'];
            }
        }

        if (empty($roleCodes)) {
            return;
        }

        $assignedRoles = Role::whereIn('v_code', $roleCodes)->get();
        foreach ($assignedRoles as $assignedRole) {
            if ((int) $assignedRole->i_level >= $actorLevel) {
                throw new ApplicationException(
                    definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_ASSIGN_HIGHER_ROLE),
                    context: [
                        'actor_userid' => $actor->v_userid,
                        'actor_level' => $actorLevel,
                        'target_role_code' => $assignedRole->v_code,
                        'target_role_level' => $assignedRole->i_level,
                    ]
                );
            }
        }
    }

    public function createUser(array $data, ?string $authUserId = null): User
    {
        if (! empty($data['roles']) && is_array($data['roles'])) {
            $this->validateRoleAssignments($data['roles']);
        }

        $user = DB::transaction(function () use ($data, $authUserId) {
            $now = Carbon::now();
            $password = null;
            if (! $data['is_external']) {
                $password = Hash::make(Str::random(32));
            }

            $attributes = [
                'v_userid' => $data['userid'],
                'v_username' => $data['username'],
                'v_password' => $password,
                'b_is_active' => (bool) $data['is_active'],
                'b_use_other' => (bool) $data['is_external'],
                'v_created_by' => $authUserId,
                'dt_created_at' => $now,
            ];

            if (array_key_exists('email', $data)) {
                $attributes['v_email'] = $data['email'];
            }
            if (array_key_exists('unit_code', $data)) {
                $attributes['v_kolok'] = $data['unit_code'];
            }

            $user = User::create($attributes);

            if (! empty($data['roles']) && is_array($data['roles'])) {
                foreach ($data['roles'] as $roleItem) {
                    $roleAttributes = [
                        'v_userid' => $user->v_userid,
                        'v_role_code' => $roleItem['role_code'],
                        'v_created_by' => $authUserId,
                        'dt_created_at' => $now,
                    ];
                    $this->copyRoleMutationFields($roleAttributes, $roleItem);
                    UserRole::create($roleAttributes);
                }
            }

            return $user->load(['userRoles.roleModel']);
        });

        $this->sendInvitationIfEligible($user);

        return $user;
    }

    public function updateUser(User $user, array $data, ?string $authUserId = null): User
    {
        $currentUserId = $this->resolveActorId($authUserId);

        if ($currentUserId === $user->v_userid && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DEACTIVATE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        if (array_key_exists('roles', $data) && is_array($data['roles'])) {
            $this->validateRoleAssignments($data['roles']);
        }

        $oldEmail = $user->v_email;
        $wasExternal = $user->b_use_other;

        $updatedUser = DB::transaction(function () use ($user, $data, $authUserId) {
            $now = Carbon::now();

            $updateData = [];

            if (array_key_exists('username', $data) && $data['username'] !== null) {
                $updateData['v_username'] = $data['username'];
            }

            if (array_key_exists('email', $data)) {
                $updateData['v_email'] = $data['email'];
                if ($data['email'] !== $user->v_email) {
                    $updateData['dt_email_verified_at'] = null;
                }
            }

            if (array_key_exists('unit_code', $data)) {
                $updateData['v_kolok'] = $data['unit_code'];
            }

            if (array_key_exists('is_active', $data) && $data['is_active'] !== null) {
                $updateData['b_is_active'] = (bool) $data['is_active'];
            }

            if (array_key_exists('is_external', $data) && $data['is_external'] !== null) {
                $updateData['b_use_other'] = (bool) $data['is_external'];
                if ((bool) $data['is_external'] !== $user->b_use_other) {
                    if ($data['is_external']) {
                        $updateData['v_password'] = null;
                    } else {
                        $updateData['v_password'] = Hash::make(Str::random(32));
                    }
                    $updateData['dt_last_updated_password'] = null;
                    $updateData['dt_email_verified_at'] = null;
                }
            }

            if (! empty($updateData)) {
                $updateData['v_updated_by'] = $authUserId;
                $updateData['dt_updated_at'] = $now;
                $user->update($updateData);
            }

            if (array_key_exists('roles', $data) && is_array($data['roles'])) {
                // Replace roles
                $assignedRoles = UserRole::where('v_userid', $user->v_userid);
                $assignedRoles->update(['v_deleted_by' => $authUserId]);
                $assignedRoles->delete();

                foreach ($data['roles'] as $roleItem) {
                    $roleAttributes = [
                        'v_userid' => $user->v_userid,
                        'v_role_code' => $roleItem['role_code'],
                        'v_created_by' => $authUserId,
                        'dt_created_at' => $now,
                    ];
                    $this->copyRoleMutationFields($roleAttributes, $roleItem);
                    UserRole::create($roleAttributes);
                }
            }

            $user->forgetAuthorizationCache();

            return $user->load(['userRoles.roleModel']);
        });

        $emailChanged = array_key_exists('email', $data) && $oldEmail !== $updatedUser->v_email;
        $becameInternal = $wasExternal && ! $updatedUser->b_use_other;
        if (($emailChanged || $becameInternal) && ! empty($oldEmail)) {
            DB::table('password_reset_tokens')->where('email', $oldEmail)->delete();
        }
        if ($emailChanged || $becameInternal) {
            $this->sendInvitationIfEligible($updatedUser);
        }

        if ((array_key_exists('is_active', $data) && ! $updatedUser->b_is_active)
            || (array_key_exists('is_external', $data) && $updatedUser->b_use_other)) {
            DB::table('sessions')->where('user_id', $updatedUser->v_userid)->delete();
        }

        return $updatedUser;
    }

    public function toggleUserStatus(User $user, ?string $authUserId = null): User
    {
        $currentUserId = $this->resolveActorId($authUserId);

        if ($currentUserId === $user->v_userid && $user->b_is_active) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DEACTIVATE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        $user->update([
            'b_is_active' => ! $user->b_is_active,
            'v_updated_by' => $authUserId,
            'dt_updated_at' => Carbon::now(),
        ]);

        if (! $user->b_is_active) {
            DB::table('sessions')->where('user_id', $user->v_userid)->delete();
        }

        return $user->load(['userRoles.roleModel']);
    }

    public function deleteUser(User $user, ?string $authUserId = null): void
    {
        $currentUserId = $this->resolveActorId($authUserId);

        if ($currentUserId === $user->v_userid) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DELETE_SELF),
                context: ['userid' => $user->v_userid]
            );
        }

        if ($user->b_use_other || empty($user->v_password)) {
            throw new ApplicationException(
                definition: $this->errorDefinitionReader->read(UserManagementError::CANNOT_DELETE_EXTERNAL_USER),
                context: ['userid' => $user->v_userid]
            );
        }

        $user->update([
            'v_deleted_by' => $authUserId,
            'dt_deleted_at' => Carbon::now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $role
     */
    private function copyRoleMutationFields(array &$attributes, array $role): void
    {
        $fields = [
            'wilayah' => 'v_wilayah',
            'unit' => 'v_unit',
            'pelaksana' => 'v_pelaksana',
            'valid_from' => 'dt_valid_from',
            'valid_until' => 'dt_valid_until',
        ];

        foreach ($fields as $payloadKey => $column) {
            if (array_key_exists($payloadKey, $role)) {
                $attributes[$column] = $role[$payloadKey];
            }
        }
    }

    private function sendInvitationIfEligible(User $user): void
    {
        if ($user->b_use_other || ! $user->b_is_active || empty($user->v_email)) {
            return;
        }

        DB::table('password_reset_tokens')->where('email', $user->v_email)->delete();
        $token = Password::broker()->createToken($user);
        $user->notify(new InvitationNotification($token));
    }

    private function resolveActorId(?string $authUserId): ?string
    {
        if ($authUserId !== null) {
            return $authUserId;
        }

        $actor = Auth::user();
        if ($actor instanceof User) {
            return $actor->v_userid;
        }

        return null;
    }
}
