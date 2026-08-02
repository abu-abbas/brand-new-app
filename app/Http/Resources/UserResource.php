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
     *     permissions: array<string>,
     *     is_root?: bool,
     *     active: bool,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hash_id,
            'name' => $this->v_username ?? $this->v_userid,
            'username' => $this->v_userid,
            'email' => $this->v_email ?? "{$this->v_userid}@domain.local",
            'unit' => [
                'name' => $this->v_kolok ?? 'Umum',
            ],
            'roles' => $this->getRolesList(),
            'permissions' => $this->getPermissionsList(),
            'is_root' => $this->when($this->isRoot(), true),
            'active' => (bool) $this->b_is_active,
            'created_at' => $this->dt_created_at?->toIso8601String(),
        ];
    }
}
