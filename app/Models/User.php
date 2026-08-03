<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\RoleConstant;
use App\Contracts\ScopedResource;
use App\Traits\HasObfuscatedId;
use App\Traits\HasOrganizationScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $i_id
 * @property string $hash_id
 * @property string $v_userid
 * @property string $v_username
 * @property string|null $v_email
 * @property string|null $v_password
 * @property bool $b_is_active
 * @property bool $b_use_other
 * @property string|null $v_klogad
 * @property string|null $v_kolok
 * @property string|null $v_kojab
 * @property string|null $v_koper
 * @property string|null $v_kopang
 * @property string|null $v_eselon
 * @property string|null $v_spmu
 * @property string|null $v_kd
 * @property string|null $v_remember_token
 * @property string|null $v_created_by
 * @property Carbon|null $dt_created_at
 * @property string|null $v_updated_by
 * @property Carbon|null $dt_updated_at
 * @property string|null $v_deleted_by
 * @property Carbon|null $dt_deleted_at
 * @property int $role_level
 */
#[Fillable([
    'v_userid',
    'v_username',
    'v_email',
    'v_password',
    'b_is_active',
    'b_use_other',
    'v_default_group_id',
    'v_klogad',
    'v_kolok',
    'v_kojab',
    'v_koper',
    'v_kopang',
    'v_eselon',
    'v_spmu',
    'v_kd',
    'v_remember_token',
    'v_created_by',
    'dt_created_at',
    'v_updated_by',
    'dt_updated_at',
    'v_deleted_by',
    'dt_deleted_at',
])]
#[Hidden(['v_password', 'v_remember_token'])]
class User extends Authenticatable implements ScopedResource
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasObfuscatedId, HasOrganizationScope, Notifiable;

    public function getResourceWilayah(): ?string
    {
        return $this->v_kolok;
    }

    public function getResourceUnit(): ?string
    {
        return $this->v_kolok;
    }

    public function getResourceLevel(): ?int
    {
        return $this->role_level;
    }

    protected $table = 'tm_users';

    protected $primaryKey = 'i_id';

    public $timestamps = false;

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName(): string
    {
        return 'v_userid';
    }

    /**
     * Get the password for the user.
     */
    public function getAuthPasswordName(): string
    {
        return 'v_password';
    }

    /**
     * Get the column name for the "remember me" token.
     */
    public function getRememberTokenName(): string
    {
        return 'v_remember_token';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'b_is_active' => 'boolean',
            'b_use_other' => 'boolean',
            'dt_created_at' => 'datetime',
            'dt_updated_at' => 'datetime',
            'dt_deleted_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke tabel pivot tr_user_roles.
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class, 'v_userid', 'v_userid');
    }

    /**
     * Mengambil daftar kode role (v_role_code) milik user.
     *
     * @return array<string>
     */
    public function getRolesList(): array
    {
        if ($this->relationLoaded('userRoles')) {
            return $this->userRoles->pluck('v_role_code')->filter()->unique()->values()->toArray();
        }

        return $this->userRoles()->pluck('v_role_code')->filter()->unique()->values()->toArray();
    }

    /**
     * Mengambil ID / Code dari Group (Role) yang sedang aktif.
     */
    public function getActiveGroupId(): ?string
    {
        $sessionActiveGroup = session('active_group_id');
        $userRoles = $this->getRolesList();

        if (empty($userRoles)) {
            return null;
        }

        if ($sessionActiveGroup && in_array($sessionActiveGroup, $userRoles, true)) {
            return $sessionActiveGroup;
        }

        if ($this->v_default_group_id && in_array($this->v_default_group_id, $userRoles, true)) {
            return $this->v_default_group_id;
        }

        if (count($userRoles) === 1) {
            return $userRoles[0];
        }

        return null;
    }

    /**
     * Memeriksa apakah user memiliki lebih dari 1 group/role.
     */
    public function hasMultipleGroups(): bool
    {
        return count($this->getRolesList()) > 1;
    }

    /**
     * Mengambil model UserRole yang sedang aktif.
     */
    public function getActiveUserRole(): ?UserRole
    {
        $activeCode = $this->getActiveGroupId();
        if (! $activeCode) {
            return null;
        }

        if ($this->relationLoaded('userRoles')) {
            return $this->userRoles->firstWhere('v_role_code', $activeCode);
        }

        return $this->userRoles()->where('v_role_code', $activeCode)->first();
    }

    /**
     * Mengambil daftar alias permission (v_alias) milik user.
     * Memanfaatkan eager-loaded relation bila sudah di-load.
     * jika active group diset, hanya mengambil permission dari active group tersebut.
     *
     * @return array<string>
     */
    public function getPermissionsList(): array
    {
        if ($this->isRoot()) {
            static $rootFeatures = null;
            if ($rootFeatures === null) {
                $rootFeatures = Feature::whereNull('dt_deleted_at')->pluck('v_alias')->unique()->values()->toArray();
            }

            return $rootFeatures;
        }

        $activeGroupId = $this->getActiveGroupId();
        $aliases = [];

        if ($this->relationLoaded('userRoles')) {
            foreach ($this->userRoles as $userRole) {
                if ($activeGroupId && $userRole->v_role_code !== $activeGroupId) {
                    continue;
                }
                $role = $userRole->relationLoaded('roleModel') ? $userRole->roleModel : $userRole->roleModel()->first();
                if ($role) {
                    $featureAliases = $role->relationLoaded('features')
                        ? $role->features->pluck('v_alias')->toArray()
                        : $role->features()->pluck('tm_features.v_alias')->toArray();
                    $aliases = array_merge($aliases, $featureAliases);
                }
            }
        } else {
            $roleCodes = $this->userRoles()->pluck('v_role_code')->filter()->toArray();
            if ($activeGroupId && in_array($activeGroupId, $roleCodes, true)) {
                $roleCodes = [$activeGroupId];
            }
            if (! empty($roleCodes)) {
                $aliases = DB::table('tr_role_features')
                    ->whereIn('v_code', $roleCodes)
                    ->pluck('v_alias')
                    ->toArray();
            }
        }

        if (empty($aliases)) {
            return [];
        }

        $parentAliases = Feature::whereIn('v_alias', $aliases)
            ->whereNotNull('v_parent')
            ->pluck('v_parent')
            ->toArray();

        return array_values(array_unique(array_merge($aliases, $parentAliases)));
    }

    /**
     * Accessor kustom untuk alias `name`.
     */
    public function getNameAttribute(): string
    {
        return $this->v_username ?? $this->v_userid ?? '';
    }

    /**
     * Accessor kustom untuk alias `username`.
     */
    public function getUsernameAttribute(): string
    {
        return $this->v_userid ?? '';
    }

    /**
     * Accessor kustom untuk alias `email`.
     */
    public function getEmailAttribute(): string
    {
        return $this->v_email ?? "{$this->v_userid}@domain.local";
    }

    /**
     * Accessor kustom untuk alias `is_active`.
     */
    public function getIsActiveAttribute(): bool
    {
        return (bool) $this->b_is_active;
    }

    /**
     * Accessor kustom untuk alias `created_at`.
     */
    public function getCreatedAtAttribute(): ?Carbon
    {
        return $this->dt_created_at;
    }

    /**
     * Mengambil level role dari group yang sedang aktif, atau fallback ke level role tertinggi.
     */
    public function getRoleLevelAttribute(): int
    {
        $activeGroupId = $this->getActiveGroupId();
        if ($activeGroupId) {
            if ($this->relationLoaded('userRoles')) {
                $userRole = $this->userRoles->firstWhere('v_role_code', $activeGroupId);
                if ($userRole) {
                    $roleModel = $userRole->relationLoaded('roleModel') ? $userRole->roleModel : $userRole->roleModel()->first();
                    if ($roleModel) {
                        return (int) $roleModel->i_level;
                    }
                }
            } else {
                $userRole = $this->userRoles()->where('v_role_code', $activeGroupId)->first();
                if ($userRole) {
                    $roleModel = $userRole->roleModel()->first();
                    if ($roleModel) {
                        return (int) $roleModel->i_level;
                    }
                }
            }

            $roleObj = Role::where('v_code', $activeGroupId)->first();
            if ($roleObj) {
                return (int) $roleObj->i_level;
            }
        }

        if ($this->relationLoaded('userRoles')) {
            $highestLevel = 0;
            foreach ($this->userRoles as $userRole) {
                $roleModel = $userRole->relationLoaded('roleModel') ? $userRole->roleModel : null;
                if ($roleModel && (int) $roleModel->i_level > $highestLevel) {
                    $highestLevel = (int) $roleModel->i_level;
                }
            }
            if ($highestLevel > 0) {
                return $highestLevel;
            }
        }

        $roleCodes = $this->userRoles->pluck('v_role_code')->toArray();
        if (empty($roleCodes)) {
            return 0;
        }

        $highestLevel = 0;
        $roles = Role::whereIn('v_code', $roleCodes)->get();
        foreach ($roles as $roleObj) {
            if ((int) $roleObj->i_level > $highestLevel) {
                $highestLevel = (int) $roleObj->i_level;
            }
        }

        if ($highestLevel === 0) {
            foreach ($roleCodes as $code) {
                if (in_array(strtolower((string) $code), ['admin', 'super_admin', 'superadmin', 'root', 'adm_sys', 'sysadmin'], true)) {
                    return RoleConstant::ROOT_LEVEL;
                }
            }
        }

        return $highestLevel;
    }

    /**
     * Cek apakah user berstatus Root Admin dalam konteks group aktif saat ini.
     */
    public function isRoot(): bool
    {
        $activeGroupId = $this->getActiveGroupId();
        if ($activeGroupId) {
            return in_array(strtoupper($activeGroupId), ['ROOT', 'ADM_SYS', 'SYSADMIN'], true);
        }

        if (in_array(strtolower((string) $this->v_userid), ['root', 'adm_sys', 'sysadmin'], true)) {
            return true;
        }

        return $this->role_level >= RoleConstant::ROOT_LEVEL;
    }
}
