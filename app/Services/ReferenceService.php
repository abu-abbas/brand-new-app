<?php

namespace App\Services;

use App\Models\User;

class ReferenceService
{
    /**
     * @return array<int, array{code: string, name: string, order: int}>
     */
    public function getWilayahOptions(?User $user): array
    {
        $data = [
            ['code' => '10', 'name' => 'Jakarta Pusat', 'order' => 0],
            ['code' => '20', 'name' => 'Jakarta Utara', 'order' => 1],
            ['code' => '11', 'name' => 'Kepulauan Seribu', 'order' => 1],
            ['code' => '30', 'name' => 'Jakarta Barat', 'order' => 2],
            ['code' => '40', 'name' => 'Jakarta Selatan 1', 'order' => 3],
            ['code' => '41', 'name' => 'Jakarta Selatan 2', 'order' => 4],
            ['code' => '50', 'name' => 'Jakarta Timur 1', 'order' => 5],
            ['code' => '51', 'name' => 'Jakarta Timur 2', 'order' => 6],
        ];

        if ($user && ! $user->isRoot()) {
            $user->loadMissing(['userRoles.roleModel']);
            $userRoles = $user->userRoles;

            $regionRoles = $userRoles->filter(fn ($ur) => (bool) ($ur->roleModel?->b_need_region ?? false));

            if ($regionRoles->isNotEmpty()) {
                $wilayahList = [];
                foreach ($regionRoles as $ur) {
                    if (! empty($ur->v_wilayah)) {
                        $wArray = array_map('trim', explode(',', $ur->v_wilayah));
                        $wilayahList = array_merge($wilayahList, array_filter($wArray));
                    }
                }
                $wilayahList = array_unique($wilayahList);

                if (! empty($wilayahList)) {
                    $data = array_values(array_filter($data, fn ($w) => in_array($w['code'], $wilayahList, true)));
                }
            }
        }

        return collect($data)
            ->sortBy('order')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{code: string, name: string, spmu: string, sipkd_code: string}>
     */
    public function getPerangkatDaerahOptions(?User $user): array
    {
        $data = [
            ['code' => '000003890', 'name' => 'DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK', 'spmu' => 'C181', 'sipkd_code' => '21001000'],
            ['code' => '000003891', 'name' => 'UNIT PENGELOLA LAYANAN PENGADAAN SECARA ELEKTRONIK', 'spmu' => 'C181', 'sipkd_code' => '21001701'],
            ['code' => '000003892', 'name' => 'SEKRETARIAT KOMISI PENYIARAN DAN KOMISI INFORMASI PROVINSI', 'spmu' => 'C181', 'sipkd_code' => '11601702'],
            ['code' => '000003893', 'name' => 'UNIT PENGELOLA JAKARTA SMART CITY', 'spmu' => 'C181', 'sipkd_code' => '21001703'],
            ['code' => '000003894', 'name' => 'PUSAT PELAYANAN STATISTIK', 'spmu' => 'C181', 'sipkd_code' => '21001702'],
            ['code' => '000003895', 'name' => 'UNIT PENGELOLA STATISTIK', 'spmu' => 'C181', 'sipkd_code' => ''],
            ['code' => '000003896', 'name' => 'UNIT PENGELOLA LAYANAN TEKNOLOGI INFORMASI DAN KOMUNIKASI', 'spmu' => 'C181', 'sipkd_code' => ''],
            ['code' => '000003897', 'name' => 'UNIT PENGELOLA PERANGKAT DAN JARINGAN SISTEM ELEKTRONIK', 'spmu' => 'C181', 'sipkd_code' => '21001704'],
            ['code' => '100003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KOTA ADM. JAKARTA PUSAT', 'spmu' => 'C181', 'sipkd_code' => '21001101'],
            ['code' => '110003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KAB ADM. KEPULAUAN SERIBU', 'spmu' => 'C181', 'sipkd_code' => '21001601'],
            ['code' => '200003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KOTA ADM. JAKARTA UTARA', 'spmu' => 'C181', 'sipkd_code' => '21001201'],
            ['code' => '300003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KOTA ADM. JAKARTA BARAT', 'spmu' => 'C181', 'sipkd_code' => '21001301'],
            ['code' => '400003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KOTA ADM. JAKARTA SELATAN', 'spmu' => 'C181', 'sipkd_code' => '21001401'],
            ['code' => '500003890', 'name' => 'SUKU DINAS KOMUNIKASI, INFORMATIKA DAN STATISTIK KOTA ADM. JAKARTA TIMUR', 'spmu' => 'C181', 'sipkd_code' => '21001501'],
        ];

        if ($user && ! $user->isRoot()) {
            $user->loadMissing(['userRoles.roleModel']);
            $userRoles = $user->userRoles;

            $unitRoles = $userRoles->filter(fn ($ur) => (bool) ($ur->roleModel?->b_need_unit ?? false));
            $regionRoles = $userRoles->filter(fn ($ur) => (bool) ($ur->roleModel?->b_need_region ?? false));

            if ($unitRoles->isNotEmpty()) {
                $userUnitCode = $unitRoles->first(fn ($ur) => ! empty($ur->v_unit))?->v_unit ?? $user->v_kolok;
                if (! empty($userUnitCode)) {
                    $data = array_values(array_filter($data, fn ($pd) => $pd['code'] === $userUnitCode));
                }
            } elseif ($regionRoles->isNotEmpty()) {
                $wilayahList = [];
                foreach ($regionRoles as $ur) {
                    if (! empty($ur->v_wilayah)) {
                        $wArray = array_map('trim', explode(',', $ur->v_wilayah));
                        $wilayahList = array_merge($wilayahList, array_filter($wArray));
                    }
                }
                $wilayahList = array_unique($wilayahList);

                if (! empty($wilayahList)) {
                    $data = array_values(array_filter($data, function ($pd) use ($wilayahList) {
                        foreach ($wilayahList as $wCode) {
                            if (str_starts_with($pd['code'], $wCode)) {
                                return true;
                            }
                        }

                        return false;
                    }));
                }
            }
        }

        return $data;
    }
}
