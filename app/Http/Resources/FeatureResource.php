<?php

namespace App\Http\Resources;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feature
 */
class FeatureResource extends JsonResource
{
    /**
     * @return array{
     *     name: string,
     *     alias: string,
     *     type: string,
     *     type_label: string,
     *     parent: string|null,
     *     description: string|null,
     *     route: string|null,
     *     icon: string|null,
     *     order: int,
     *     show_on_sidebar: bool,
     *     created_at: string|null,
     *     updated_at: string|null,
     *     deleted_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => toTitleCase($this->v_name),
            'alias' => $this->v_alias,
            'type' => $this->e_type->value,
            'type_label' => $this->e_type->label(),
            'parent' => $this->v_parent,
            'description' => $this->v_desc,
            'route' => $this->v_route,
            'icon' => $this->v_icon,
            'order' => $this->si_order,
            'show_on_sidebar' => $this->b_show_on_sidebar,
            'is_restricted' => (bool) $this->b_is_restricted,
            'created_at' => $this->dt_created_at?->toIso8601String(),
            'updated_at' => $this->dt_updated_at?->toIso8601String(),
            'deleted_at' => $this->dt_deleted_at?->toIso8601String(),
        ];
    }
}
