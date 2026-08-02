<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class VwUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('vw_users')) {
            $sqlFile = storage_path('app/vw_users.sql');
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                if ($sql) {
                    DB::unprepared($sql);
                }
            }
        }
    }
}
