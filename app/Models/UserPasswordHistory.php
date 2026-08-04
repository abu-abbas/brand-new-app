<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $i_id
 * @property string $v_userid
 * @property string $v_password_hash
 * @property Carbon|null $dt_created_at
 */
class UserPasswordHistory extends Model
{
    protected $table = 'tr_user_password_histories';

    protected $primaryKey = 'i_id';

    public $timestamps = false;

    protected $fillable = [
        'v_userid',
        'v_password_hash',
        'dt_created_at',
    ];

    protected function casts(): array
    {
        return [
            'dt_created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_userid', 'v_userid');
    }
}
