<?php

namespace App\Services;

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Errors\AuthError;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::TOO_MANY_ATTEMPTS),
                context: ['username' => $inputUsername],
            );
        }

        // 1. Cari user di tm_users (berdasarkan v_userid atau v_username)
        $user = User::where('v_userid', $inputUsername)
            ->orWhere('v_username', $inputUsername)
            ->first();

        if ($user) {
            // Cek status aktif
            if (! $user->b_is_active) {
                RateLimiter::hit($throttleKey, 60);
                throw new ApplicationException(
                    definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                    context: ['username' => $inputUsername],
                );
            }

            // Cek password sesuai flag b_use_other
            $isValid = $user->b_use_other
                ? $this->verifyMd5FromView($user->v_userid, $password)
                : (! empty($user->v_password) && Hash::check($password, $user->v_password));

            if (! $isValid) {
                RateLimiter::hit($throttleKey, 60);
                throw new ApplicationException(
                    definition: $this->reader->read(AuthError::INVALID_CREDENTIALS),
                    context: ['username' => $inputUsername],
                );
            }
        } else {
            // 2. Fallback - Cek ke vw_users untuk JIT Provisioning
            $vwUser = DB::table('vw_users')
                ->where('v_userid', $inputUsername)
                ->orWhere('v_username', $inputUsername)
                ->first();

            if (! $vwUser || empty($vwUser->v_password) || md5($password) !== $vwUser->v_password) {
                RateLimiter::hit($throttleKey, 60);
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
        }

        // 3. Cek ketersediaan role aktif di tr_user_roles
        $today = now()->format('Y-m-d');
        $hasRole = UserRole::where('v_userid', $user->v_userid)
            ->where(function ($q) use ($today) {
                $q->whereNull('dt_valid_from')->orWhere('dt_valid_from', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('dt_valid_until')->orWhere('dt_valid_until', '>=', $today);
            })
            ->exists();

        if (! $hasRole && ! $user->isRoot()) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::NO_ROLE_ASSIGNED),
                context: ['username' => $inputUsername],
            );
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        Auth::guard('web')->login($user);

        // Auto-set active group jika hanya punya 1 group atau punya default_group_id
        $roles = $user->getRolesList();
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

        if (! $user->isRoot() && ! in_array($groupId, $userRoles, true)) {
            throw new ApplicationException(
                definition: $this->reader->read(AuthError::INVALID_GROUP),
                context: ['userid' => $user->v_userid, 'group_id' => $groupId],
            );
        }

        session(['active_group_id' => $groupId]);

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
}
