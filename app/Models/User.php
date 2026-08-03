<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\RoleConstant;
use App\Contracts\HasNotFoundError;
use App\Contracts\ScopedResource;
use App\Errors\UserManagementError;
use App\Traits\HasObfuscatedId;
use App\Traits\HasOrganizationScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

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
class User extends Authenticatable implements HasNotFoundError, ScopedResource
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasObfuscatedId, HasOrganizationScope, Notifiable;

    /** @var array<string, mixed> */
    private array $authorizationCache = [];

    public static function notFoundError(): UserManagementError
    {
        return UserManagementError::USER_NOT_FOUND;
    }

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

    public function currentUserRoles(): HasMany
    {
        return $this->userRoles()->currentlyValid();
    }

    public function forgetAuthorizationCache(): void
    {
        $this->authorizationCache = [];
        $this->unsetRelation('currentUserRoles')->unsetRelation('userRoles');
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getCurrentUserRoles(): Collection
    {
        if (isset($this->authorizationCache['current_user_roles'])) {
            return $this->authorizationCache['current_user_roles'];
        }

        if ($this->relationLoaded('userRoles')) {
            $this->loadMissing('userRoles.roleModel.features');
            $roles = $this->userRoles
                ->filter(fn (UserRole $userRole) => $userRole->isCurrentlyValid())
                ->values();
        } else {
            $roles = $this->currentUserRoles()
                ->with('roleModel.features')
                ->get()
                ->filter(fn (UserRole $userRole) => $userRole->isCurrentlyValid())
                ->values();
            $this->setRelation('currentUserRoles', $roles);
        }

        return $this->authorizationCache['current_user_roles'] = $roles;
    }

    /**
     * Mengambil daftar kode role (v_role_code) milik user.
     *
     * @return array<string>
     */
    public function getRolesList(): array
    {
        return $this->authorizationCache['roles'] ??= $this->getCurrentUserRoles()
            ->pluck('v_role_code')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Mengambil ID / Code dari Group (Role) yang sedang aktif.
     */
    public function getActiveGroupId(): ?string
    {
        if (array_key_exists('active_group_id', $this->authorizationCache)) {
            return $this->authorizationCache['active_group_id'];
        }

        $sessionActiveGroup = session('active_group_id');
        $userRoles = $this->getRolesList();

        if (empty($userRoles)) {
            return $this->authorizationCache['active_group_id'] = null;
        }

        if ($sessionActiveGroup && in_array($sessionActiveGroup, $userRoles, true)) {
            return $this->authorizationCache['active_group_id'] = $sessionActiveGroup;
        }

        if ($this->v_default_group_id && in_array($this->v_default_group_id, $userRoles, true)) {
            return $this->authorizationCache['active_group_id'] = $this->v_default_group_id;
        }

        if (count($userRoles) === 1) {
            return $this->authorizationCache['active_group_id'] = $userRoles[0];
        }

        return $this->authorizationCache['active_group_id'] = null;
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

        return $this->getCurrentUserRoles()->firstWhere('v_role_code', $activeCode);
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
        $activeGroupId = $this->getActiveGroupId();
        $cacheKey = 'permissions:'.($activeGroupId ?? '*');
        if (isset($this->authorizationCache[$cacheKey])) {
            return $this->authorizationCache[$cacheKey];
        }

        if ($this->isRoot()) {
            return $this->authorizationCache[$cacheKey] = Feature::query()
                ->pluck('v_alias')
                ->unique()
                ->values()
                ->toArray();
        }

        $aliases = [];

        foreach ($this->getCurrentUserRoles() as $userRole) {
            if ($activeGroupId && $userRole->v_role_code !== $activeGroupId) {
                continue;
            }

            foreach ($userRole->roleModel?->features ?? [] as $feature) {
                $aliases[] = $feature->v_alias;
                if ($feature->v_parent) {
                    $aliases[] = $feature->v_parent;
                }
            }
        }

        return $this->authorizationCache[$cacheKey] = array_values(array_unique($aliases));
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
        $cacheKey = 'role_level:'.($activeGroupId ?? '*');
        if (isset($this->authorizationCache[$cacheKey])) {
            return $this->authorizationCache[$cacheKey];
        }

        $roles = $this->getCurrentUserRoles();
        if ($activeGroupId) {
            $level = (int) ($roles->firstWhere('v_role_code', $activeGroupId)?->roleModel?->i_level ?? 0);

            return $this->authorizationCache[$cacheKey] = $level;
        }

        return $this->authorizationCache[$cacheKey] = (int) ($roles
            ->pluck('roleModel.i_level')
            ->filter(fn ($level) => $level !== null)
            ->max() ?? 0);
    }

    /**
     * Cek apakah user berstatus Root Admin dalam konteks group aktif saat ini.
     */
    public function isRoot(): bool
    {
        return $this->role_level >= RoleConstant::ROOT_LEVEL;
    }
}
