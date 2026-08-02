<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Import dummy vw_users dari storage/app/vw_users.sql jika belum ada
        $this->call(VwUsersSeeder::class);

        // 2. Import master fitur dari storage/app/tm_features.csv
        $this->call(FeatureSeeder::class);

        // 3. Import master role dari storage/app/tm_roles.csv
        $this->call(RoleSeeder::class);

        // 4. User 1: sysadmin (ROOT Admin - Akses ke Semua Fitur)
        User::updateOrCreate(
            ['v_userid' => 'sysadmin'],
            [
                'v_username' => 'System Administrator',
                'v_email' => 'sysadmin@domain.local',
                'v_password' => Hash::make('x'),
                'b_is_active' => true,
                'b_use_other' => false,
                'dt_created_at' => now(),
            ]
        );

        UserRole::firstOrCreate(
            [
                'v_userid' => 'sysadmin',
                'v_role_code' => 'ROOT',
            ],
            [
                'dt_valid_from' => now()->subDay()->format('Y-m-d'),
                'dt_created_at' => now(),
            ]
        );

        // 5. User 2: userhome (Hanya bisa akses fitur Beranda / Home)
        $homeRole = Role::updateOrCreate(
            ['v_code' => 'USER_HOME'],
            [
                'v_name' => 'User Home Only',
                'i_level' => 10,
                'b_need_region' => false,
                'b_need_unit' => false,
                'b_locked' => false,
                'v_created_by' => 'sysadmin',
                'dt_created_at' => now(),
            ]
        );
        $homeRole->features()->sync(['beranda']);

        User::updateOrCreate(
            ['v_userid' => 'userhome'],
            [
                'v_username' => 'User Akses Home Only',
                'v_email' => 'userhome@domain.local',
                'v_password' => Hash::make('x'),
                'b_is_active' => true,
                'b_use_other' => false,
                'dt_created_at' => now(),
            ]
        );

        UserRole::firstOrCreate(
            [
                'v_userid' => 'userhome',
                'v_role_code' => 'USER_HOME',
            ],
            [
                'dt_valid_from' => now()->subDay()->format('Y-m-d'),
                'dt_created_at' => now(),
            ]
        );

        // 6. User 3: noroleuser (User yang belum memiliki roles sama sekali)
        User::updateOrCreate(
            ['v_userid' => 'noroleuser'],
            [
                'v_username' => 'User Tanpa Role',
                'v_email' => 'noroleuser@domain.local',
                'v_password' => Hash::make('x'),
                'b_is_active' => true,
                'b_use_other' => false,
                'dt_created_at' => now(),
            ]
        );
    }
}
