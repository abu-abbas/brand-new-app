<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $i_id
 * @property string $v_userid
 * @property string $v_role_code
 * @property string|null $v_wilayah
 * @property string|null $v_unit
 * @property string|null $v_pelaksana
 * @property Carbon|null $dt_valid_from
 * @property Carbon|null $dt_valid_until
 * @property string|null $v_created_by
 * @property string|null $dt_created_at
 * @property string|null $v_updated_by
 * @property string|null $dt_updated_at
 * @property string|null $v_deleted_by
 * @property Carbon|null $dt_deleted_at
 * @property-read Role|null $roleModel
 * @property-read User $user
 */
class UserRole extends Model
{
    use SoftDeletes;

    protected $table = 'tr_user_roles';

    protected $primaryKey = 'i_id';

    public $timestamps = false;

    const DELETED_AT = 'dt_deleted_at';

    protected $fillable = [
        'v_userid',
        'v_role_code',
        'v_wilayah',
        'v_unit',
        'v_pelaksana',
        'dt_valid_from',
        'dt_valid_until',
        'v_created_by',
        'dt_created_at',
        'v_updated_by',
        'dt_updated_at',
        'v_deleted_by',
        'dt_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'dt_valid_from' => 'date:Y-m-d',
            'dt_valid_until' => 'date:Y-m-d',
            'dt_deleted_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<UserRole>  $query
     * @return Builder<UserRole>
     */
    public function scopeCurrentlyValid(Builder $query, ?Carbon $date = null): Builder
    {
        $date = ($date ?? today())->toDateString();

        return $query
            ->where(fn (Builder $query) => $query
                ->whereNull('dt_valid_from')
                ->orWhereDate('dt_valid_from', '<=', $date))
            ->where(fn (Builder $query) => $query
                ->whereNull('dt_valid_until')
                ->orWhereDate('dt_valid_until', '>=', $date));
    }

    public function isCurrentlyValid(?Carbon $date = null): bool
    {
        $date = ($date ?? today())->startOfDay();

        return $this->dt_deleted_at === null
            && ($this->dt_valid_from === null || $this->dt_valid_from->lte($date))
            && ($this->dt_valid_until === null || $this->dt_valid_until->gte($date))
            && ($this->roleModel === null || $this->roleModel->isCurrentlyActive($date));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_userid', 'v_userid');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'v_role_code', 'v_code');
    }
}
