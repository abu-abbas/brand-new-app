<?php

namespace App\Http\Resources;

use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Feature
 */
class FeatureOptionResource extends JsonResource
{
    /**
     * @return array{id: int, name: string, alias: string, type: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->i_id,
            'name' => $this->v_name,
            'alias' => $this->v_alias,
            'type' => $this->e_type->value,
        ];
    }
}
