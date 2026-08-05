<?php

namespace App\Http\Resources;

use App\Models\UserRole;
use App\Services\ReferenceService;
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
     *     is_expired: bool,
     *     need_region: bool,
     *     need_unit: bool
     * }
     */
    public function toArray(Request $request): array
    {
        $unitName = null;
        if ($this->v_unit) {
            static $unitMap = null;
            if ($unitMap === null) {
                $unitOptions = app(ReferenceService::class)->getPerangkatDaerahOptions(null);
                $unitMap = array_column($unitOptions, 'name', 'code');
            }
            $rawUnit = $unitMap[$this->v_unit] ?? $this->v_unit;
            $unitName = toTitleCase($rawUnit);
        }

        $wilayahName = null;
        if ($this->v_wilayah) {
            static $wilMap = null;
            if ($wilMap === null) {
                $wilOptions = app(ReferenceService::class)->getWilayahOptions(null);
                $wilMap = array_column($wilOptions, 'name', 'code');
            }
            $wilCodes = array_map('trim', explode(',', $this->v_wilayah));
            $mappedNames = array_map(fn ($c) => $wilMap[$c] ?? $c, $wilCodes);
            $wilayahName = implode(', ', $mappedNames);
        }

        return [
            'id' => $this->i_id,
            'userid' => $this->v_userid,
            'role_code' => $this->v_role_code,
            'role_name' => $this->roleModel
                ? toTitleCase($this->roleModel->v_name)
                : $this->v_role_code,
            'wilayah' => $this->v_wilayah,
            'wilayah_name' => $wilayahName,
            'unit' => $this->v_unit,
            'unit_name' => $unitName,
            'pelaksana' => $this->v_pelaksana,
            'valid_from' => $this->dt_valid_from?->toDateString(),
            'valid_until' => $this->dt_valid_until?->toDateString(),
            'is_expired' => ! $this->isCurrentlyValid(),
            'need_region' => (bool) ($this->roleModel?->b_need_region ?? false),
            'need_unit' => (bool) ($this->roleModel?->b_need_unit ?? false),
        ];
    }
}
