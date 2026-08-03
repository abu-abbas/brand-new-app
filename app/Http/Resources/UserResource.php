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
     *     userid: string,
     *     username: string,
     *     name: string,
     *     email: string|null,
     *     unit: array{code: string|null, name: string},
     *     is_active: bool,
     *     is_external: bool,
     *     roles: array<string>,
     *     user_roles?: array<mixed>,
     *     permissions: array<string>,
     *     is_root?: bool,
     *     created_at: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->hash_id,
            'userid' => $this->v_userid,
            'username' => $this->v_username ?? $this->v_userid,
            'name' => $this->v_username ?? $this->v_userid,
            'email' => $this->v_email,
            'unit' => [
                'code' => $this->v_kolok,
                'name' => $this->v_kolok ?? 'Umum',
            ],
            'is_active' => (bool) $this->b_is_active,
            'is_external' => (bool) ($this->b_use_other || empty($this->v_password)),
            'roles' => $this->getRolesList(),
            'user_roles' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'permissions' => $this->getPermissionsList(),
            'is_root' => $this->when($this->isRoot(), true),
            'created_at' => $this->dt_created_at?->toIso8601String(),
        ];
    }
}
