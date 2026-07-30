<?php

namespace App\Models;

use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $i_id
 * @property string $v_name
 * @property string $v_alias
 * @property PermissionType $e_type
 * @property string|null $v_parent
 * @property string|null $v_desc
 * @property string|null $v_route
 * @property string|null $v_icon
 * @property int $si_order
 * @property bool $b_show_on_sidebar
 * @property string|null $v_created_by
 * @property Carbon|null $dt_created_at
 * @property string|null $v_updated_by
 * @property Carbon|null $dt_updated_at
 * @property string|null $v_deleted_by
 * @property Carbon|null $dt_deleted_at
 */
class Feature extends Model
{
    use SoftDeletes;

    protected $table = 'tm_features';

    protected $primaryKey = 'i_id';

    public function getRouteKeyName(): string
    {
        return 'v_alias';
    }

    /** @var string */
    const CREATED_AT = 'dt_created_at';

    /** @var string */
    const UPDATED_AT = 'dt_updated_at';

    /** @var string */
    const DELETED_AT = 'dt_deleted_at';

    protected $fillable = [
        'v_name',
        'v_alias',
        'e_type',
        'v_parent',
        'v_desc',
        'v_route',
        'v_icon',
        'si_order',
        'b_show_on_sidebar',
        'v_created_by',
        'v_updated_by',
        'v_deleted_by',
    ];

    protected $casts = [
        'e_type' => PermissionType::class,
        'b_show_on_sidebar' => 'boolean',
        'si_order' => 'integer',
        'dt_created_at' => 'datetime',
        'dt_updated_at' => 'datetime',
        'dt_deleted_at' => 'datetime',
    ];

    /**
     * Relasi ke parent feature (self-referential melalui v_alias).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Feature::class, 'v_parent', 'v_alias');
    }

    /**
     * Relasi ke children features.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Feature::class, 'v_parent', 'v_alias');
    }

    /**
     * Scope: hanya menu yang tampil di sidebar.
     */
    public function scopeVisibleOnSidebar(Builder $query): Builder
    {
        return $query->where('b_show_on_sidebar', true)->orderBy('si_order');
    }

    /**
     * Scope: filter berdasarkan tipe.
     */
    public function scopeOfType(Builder $query, PermissionType $type): Builder
    {
        return $query->where('e_type', $type->value);
    }
}
