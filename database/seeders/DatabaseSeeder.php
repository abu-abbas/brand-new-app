<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Afria Administrator',
            'username' => 'afria.admin',
            'email' => 'afria@example.com',
            'unit_name' => 'Teknologi Informasi',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        User::factory(50)->create();
    }
}
