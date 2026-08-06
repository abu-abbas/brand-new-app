<?php

namespace App\Models;

use App\Traits\HasObfuscatedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $i_id
 * @property string $hash_id
 * @property string $v_subject_type
 * @property string $v_subject_id
 * @property string $v_event
 * @property string $v_causer_id
 * @property string|null $v_impersonator_id
 * @property string|null $v_request_id
 * @property string|null $v_ip_address
 * @property array<string, mixed>|null $j_properties
 * @property Carbon $dt_created_at
 * @property-read User|null $causer
 * @property-read User|null $impersonator
 *
 * @method static self create(array<string, mixed> $attributes = [])
 */
class ActivityLog extends Model
{
    use HasObfuscatedId;

    protected $table = 'tm_activity_log';

    protected $primaryKey = 'i_id';

    public $timestamps = false;

    protected $fillable = [
        'v_subject_type',
        'v_subject_id',
        'v_event',
        'v_causer_id',
        'v_impersonator_id',
        'v_request_id',
        'v_ip_address',
        'j_properties',
        'dt_created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'j_properties' => 'array',
            'dt_created_at' => 'datetime',
        ];
    }

    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_causer_id', 'v_userid');
    }

    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'v_impersonator_id', 'v_userid');
    }
}
