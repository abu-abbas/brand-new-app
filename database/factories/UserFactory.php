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
    $units = ['Keuangan', 'Teknologi Informasi', 'Sumber Daya Manusia', 'Operasional', 'Pemasaran', 'Hukum & Kepatuhan'];
    $roles = ['Admin', 'Manager', 'Supervisor', 'Staff'];

    $name = fake()->name();
    $username = Str::slug(fake()->unique()->userName(), '');

    return [
      'name' => $name,
      'username' => $username,
      'email' => fake()->unique()->safeEmail(),
      'unit_name' => fake()->randomElement($units),
      'role' => fake()->randomElement($roles),
      'is_active' => fake()->boolean(80),
      'email_verified_at' => now(),
      'password' => static::$password ??= Hash::make('password'),
      'remember_token' => Str::random(10),
    ];
  }

  public function unverified(): static
  {
    return $this->state(fn(array $attributes) => [
      'email_verified_at' => null,
    ]);
  }
}
