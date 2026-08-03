<?php

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array{
     *     id: string,
     *     code: string,
     *     name: string,
     *     level?: int,
     *     need_region: bool,
     *     need_unit: bool,
     *     active_periode: array<string, mixed>|null,
     *     locked: bool,
     *     features: array<int, array{alias: string, name: string}>,
     *     updated_at: string|null,
     *     deleted_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $featureModels = $this->relationLoaded('features')
            ? $this->features
            : $this->features()->get(['tm_features.v_alias', 'tm_features.v_name']);

        $features = $featureModels->map(fn ($f) => [
            'alias' => $f->v_alias,
            'name' => $f->v_name,
        ])->values()->all();

        $user = $request->user();
        $isRoot = (bool) $user?->isRoot();
        $canManage = false;

        if ($user) {
            if ($isRoot) {
                $canManage = true;
            } else {
                $firstRole = $user->relationLoaded('userRoles')
                    ? $user->userRoles->pluck('v_role_code')->first()
                    : $user->userRoles()->pluck('v_role_code')->first();

                if ($firstRole) {
                    $prefix = strtoupper((string) $firstRole).'_';
                    $canManage = str_starts_with(strtoupper($this->v_code), $prefix);
                }
            }
        }

        return [
            'id' => $this->hash_id,
            'code' => $this->v_code,
            'name' => $this->v_name,
            'level' => $this->when($isRoot, (int) $this->i_level),
            'need_region' => (bool) $this->b_need_region,
            'need_unit' => (bool) $this->b_need_unit,
            'active_periode' => $this->v_active_periode,
            'locked' => (bool) $this->b_locked,
            'can_edit' => $canManage,
            'can_delete' => $canManage && ! (bool) $this->b_locked,
            'features' => array_values($features),
            'updated_at' => $this->dt_updated_at?->toIso8601String(),
            'deleted_at' => $this->dt_deleted_at?->toIso8601String(),
        ];
    }
}
