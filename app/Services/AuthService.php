<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Models\User;
use App\Models\UserPasswordHistory;
use App\Notifications\InvitationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly ErrorDefinitionReader $reader,
        private readonly ActivityLogger $activityLogger = new ActivityLogger,
    ) {}

    /**
     * @param  array{username: string, password: string}  $credentials
     */
    public function authenticate(array $credentials, string $ip): User
    {
        $inputUsername = trim($credentials['username']);
        $password = $credentials['password'];
        $throttleKey = 'login:'.Str::lower($inputUsername).'|'.$ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::TOO_MANY_ATTEMPTS),
                context: ['seconds' => $seconds],
            );
        }

        // 1. Cek tm_users terlebih dahulu
        $user = User::where('v_username', $inputUsername)
            ->orWhere('v_userid', $inputUsername)
            ->first();

        if ($user) {
            $isPasswordValid = $user->b_use_other
                ? $this->verifyMd5FromView($user->v_userid, $password)
                : is_string($user->v_password) && Hash::check($password, $user->v_password);

            if (! $isPasswordValid || ! $user->b_is_active) {
                RateLimiter::hit($throttleKey, 300);
                throw new ApplicationException(
                    definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                    context: ['username' => $inputUsername],
                );
            }

            $roles = $user->getRolesList();
            if (empty($roles)) {
                throw new ApplicationException(
                    definition: $this->reader->read(AuthError::NO_ROLE_ASSIGNED),
                    context: ['username' => $inputUsername],
                );
            }

            RateLimiter::clear($throttleKey);
            Auth::guard('web')->login($user);

            if (count($roles) === 1) {
                session(['active_group_id' => $roles[0]]);
            } elseif ($user->v_default_group_id && in_array($user->v_default_group_id, $roles, true)) {
                session(['active_group_id' => $user->v_default_group_id]);
            }

            $this->activityLogger->record(
                subjectType: 'User',
                subjectId: $user->v_userid,
                event: 'login',
                properties: [
                    'username' => $inputUsername,
                    'ip' => $ip,
                ],
                causerId: $user->v_userid
            );

            return $user;
        }

        // 2. Jika tidak ada di tm_users, cek ke vw_users (view kepegawaian)
        if (! Schema::hasTable('vw_users')) {
            RateLimiter::hit($throttleKey, 300);
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                context: ['username' => $inputUsername],
            );
        }

        $vwUser = DB::table('vw_users')
            ->where(function ($query) use ($inputUsername) {
                $query->where('v_userid', $inputUsername)
                    ->orWhere('v_username', $inputUsername);
            })
            ->first();

        if (! $vwUser || empty($vwUser->v_password) || md5($password) !== $vwUser->v_password) {
            RateLimiter::hit($throttleKey, 300);
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                context: ['username' => $inputUsername],
            );
        }

        // Auto-provisioning ke tm_users
        $attributes = [
            'v_userid' => (string) $vwUser->v_userid,
            'v_username' => (string) $vwUser->v_username,
            'v_password' => null,
            'b_is_active' => true,
            'b_use_other' => true,
            'dt_created_at' => now(),
        ];

        foreach (['v_klogad', 'v_kolok', 'v_kojab', 'v_koper', 'v_kopang', 'v_eselon', 'v_spmu', 'v_kd'] as $field) {
            if (property_exists($vwUser, $field)) {
                $attributes[$field] = $vwUser->{$field};
            }
        }

        $user = User::create($attributes);

        // 3. Hanya assignment dalam rentang berlaku yang dapat dipakai.
        $roles = $user->getRolesList();
        if (empty($roles)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::NO_ROLE_ASSIGNED),
                context: ['username' => $inputUsername],
            );
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        Auth::guard('web')->login($user);

        // Auto-set active group jika hanya punya 1 group atau punya default_group_id
        if (count($roles) === 1) {
            session(['active_group_id' => $roles[0]]);
        } elseif ($user->v_default_group_id && in_array($user->v_default_group_id, $roles, true)) {
            session(['active_group_id' => $user->v_default_group_id]);
        }

        $this->activityLogger->record(
            subjectType: 'User',
            subjectId: $user->v_userid,
            event: 'login',
            properties: [
                'username' => $inputUsername,
                'ip' => $ip,
                'auto_provisioned' => true,
            ],
            causerId: $user->v_userid
        );

        return $user;
    }

    /**
     * Memilih / mengubah group aktif dalam session dan opsional menyimpan preferensi default.
     */
    public function setActiveGroup(User $user, string $groupId, bool $remember = false): User
    {
        $userRoles = $user->getRolesList();

        if (! in_array($groupId, $userRoles, true)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_GROUP),
                context: ['userid' => $user->v_userid, 'group_id' => $groupId],
            );
        }

        session(['active_group_id' => $groupId]);
        $user->forgetAuthorizationCache();

        if ($remember) {
            $user->v_default_group_id = $groupId;
            User::where('v_userid', $user->v_userid)->update([
                'v_default_group_id' => $groupId,
            ]);
        } else {
            $user->v_default_group_id = null;
            User::where('v_userid', $user->v_userid)->update([
                'v_default_group_id' => null,
            ]);
        }

        return $user;
    }

    /**
     * Mengapus / me-reset preferensi default group milik pengguna.
     */
    public function resetDefaultGroup(User $user): User
    {
        $user->v_default_group_id = null;
        session()->forget('active_group_id');
        $user->forgetAuthorizationCache();
        User::where('v_userid', $user->v_userid)->update([
            'v_default_group_id' => null,
        ]);

        return $user;
    }

    /**
     * Helper memverifikasi password MD5 terhadap vw_users.
     */
    private function verifyMd5FromView(string $userId, string $password): bool
    {
        if (! Schema::hasTable('vw_users')) {
            return false;
        }

        $vwUser = DB::table('vw_users')
            ->where('v_userid', $userId)
            ->first();

        return $vwUser && ! empty($vwUser->v_password) && (md5($password) === $vwUser->v_password);
    }

    /**
     * Memproses permintaan lupa password mandiri.
     */
    public function forgotPassword(string $email, string $ip): void
    {
        $emailKey = 'forgot-password:email:'.hash('sha256', Str::lower($email));
        $ipKey = 'forgot-password:ip:'.$ip;

        if (RateLimiter::tooManyAttempts($emailKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)) {
            return;
        }
        RateLimiter::hit($emailKey, 60);
        RateLimiter::hit($ipKey, 60);

        $user = User::where('v_email', $email)
            ->whereNull('dt_deleted_at')
            ->first();

        if ($user && ! $user->b_use_other && $user->b_is_active && ! empty($user->v_email)) {
            DB::table('password_reset_tokens')->where('email', $user->v_email)->delete();
            $token = Password::broker()->createToken($user);
            $user->notify(new ResetPasswordNotification($token, false));
        }
    }

    /**
     * Memproses reset password menggunakan token.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        DB::transaction(function () use ($email, $token, $password) {
            $user = User::query()
                ->where('v_email', $email)
                ->where('b_use_other', false)
                ->where('b_is_active', true)
                ->whereNull('dt_deleted_at')
                ->lockForUpdate()
                ->first();

            if (! $user) {
                $this->throwInvalidToken($email);
            }

            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! Password::broker()->tokenExists($user, $token)) {
                $this->throwInvalidToken($email);
            }

            $this->assertPasswordNotReused($user, $password);

            if ($user->dt_last_updated_password !== null && is_string($user->v_password)) {
                UserPasswordHistory::create([
                    'v_userid' => $user->v_userid,
                    'v_password_hash' => $user->v_password,
                    'dt_created_at' => now(),
                ]);
            }

            $this->prunePasswordHistories($user->v_userid);

            $user->v_password = Hash::make($password);
            if ($user->dt_email_verified_at === null) {
                $user->dt_email_verified_at = now();
            }
            $user->dt_last_updated_password = now();
            $user->v_remember_token = Str::random(60);
            $user->save();

            Password::broker()->deleteToken($user);
            DB::table('sessions')->where('user_id', $user->v_userid)->delete();
        });
    }

    /**
     * Memproses pengubahan password oleh pengguna terautentikasi.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        DB::transaction(function () use ($user, $currentPassword, $newPassword) {
            $lockedUser = User::query()
                ->where('v_userid', $user->v_userid)
                ->lockForUpdate()
                ->firstOrFail();

            if (empty($lockedUser->v_password) || ! Hash::check($currentPassword, $lockedUser->v_password)) {
                throw new ApplicationException(
                    definition: $this->reader->read(AuthError::CURRENT_PASSWORD_INCORRECT),
                    context: ['userid' => $lockedUser->v_userid],
                );
            }

            $this->assertPasswordNotReused($lockedUser, $newPassword);

            if (is_string($lockedUser->v_password)) {
                UserPasswordHistory::create([
                    'v_userid' => $lockedUser->v_userid,
                    'v_password_hash' => $lockedUser->v_password,
                    'dt_created_at' => now(),
                ]);
            }

            $this->prunePasswordHistories($lockedUser->v_userid);

            $lockedUser->v_password = Hash::make($newPassword);
            $lockedUser->dt_last_updated_password = now();
            $lockedUser->v_remember_token = Str::random(60);
            $lockedUser->save();

            DB::table('sessions')->where('user_id', $lockedUser->v_userid)->delete();
        });
    }

    /**
     * Mengirimkan tautan reset/undangan password oleh Admin.
     */
    public function sendPasswordLink(User $actor, User $target): void
    {
        if ($target->b_use_other || ! $target->b_is_active || empty($target->v_email)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::ADMIN_RESET_FORBIDDEN),
                context: ['actor' => $actor->v_userid, 'target' => $target->v_userid],
            );
        }

        if ($target->v_userid === $actor->v_userid) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::ADMIN_RESET_FORBIDDEN),
                context: ['actor' => $actor->v_userid, 'target' => $target->v_userid],
            );
        }

        if (! $actor->isRoot() && ($target->isRoot() || $target->role_level >= $actor->role_level)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::ADMIN_RESET_FORBIDDEN),
                context: ['actor' => $actor->v_userid, 'target' => $target->v_userid],
            );
        }

        $throttleKey = 'admin-password-link:'.$actor->v_userid.':'.$target->v_userid;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::TOO_MANY_ATTEMPTS),
                context: ['seconds' => RateLimiter::availableIn($throttleKey)],
            );
        }
        RateLimiter::hit($throttleKey, 60);

        DB::table('password_reset_tokens')->where('email', $target->v_email)->delete();
        $token = Password::broker()->createToken($target);

        if ($target->dt_email_verified_at === null) {
            $target->notify(new InvitationNotification($token));
        } else {
            $target->notify(new ResetPasswordNotification($token, true));
        }
    }

    private function prunePasswordHistories(string $userId): void
    {
        $historyCount = UserPasswordHistory::where('v_userid', $userId)->count();
        if ($historyCount > 2) {
            $excess = $historyCount - 2;
            $oldestIds = UserPasswordHistory::where('v_userid', $userId)
                ->orderBy('dt_created_at', 'asc')
                ->limit($excess)
                ->pluck('i_id');
            UserPasswordHistory::whereIn('i_id', $oldestIds)->delete();
        }
    }

    private function assertPasswordNotReused(User $user, string $password): void
    {
        if (is_string($user->v_password) && Hash::check($password, $user->v_password)) {
            $this->throwPasswordReused($user);
        }

        $recentHistories = UserPasswordHistory::query()
            ->where('v_userid', $user->v_userid)
            ->orderByDesc('dt_created_at')
            ->limit(2)
            ->get();

        foreach ($recentHistories as $history) {
            if (Hash::check($password, $history->v_password_hash)) {
                $this->throwPasswordReused($user);
            }
        }
    }

    private function throwPasswordReused(User $user): never
    {
        throw new ApplicationException(
            definition: $this->reader->read(AuthError::PASSWORD_REUSED),
            context: ['userid' => $user->v_userid],
        );
    }

    private function throwInvalidToken(string $email): never
    {
        throw new ApplicationException(
            definition: $this->reader->read(AuthError::TOKEN_INVALID),
            context: ['email_hash' => hash('sha256', Str::lower($email))],
        );
    }
}
