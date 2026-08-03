<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isRoot() || in_array('manajemen-pengguna', $user->getPermissionsList(), true);
    }

    public function view(User $user, User $model): bool
    {
        return $user->isRoot() || in_array('manajemen-pengguna', $user->getPermissionsList(), true);
    }

    public function create(User $user): bool
    {
        return $user->isRoot() || in_array('tambah-pengguna', $user->getPermissionsList(), true);
    }

    public function update(User $user, User $model): bool
    {
        return $user->isRoot() || in_array('ubah-pengguna', $user->getPermissionsList(), true);
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->b_use_other || empty($model->v_password)) {
            return false;
        }

        return $user->isRoot() || in_array('hapus-pengguna', $user->getPermissionsList(), true);
    }
}
