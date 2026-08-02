<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $csvFile = storage_path('app/tm_features.csv');
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
            if (! $data || empty($data['v_alias'])) {
                continue;
            }

            Feature::updateOrCreate(
                ['v_alias' => $data['v_alias']],
                [
                    'v_name' => $data['v_name'],
                    'e_type' => $data['e_type'] ?: 'menu',
                    'v_parent' => $data['v_parent'] ?: null,
                    'v_desc' => $data['v_desc'] ?: null,
                    'v_route' => $data['v_route'] ?: null,
                    'v_icon' => $data['v_icon'] ?: null,
                    'si_order' => (int) ($data['si_order'] ?: 0),
                    'b_show_on_sidebar' => ((string) $data['b_show_on_sidebar']) === '1',
                    'v_created_by' => $data['v_created_by'] ?: 'sysadmin',
                    'dt_created_at' => $data['dt_created_at'] ?: now(),
                    'v_updated_by' => $data['v_updated_by'] ?: null,
                    'dt_updated_at' => $data['dt_updated_at'] ?: null,
                ]
            );
        }

        fclose($handle);
    }
}
