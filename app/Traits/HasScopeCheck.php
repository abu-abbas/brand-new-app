<?php

namespace App\Traits;

use App\Contracts\ScopedResource;
use App\Models\User;

trait HasScopeCheck
{
    /**
     * Memeriksa apakah actor berhak mengelola/mengakses resource berdasarkan level, unit, dan wilayah.
     *
     * @param  ScopedResource|object  $resource
     */
    public function canAccessScope(User $actor, object $resource): bool
    {
        if ($actor->isRoot()) {
            return true;
        }

        // 1. Pengecekan Level (jika resource memiliki level, misal User)
        $resourceLevel = $resource instanceof ScopedResource
            ? $resource->getResourceLevel()
            : ($resource->role_level ?? null);

        if ($resourceLevel !== null && (int) $resourceLevel >= $actor->role_level) {
            return false;
        }

        $activeGroupId = $actor->getActiveGroupId();
        $relevantUserRoles = $actor->userRoles;
        if ($activeGroupId) {
            $relevantUserRoles = $relevantUserRoles->where('v_role_code', $activeGroupId);
        }

        // 2. Pengecekan Unit Spesifik
        $resourceUnit = $resource instanceof ScopedResource
            ? $resource->getResourceUnit()
            : ($resource->v_kolok ?? null);

        if (! empty($resourceUnit)) {
            $actorUnits = $relevantUserRoles
                ->pluck('v_unit')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($actorUnits) && ! in_array($resourceUnit, $actorUnits, true)) {
                return false;
            }
        }

        // 3. Pengecekan Wilayah (2 Digit Prefix Kode Wilayah)
        $resourceWilayah = $resource instanceof ScopedResource
            ? $resource->getResourceWilayah()
            : ($resource->v_kolok ?? null);

        if (! empty($resourceWilayah)) {
            $actorWilayahs = $relevantUserRoles
                ->pluck('v_wilayah')
                ->filter()
                ->map(fn ($w) => substr((string) $w, 0, 2))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            if (! empty($actorWilayahs)) {
                $targetWilayah = substr((string) $resourceWilayah, 0, 2);
                if (! in_array($targetWilayah, $actorWilayahs, true)) {
                    return false;
                }
            }
        }

        return true;
    }
}
