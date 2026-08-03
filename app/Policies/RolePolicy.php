<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function view(User $user, Role $role): bool
    {
        return $this->hasPermission($user, 'manajemen-group') && $this->canManage($user, $role);
    }

    public function update(User $user, Role $role): bool
    {
        return $this->hasPermission($user, 'ubah-group') && $this->canManage($user, $role);
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->hasPermission($user, 'hapus-group') && $this->canManage($user, $role);
    }

    public function restore(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }

    private function hasPermission(User $user, string $permission): bool
    {
        return $user->isRoot() || in_array($permission, $user->getPermissionsList(), true);
    }

    private function canManage(User $user, Role $role): bool
    {
        if ($user->isRoot()) {
            return true;
        }

        $activeGroup = $user->getActiveGroupId();

        return $activeGroup !== null
            && (int) $role->i_level < $user->role_level
            && str_starts_with(strtoupper($role->v_code), strtoupper($activeGroup).'_');
    }
}
