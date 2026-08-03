<?php

namespace App\Models;

use App\Contracts\HasNotFoundError;
use App\Errors\RoleError;
use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $i_id
 * @property string $hash_id
 * @property string $v_code
 * @property string $v_name
 * @property int $i_level
 * @property bool $b_need_region
 * @property bool $b_need_unit
 * @property array<string, string>|null $v_active_periode
 * @property bool $b_locked
 * @property string|null $v_created_by
 * @property Carbon|null $dt_created_at
 * @property string|null $v_updated_by
 * @property Carbon|null $dt_updated_at
 * @property string|null $v_deleted_by
 * @property Carbon|null $dt_deleted_at
 * @property Collection<int, Feature> $features
 */
class Role extends Model implements HasNotFoundError
{
    use HasObfuscatedId, SoftDeletes;

    protected $table = 'tm_roles';

    protected $primaryKey = 'i_id';

    public static function notFoundError(): RoleError
    {
        return RoleError::ROLE_NOT_FOUND;
    }

    /** @var string */
    const CREATED_AT = 'dt_created_at';

    /** @var string */
    const UPDATED_AT = 'dt_updated_at';

    /** @var string */
    const DELETED_AT = 'dt_deleted_at';

    protected $fillable = [
        'v_code',
        'v_name',
        'i_level',
        'b_need_region',
        'b_need_unit',
        'v_active_periode',
        'b_locked',
        'v_created_by',
        'v_updated_by',
        'v_deleted_by',
    ];

    protected $casts = [
        'i_level' => 'integer',
        'b_need_region' => 'boolean',
        'b_need_unit' => 'boolean',
        'b_locked' => 'boolean',
        'v_active_periode' => 'array',
        'dt_created_at' => 'datetime',
        'dt_updated_at' => 'datetime',
        'dt_deleted_at' => 'datetime',
    ];

    /**
     * Relasi ke features.
     */
    public function features(): BelongsToMany
    {
        return $this->belongsToMany(
            Feature::class,
            'tr_role_features',
            'v_code',
            'v_alias',
            'v_code',
            'v_alias'
        );
    }
}
