<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $userId = Str::lower(fake()->unique()->userName());

        return [
            'v_userid' => $userId,
            'v_username' => fake()->name(),
            'v_email' => fake()->unique()->safeEmail(),
            'v_password' => static::$password ??= Hash::make('password'),
            'b_is_active' => true,
            'b_use_other' => false,
            'v_remember_token' => Str::random(10),
            'dt_created_at' => now(),
        ];
    }
}
