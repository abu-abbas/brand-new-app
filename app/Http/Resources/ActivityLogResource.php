<?php

namespace App\Http\Resources;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin ActivityLog
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $impersonatorId = ($this->v_impersonator_id && $this->v_impersonator_id !== $this->v_causer_id)
            ? $this->v_impersonator_id
            : null;

        $rawReason = (is_array($this->j_properties) && isset($this->j_properties['reason']) && is_string($this->j_properties['reason']))
            ? $this->j_properties['reason']
            : null;

        $reason = match ($rawReason) {
            'manual_leave' => 'Manual Leave',
            'ttl_expired' => 'TTL Expired',
            'session_invalidated' => 'Session Invalidated',
            default => $rawReason ? Str::headline($rawReason) : null,
        };

        $title = match ($this->v_event) {
            'auth.login', 'login' => 'Login ke Sistem',
            'auth.logout', 'logout' => 'Logout dari Sistem',
            'impersonation_started', 'impersonation.started' => 'Mulai Impersonasi Pengguna',
            'impersonation_stopped', 'impersonation.stopped', 'impersonation_expired', 'impersonation_invalidated' => 'Selesai Impersonasi Pengguna',
            'password.changed', 'password_changed' => 'Perubahan Password',
            default => Str::headline($this->v_event),
        };

        if ($reason !== null) {
            $title .= ' - '.$reason;
        }

        $causer = $this->causer ? $this->causer->v_username : ($this->v_causer_id ?: 'Sistem');
        $impersonator = ($impersonatorId && $this->impersonator) ? $this->impersonator->v_username : null;

        return [
            'id' => $this->hash_id,
            'event' => $this->v_event,
            'title' => $title,
            'reason' => $reason,
            'causer_name' => $causer,
            'impersonator_name' => $impersonator,
            'is_impersonated' => $impersonator !== null,
            'request_id' => $this->v_request_id,
            'ip_address' => $this->v_ip_address,
            'created_at' => $this->dt_created_at->toIso8601String(),
        ];
    }
}
