<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\RoleConstant;
use App\Traits\HasObfuscatedId;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $hash_id
 * @property string $name
 * @property string|null $username
 * @property string $email
 * @property string|null $unit_name
 * @property string|null $role
 * @property int $role_level
 * @property bool $is_active
 * @property string $password
 * @property Carbon|null $email_verified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'username', 'email', 'unit_name', 'role', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasObfuscatedId, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke model Role berdasarkan v_code.
     */
    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'v_code');
    }

    /**
     * Mengambil level role user yang sedang login.
     */
    public function getRoleLevelAttribute(): int
    {
        if (! $this->role) {
            return RoleConstant::ROOT_LEVEL;
        }

        $roleObj = $this->roleModel;
        if ($roleObj) {
            return (int) $roleObj->i_level;
        }

        if (in_array(strtolower((string) $this->role), ['admin', 'super_admin', 'superadmin', 'root', 'adm_sys'], true)) {
            return RoleConstant::ROOT_LEVEL;
        }

        return 0;
    }

    /**
     * Cek apakah user adalah Root Admin.
     */
    public function isRoot(): bool
    {
        return $this->role_level >= RoleConstant::ROOT_LEVEL;
    }
}
