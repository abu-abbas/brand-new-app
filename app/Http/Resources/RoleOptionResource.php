<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleOptionResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     name: string,
     *     need_region: bool,
     *     need_unit: bool,
     *     active_periode: array<string, mixed>|null,
     *     locked: bool
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hash_id,
            'code' => $this->v_code,
            'name' => $this->v_name,
            'need_region' => (bool) $this->b_need_region,
            'need_unit' => (bool) $this->b_need_unit,
            'active_periode' => $this->v_active_periode,
            'locked' => (bool) $this->b_locked,
        ];
    }
}
