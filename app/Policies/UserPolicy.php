<?php

namespace App\Policies;

use App\Models\User;
use App\Traits\HasScopeCheck;

class UserPolicy
{
    use HasScopeCheck;

    public function viewAny(User $user): bool
    {
        return $user->isRoot() || in_array('manajemen-pengguna', $user->getPermissionsList(), true);
    }

    public function view(User $user, User $model): bool
    {
        if (! $user->isRoot() && ! in_array('manajemen-pengguna', $user->getPermissionsList(), true)) {
            return false;
        }

        if ($user->v_userid === $model->v_userid) {
            return true;
        }

        return $this->canAccessScope($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isRoot() || in_array('tambah-pengguna', $user->getPermissionsList(), true);
    }

    public function update(User $user, User $model): bool
    {
        if (! $user->isRoot() && ! in_array('ubah-pengguna', $user->getPermissionsList(), true)) {
            return false;
        }

        if ($user->v_userid === $model->v_userid) {
            return true;
        }

        return $this->canAccessScope($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->v_userid === $model->v_userid) {
            return false;
        }

        if ($model->b_use_other || empty($model->v_password)) {
            return false;
        }

        if (! $user->isRoot() && ! in_array('hapus-pengguna', $user->getPermissionsList(), true)) {
            return false;
        }

        return $this->canAccessScope($user, $model);
    }
}
