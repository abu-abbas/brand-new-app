<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     username: string,
     *     email: string,
     *     unit: array{name: string},
     *     roles: array<string>,
     *     active: bool,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hash_id,
            'name' => $this->name,
            'username' => $this->username ?? "user{$this->id}",
            'email' => $this->email,
            'unit' => [
                'name' => $this->unit_name ?? 'Umum',
            ],
            'roles' => [$this->role ?? 'Staff'],
            'is_root' => $this->when($this->isRoot(), true),
            'active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
