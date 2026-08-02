<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $i_id
 * @property string $v_userid
 * @property string $v_role_code
 * @property string|null $v_wilayah
 * @property string|null $v_unit
 * @property string|null $v_pelaksana
 * @property string|null $dt_valid_from
 * @property string|null $dt_valid_until
 * @property string|null $v_created_by
 * @property string|null $dt_created_at
 * @property string|null $v_updated_by
 * @property string|null $dt_updated_at
 */
class UserRole extends Model
{
    protected $table = 'tr_user_roles';

    protected $primaryKey = 'i_id';

    public $timestamps = false;

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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_userid', 'v_userid');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'v_role_code', 'v_code');
    }
}
