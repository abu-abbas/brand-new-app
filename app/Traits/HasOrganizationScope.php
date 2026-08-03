<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait HasOrganizationScope
{
    /**
     * Scope query untuk memfilter data berdasarkan level, unit, dan wilayah user login.
     *
     * @param  Builder<static>  $query
     * @param  string  $unitColumn  Nama kolom unit/kolok pada tabel (default: 'v_kolok')
     * @return Builder<static>
     */
    public function scopeForUserScope(Builder $query, ?User $user, string $unitColumn = 'v_kolok'): Builder
    {
        if (! $user) {
            return $query;
        }

        if ($user->isRoot()) {
            return $query;
        }

        // Filter 1: Sembunyikan data milik user dengan level >= level user saat ini (jika bertipe User model)
        if ($this instanceof User) {
            $userLevel = $user->role_level;
            $query->whereDoesntHave('currentUserRoles.roleModel', function ($q) use ($userLevel) {
                $q->where('i_level', '>=', $userLevel);
            });
        }

        $activeGroupId = $user->getActiveGroupId();
        $relevantUserRoles = $user->getCurrentUserRoles();
        if ($activeGroupId) {
            $relevantUserRoles = $relevantUserRoles->where('v_role_code', $activeGroupId);
        }

        // Filter 2: Unit Spesifik (jika user memiliki v_unit khusus)
        $userUnits = $relevantUserRoles
            ->pluck('v_unit')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (! empty($userUnits)) {
            $query->whereIn($unitColumn, $userUnits);
        }

        // Filter 3: Wilayah Scope (2 Digit Prefix Kode Wilayah)
        $userWilayahs = $relevantUserRoles
            ->pluck('v_wilayah')
            ->filter()
            ->map(fn ($w) => substr((string) $w, 0, 2))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (! empty($userWilayahs)) {
            $query->where(function (Builder $q) use ($userWilayahs, $unitColumn) {
                foreach ($userWilayahs as $index => $wCode) {
                    if ($index === 0) {
                        $q->where(DB::raw("SUBSTRING({$unitColumn}, 1, 2)"), '=', $wCode);
                    } else {
                        $q->orWhere(DB::raw("SUBSTRING({$unitColumn}, 1, 2)"), '=', $wCode);
                    }
                }
            });
        }

        return $query;
    }
}
