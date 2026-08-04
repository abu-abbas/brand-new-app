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
     *     is_active: bool,
     *     is_expired: bool,
     *     locked: bool,
     *     can_edit: bool,
     *     can_delete: bool,
     *     features: array<int, array{alias: string, name: string}>,
     *     updated_at: string|null,
     *     deleted_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $features = $this->whenLoaded('features', fn () => $this->features->map(fn ($feature) => [
            'alias' => $feature->v_alias,
            'name' => toTitleCase($feature->v_name),
        ])->values()->all(), []);

        $user = $request->user();
        $isRoot = (bool) $user?->isRoot();
        $canEdit = (bool) $user?->can('update', $this->resource);
        $canDelete = (bool) $user?->can('delete', $this->resource) && ! (bool) $this->b_locked;

        return [
            'id' => $this->hash_id,
            'code' => $this->v_code,
            'name' => toTitleCase($this->v_name),
            'level' => $this->when($isRoot, (int) $this->i_level),
            'need_region' => (bool) $this->b_need_region,
            'need_unit' => (bool) $this->b_need_unit,
            'active_periode' => $this->v_active_periode,
            'is_active' => $this->dt_deleted_at === null && $this->isCurrentlyActive(),
            'is_expired' => $this->dt_deleted_at === null && ! $this->isCurrentlyActive(),
            'locked' => (bool) $this->b_locked,
            'can_edit' => $canEdit,
            'can_delete' => $canDelete ? true : false,
            'features' => $features,
            'updated_at' => $this->dt_updated_at?->toIso8601String(),
            'deleted_at' => $this->dt_deleted_at?->toIso8601String(),
        ];
    }
}
