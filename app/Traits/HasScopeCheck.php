<?php

namespace App\Traits;

use App\Contracts\ScopedResource;
use App\Models\User;

trait HasScopeCheck
{
    /**
     * Memeriksa apakah actor berhak mengelola/mengakses resource berdasarkan level, unit, dan wilayah.
     */
    public function canAccessScope(User $actor, ScopedResource $resource): bool
    {
        if ($actor->isRoot()) {
            return true;
        }

        // 1. Pengecekan Level (jika resource memiliki level, misal User)
        $resourceLevel = $resource->getResourceLevel();
        if ($resourceLevel !== null && $resourceLevel >= $actor->role_level) {
            return false;
        }

        // 2. Pengecekan Unit Spesifik
        $resourceUnit = $resource->getResourceUnit();
        if (! empty($resourceUnit)) {
            $actorUnits = $actor->userRoles
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
        $resourceWilayah = $resource->getResourceWilayah();
        if (! empty($resourceWilayah)) {
            $actorWilayahs = $actor->userRoles
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
