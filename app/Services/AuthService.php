<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly ErrorDefinitionReader $reader,
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
            $isPasswordValid = false;
            if ($user->v_password && Hash::check($password, $user->v_password)) {
                $isPasswordValid = true;
            } elseif ($user->b_use_other && $this->verifyMd5FromView($user->v_userid, $password)) {
                $isPasswordValid = true;
            }

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
            ->where('v_userid', $inputUsername)
            ->first();

        if (! $vwUser || empty($vwUser->v_password) || md5($password) !== $vwUser->v_password) {
            RateLimiter::hit($throttleKey, 300);
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                context: ['username' => $inputUsername],
            );
        }

        // Auto-provisioning ke tm_users
        $user = User::create([
            'v_userid' => (string) $vwUser->v_userid,
            'v_username' => (string) ($vwUser->v_username ?? $vwUser->v_userid),
            'v_password' => null,
            'b_is_active' => true,
            'b_use_other' => true,
            'v_klogad' => $vwUser->v_klogad ?? null,
            'v_kolok' => $vwUser->v_kolok ?? null,
            'v_kojab' => $vwUser->v_kojab ?? null,
            'v_koper' => $vwUser->v_koper ?? null,
            'v_kopang' => $vwUser->v_kopang ?? null,
            'v_eselon' => $vwUser->v_eselon ?? null,
            'v_spmu' => $vwUser->v_spmu ?? null,
            'v_kd' => $vwUser->v_kd ?? null,
            'dt_created_at' => now(),
        ]);

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
        $throttleKey = 'forgot-password:'.Str::lower($email).'|'.$ip;

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            Log::warning('AuthService@forgotPassword rate limited', ['email' => $email, 'ip' => $ip]);

            return;
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::where('v_email', $email)
            ->whereNull('dt_deleted_at')
            ->first();

        Log::info('AuthService@forgotPassword dipanggil', [
            'email_input' => $email,
            'user_exists' => (bool) $user,
            'userid' => $user?->v_userid,
            'b_use_other' => $user?->b_use_other,
            'b_is_active' => $user?->b_is_active,
            'v_email' => $user?->v_email,
        ]);

        if ($user && ! $user->b_use_other && $user->b_is_active && ! empty($user->v_email)) {
            DB::table('password_reset_tokens')->where('email', $user->v_email)->delete();
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);

            Log::info('AuthService@forgotPassword: Mengirimkan ResetPasswordNotification', [
                'userid' => $user->v_userid,
                'email' => $user->v_email,
                'token' => $token,
            ]);

            $user->notify(new \App\Notifications\ResetPasswordNotification($token, false));
        } else {
            Log::notice('AuthService@forgotPassword: User tidak memenuhi syarat pengiriman email', [
                'email' => $email,
                'user_found' => (bool) $user,
                'b_use_other' => $user?->b_use_other,
                'b_is_active' => $user?->b_is_active,
            ]);
        }
    }

    /**
     * Memproses reset password menggunakan token.
     */
    public function resetPassword(string $email, string $token, string $password): void
    {
        $user = User::where('v_email', $email)
            ->whereNull('dt_deleted_at')
            ->first();

        if (! $user || ! \Illuminate\Support\Facades\Password::broker()->tokenExists($user, $token)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::TOKEN_INVALID),
                context: ['email' => $email],
            );
        }

        DB::transaction(function () use ($user, $password) {
            if ($user->v_password) {
                \App\Models\UserPasswordHistory::create([
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

            \Illuminate\Support\Facades\Password::broker()->deleteToken($user);
            DB::table('sessions')->where('user_id', $user->v_userid)->delete();
        });
    }

    /**
     * Memproses pengubahan password oleh pengguna terautentikasi.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (empty($user->v_password) || ! Hash::check($currentPassword, $user->v_password)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::CURRENT_PASSWORD_INCORRECT),
                context: ['userid' => $user->v_userid],
            );
        }

        DB::transaction(function () use ($user, $newPassword) {
            if ($user->v_password) {
                \App\Models\UserPasswordHistory::create([
                    'v_userid' => $user->v_userid,
                    'v_password_hash' => $user->v_password,
                    'dt_created_at' => now(),
                ]);
            }

            $this->prunePasswordHistories($user->v_userid);

            $user->v_password = Hash::make($newPassword);
            $user->dt_last_updated_password = now();
            $user->v_remember_token = Str::random(60);
            $user->save();

            DB::table('sessions')->where('user_id', $user->v_userid)->delete();
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

        DB::table('password_reset_tokens')->where('email', $target->v_email)->delete();
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($target);

        if ($target->dt_email_verified_at === null) {
            $target->notify(new \App\Notifications\InvitationNotification($token));
        } else {
            $target->notify(new \App\Notifications\ResetPasswordNotification($token, true));
        }
    }

    private function prunePasswordHistories(string $userId): void
    {
        $historyCount = \App\Models\UserPasswordHistory::where('v_userid', $userId)->count();
        if ($historyCount > 5) {
            $excess = $historyCount - 5;
            $oldestIds = \App\Models\UserPasswordHistory::where('v_userid', $userId)
                ->orderBy('dt_created_at', 'asc')
                ->limit($excess)
                ->pluck('i_id');
            \App\Models\UserPasswordHistory::whereIn('i_id', $oldestIds)->delete();
        }
    }
}
