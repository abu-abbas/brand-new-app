<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    private function canManageTargetUser(User $actor, User $target): bool
    {
        if ($actor->isRoot()) {
            return true;
        }

        // Tidak boleh mengelola user yang levelnya >= level actor
        if ($target->role_level >= $actor->role_level) {
            return false;
        }

        // Check unit spesifik jika actor terikat pada v_unit tertentu
        if (! empty($target->v_kolok)) {
            $actorUnits = $actor->userRoles
                ->pluck('v_unit')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($actorUnits)) {
                if (! in_array($target->v_kolok, $actorUnits, true)) {
                    return false;
                }
            }

            // Wilayah check jika actor terikat pada v_wilayah tertentu
            $actorWilayahs = $actor->userRoles
                ->pluck('v_wilayah')
                ->filter()
                ->map(fn ($w) => substr((string) $w, 0, 2))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($actorWilayahs)) {
                $targetWilayah = substr((string) $target->v_kolok, 0, 2);
                if (! in_array($targetWilayah, $actorWilayahs, true)) {
                    return false;
                }
            }
        }


        return true;
    }

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

        return $this->canManageTargetUser($user, $model);
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

        return $this->canManageTargetUser($user, $model);
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

        return $this->canManageTargetUser($user, $model);
    }
}

