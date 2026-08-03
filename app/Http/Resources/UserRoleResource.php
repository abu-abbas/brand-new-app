<?php

namespace App\Http\Resources;

use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserRole
 */
class UserRoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: int,
     *     userid: string,
     *     role_code: string,
     *     wilayah: string|null,
     *     unit: string|null,
     *     pelaksana: string|null,
     *     valid_from: string|null,
     *     valid_until: string|null,
     *     need_region: bool,
     *     need_unit: bool
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->i_id,
            'userid' => $this->v_userid,
            'role_code' => $this->v_role_code,
            'role_name' => $this->roleModel
                ? toTitleCase($this->roleModel->v_name)
                : $this->v_role_code,
            'wilayah' => $this->v_wilayah,
            'unit' => $this->v_unit,
            'pelaksana' => $this->v_pelaksana,
            'valid_from' => $this->dt_valid_from?->toDateString(),
            'valid_until' => $this->dt_valid_until?->toDateString(),
            'need_region' => (bool) ($this->roleModel?->b_need_region ?? false),
            'need_unit' => (bool) ($this->roleModel?->b_need_unit ?? false),
        ];
    }
}
