<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\UserRole;
use App\Services\ReferenceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

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
     *     active_group_id: string|null,
     *     default_group_id: string|null,
     *     has_multiple_groups: bool,
     *     permissions: array<string>,
     *     is_root?: bool,
     *     must_change_password: bool,
     *     password_expires_at: string|null,
     *     is_verified: bool,
     *     created_at: string|null,
     *     is_impersonating?: bool,
     *     impersonator?: array{userid: string, name: string},
     *     impersonated_active_group?: string|null,
     *     impersonated_active_group_name?: string|null,
     *     impersonated_active_group_unit_name?: string|null,
     *     impersonate_expires_at?: string|null
     * }
     */
    public function toArray(Request $request): array
    {
        $name = toTitleCase($this->v_username ?? $this->v_userid);

        $isImpersonating = session()->has('impersonator_id');
        /** @phpstan-ignore-next-line nullsafe.neverNull */
        $routeName = $request->route()?->getName();
        $isAuthOrImpersonateEndpoint = is_string($routeName) && (
            str_starts_with($routeName, 'api.auth.') || str_starts_with($routeName, 'api.impersonate.')
        );

        $impersonatorId = null;
        $impersonatorName = null;
        $impersonatedGroupCode = null;
        $impersonatedGroupName = null;
        $unitName = null;
        $expiresAtIso = null;

        if ($isImpersonating) {
            $impersonatorId = (string) session('impersonator_id');
            $impersonatorUser = User::query()->where('v_userid', $impersonatorId)->first();
            $impersonatorName = $impersonatorUser !== null ? toTitleCase($impersonatorUser->v_username) : $impersonatorId;
            $startedAtIso = (string) session('impersonate_started_at', '');
            $expiresAtIso = $startedAtIso !== ''
                ? Carbon::parse($startedAtIso)->addMinutes(60)->toIso8601String()
                : null;

            $impersonatedGroupCode = (string) session('impersonated_active_group');
            $impersonatedUserRole = UserRole::query()->where('v_userid', $this->v_userid)
                ->where('v_role_code', $impersonatedGroupCode)
                ->first();
            $impersonatedRoleModel = $impersonatedUserRole?->roleModel;
            $impersonatedGroupName = $impersonatedRoleModel !== null
                ? toTitleCase($impersonatedRoleModel->v_name)
                : $impersonatedGroupCode;

            if ($impersonatedUserRole?->v_unit) {
                $options = app(ReferenceService::class)->getPerangkatDaerahOptions(null);
                $found = Arr::first($options, fn ($u) => $u['code'] === $impersonatedUserRole->v_unit);
                $unitName = $found ? toTitleCase($found['name']) : $impersonatedUserRole->v_unit;
            }
        }

        return [
            'id' => $this->hash_id,
            'userid' => $this->v_userid,
            'username' => $name,
            'name' => $name,
            'email' => $this->v_email,
            'unit' => [
                'code' => $this->v_kolok,
                'name' => $this->v_kolok ?? 'Umum',
            ],
            'is_active' => (bool) $this->b_is_active,
            'is_external' => (bool) ($this->b_use_other || empty($this->v_password)),
            'roles' => $this->getRolesList(),
            'user_roles' => UserRoleResource::collection($this->whenLoaded('userRoles')),
            'active_group_id' => $this->getActiveGroupId(),
            'default_group_id' => $this->v_default_group_id,
            'has_multiple_groups' => $this->hasMultipleGroups(),
            'permissions' => $this->getPermissionsList(),
            'is_root' => $this->when($this->isRoot(), true),
            'must_change_password' => $this->mustChangePassword(),
            'password_expires_at' => $this->passwordExpiresAt()?->toIso8601String(),
            'is_verified' => $this->dt_email_verified_at !== null,
            'created_at' => $this->dt_created_at?->toIso8601String(),
            'is_impersonating' => $isImpersonating,
            'impersonator' => $this->when($isImpersonating, [
                'userid' => $impersonatorId ?? '',
                'name' => $impersonatorName ?? '',
            ]),
            'impersonated_active_group' => $this->when($isImpersonating, $impersonatedGroupCode),
            'impersonated_active_group_name' => $this->when($isImpersonating, $impersonatedGroupName),
            'impersonated_active_group_unit_name' => $this->when($isImpersonating, $unitName),
            'impersonate_expires_at' => $this->when($isImpersonating, $expiresAtIso),
        ];
    }
}
