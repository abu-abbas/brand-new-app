<?php

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['captcha.disable' => true]);
    $this->withHeader('Referer', config('app.url'));
});

it('validates the reset token before checking password history', function () {
    User::factory()->create([
        'v_email' => 'internal@example.test',
        'v_password' => Hash::make('OldPass1!'),
    ]);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'internal@example.test',
        'token' => 'invalid-token',
        'password' => 'OldPass1!',
        'password_confirmation' => 'OldPass1!',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AUTH-VAL-019');
});

it('resets an eligible internal account once and records its previous password', function () {
    $user = User::factory()->create([
        'v_email' => 'reset@example.test',
        'v_password' => Hash::make('OldPass1!'),
        'dt_last_updated_password' => now()->subMonth(),
    ]);
    $token = Password::broker()->createToken($user);

    $payload = [
        'email' => 'reset@example.test',
        'token' => $token,
        'password' => 'NewPass2@',
        'password_confirmation' => 'NewPass2@',
    ];

    $this->postJson('/api/auth/reset-password', $payload)->assertOk();

    expect(Hash::check('NewPass2@', $user->fresh()->v_password))->toBeTrue();
    $this->assertDatabaseCount('tr_user_password_histories', 1);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'reset@example.test']);

    $this->postJson('/api/auth/reset-password', $payload)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AUTH-VAL-019');
});

it('rejects reset tokens after an account becomes external or inactive', function (array $state) {
    $user = User::factory()->create([
        'v_email' => 'ineligible@example.test',
        'b_use_other' => false,
        'b_is_active' => true,
    ]);
    $token = Password::broker()->createToken($user);
    $user->update($state);

    $this->postJson('/api/auth/reset-password', [
        'email' => 'ineligible@example.test',
        'token' => $token,
        'password' => 'NewPass2@',
        'password_confirmation' => 'NewPass2@',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AUTH-VAL-019');
})->with([
    'external account' => [['b_use_other' => true]],
    'inactive account' => [['b_is_active' => false]],
]);

it('does not accept a local hash for an externally authenticated account', function () {
    Schema::dropIfExists('vw_users');
    Schema::create('vw_users', function ($table) {
        $table->string('v_userid', 100)->primary();
        $table->string('v_username', 255);
        $table->string('v_password', 255);
    });

    DB::table('vw_users')->insert([
        'v_userid' => 'external-user',
        'v_username' => 'external-user',
        'v_password' => md5('ExternalPass1!'),
    ]);
    DB::table('tm_roles')->insert([
        'v_code' => 'EXTERNAL',
        'v_name' => 'External',
        'i_level' => 1,
    ]);
    $user = User::factory()->create([
        'v_userid' => 'external-user',
        'v_username' => 'external-user',
        'v_password' => Hash::make('LocalPass1!'),
        'b_use_other' => true,
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'EXTERNAL',
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'external-user',
        'password' => 'LocalPass1!',
    ])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'AUTH-LOGIN-001');
});

it('blocks expired sessions and still allows the password change endpoint', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'PASSWORD_USER',
        'v_name' => 'Password User',
        'i_level' => 1,
    ]);
    $user = User::factory()->create([
        'v_password' => Hash::make('OldPass1!'),
        'dt_last_updated_password' => now()->subMonths(4),
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'PASSWORD_USER',
    ]);

    $this->actingAs($user)
        ->getJson('/api/features')
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH-VAL-017');

    $this->actingAs($user)
        ->putJson('/api/auth/password', [
            'current_password' => 'OldPass1!',
            'password' => 'NewPass2@',
            'password_confirmation' => 'NewPass2@',
        ])
        ->assertOk();
});

it('reports password shape and password reuse as different errors', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'PASSWORD_USER',
        'v_name' => 'Password User',
        'i_level' => 1,
    ]);
    $user = User::factory()->create([
        'v_password' => Hash::make('OldPass1!'),
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'PASSWORD_USER',
    ]);

    $this->actingAs($user)
        ->putJson('/api/auth/password', [
            'current_password' => 'OldPass1!',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.password.0.code', 'AUTH-VAL-021');

    $this->actingAs($user)
        ->putJson('/api/auth/password', [
            'current_password' => 'OldPass1!',
            'password' => 'OldPass1!',
            'password_confirmation' => 'OldPass1!',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'AUTH-VAL-018');
});
