<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = storage_path('app/tm_roles.csv');
        if (! file_exists($csvFile)) {
            return;
        }

        $handle = fopen($csvFile, 'r');
        if ($handle === false) {
            return;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return;
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            if (! $data || empty($data['v_code'])) {
                continue;
            }

            $role = Role::updateOrCreate(
                ['v_code' => $data['v_code']],
                [
                    'v_name' => $data['v_name'],
                    'i_level' => (int) ($data['i_level'] ?: 0),
                    'b_need_region' => ((string) $data['b_need_region']) === '1',
                    'b_need_unit' => ((string) $data['b_need_unit']) === '1',
                    'v_active_periode' => $data['v_active_periode'] ?: null,
                    'b_locked' => ((string) $data['b_locked']) === '1',
                    'v_created_by' => $data['v_created_by'] ?: 'sysadmin',
                    'dt_created_at' => $data['dt_created_at'] ?: now(),
                    'v_updated_by' => $data['v_updated_by'] ?: null,
                    'dt_updated_at' => $data['dt_updated_at'] ?: null,
                ]
            );

            // Jika role ROOT, secara otomatis sinkronkan semua fitur yang ada
            if ($role->v_code === 'ROOT') {
                $allFeatureAliases = Feature::query()->pluck('v_alias')->toArray();
                $role->features()->sync($allFeatureAliases);
            }
        }

        fclose($handle);
    }
}
