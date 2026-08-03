<?php

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['captcha.disable' => true]);
    $this->withHeader('Referer', config('app.url'));
});

it('serves the flat captcha and rejects an invalid answer', function () {
    config(['captcha.disable' => false]);

    $challenge = $this->getJson('/api/auth/captcha')
        ->assertOk()
        ->assertJsonStructure(['img', 'key'])
        ->json();

    expect($challenge['img'])->toStartWith('data:image/jpeg;base64,');

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
        'captcha_key' => $challenge['key'],
        'captcha' => 'salah',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.captcha.0.code', 'AUTH-VAL-010');
});

it('returns audio wav for a valid captcha key', function () {
    config(['captcha.disable' => false]);

    $challenge = $this->getJson('/api/auth/captcha')
        ->assertOk()
        ->assertJsonStructure(['img', 'key'])
        ->json();

    $response = $this->get('/api/auth/captcha/audio?key='.$challenge['key']);
    $response->assertOk()
        ->assertHeader('Content-Type', 'audio/wav');

    expect(strlen((string) $response->getContent()))->toBeGreaterThan(44);
});

it('logs in an active user with role and returns the current session user', function () {
    $user = User::factory()->create([
        'v_userid' => 'pegawai',
        'v_password' => Hash::make('rahasia'),
        'b_is_active' => true,
    ]);

    UserRole::create([
        'v_userid' => 'pegawai',
        'v_role_code' => 'ADMIN',
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $user->hash_id);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.userid', 'pegawai');
});

it('rejects login when user has no role assigned', function () {
    User::factory()->create([
        'v_userid' => 'noroleuser',
        'v_password' => Hash::make('rahasia'),
        'b_is_active' => true,
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'noroleuser',
        'password' => 'rahasia',
    ])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH-LOGIN-003')
        ->assertJsonPath('message', 'Anda belum memiliki group, silakan hubungi admin untuk informasi lebih lanjut.');
});

it('provisions user from vw_users on login but blocks if no role assigned', function () {
    Schema::create('vw_users', function ($table) {
        $table->string('v_userid', 100)->primary();
        $table->string('v_username', 255);
        $table->string('v_password', 255);
        $table->string('v_klogad', 15)->nullable();
        $table->string('v_kolok', 15)->nullable();
        $table->string('v_kojab', 10)->nullable();
        $table->string('v_koper', 10)->nullable();
        $table->string('v_kopang', 10)->nullable();
        $table->string('v_eselon', 4)->nullable();
        $table->string('v_spmu', 10)->nullable();
        $table->string('v_kd', 4)->nullable();
    });

    DB::table('vw_users')->insert([
        'v_userid' => 'external_user',
        'v_username' => 'EXTERNAL USER',
        'v_password' => md5('password123'),
    ]);

    // Login attempt triggers JIT provisioning to tm_users, but fails role check
    $this->postJson('/api/auth/login', [
        'username' => 'external_user',
        'password' => 'password123',
    ])
        ->assertForbidden()
        ->assertJsonPath('code', 'AUTH-LOGIN-003');

    // Confirm user was provisioned in tm_users
    $this->assertDatabaseHas('tm_users', [
        'v_userid' => 'external_user',
        'b_use_other' => true,
    ]);

    // Now assign role to user and attempt login again
    UserRole::create([
        'v_userid' => 'external_user',
        'v_role_code' => 'STAFF',
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'external_user',
        'password' => 'password123',
    ])->assertOk();
});

it('rejects invalid credentials without revealing account status', function () {
    User::factory()->create([
        'v_userid' => 'nonaktif',
        'v_password' => Hash::make('rahasia'),
        'b_is_active' => false,
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'nonaktif',
        'password' => 'rahasia',
    ])
        ->assertUnauthorized()
        ->assertJson([
            'code' => 'AUTH-LOGIN-001',
            'message' => 'Username atau password tidak valid.',
            'retryable' => false,
        ]);
});

it('rate limits repeated credential failures without counting captcha validation', function () {
    config(['captcha.disable' => false]);

    User::factory()->create([
        'v_userid' => 'dibatasi',
        'v_password' => Hash::make('benar'),
        'b_is_active' => true,
    ]);

    foreach (range(1, 6) as $_) {
        $this->postJson('/api/auth/login', [
            'username' => 'dibatasi',
            'password' => 'salah',
        ])->assertUnprocessable();
    }

    config(['captcha.disable' => true]);

    $payload = [
        'username' => 'dibatasi',
        'password' => 'salah',
    ];

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/auth/login', $payload)->assertUnauthorized();
    }

    $this->postJson('/api/auth/login', $payload)
        ->assertTooManyRequests()
        ->assertJsonPath('code', 'AUTH-LOGIN-002')
        ->assertJsonPath('message', 'Terlalu banyak percobaan login. Coba lagi dalam satu menit.');
});

it('requires a captcha and protects authenticated endpoints', function () {
    config(['captcha.disable' => false]);

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.captcha.0.code', 'AUTH-VAL-007');

    $this->getJson('/api/features')->assertUnauthorized();
});

it('logs out and invalidates the session', function () {
    User::factory()->create([
        'v_userid' => 'pegawai',
        'v_password' => Hash::make('rahasia'),
        'b_is_active' => true,
    ]);

    UserRole::create([
        'v_userid' => 'pegawai',
        'v_role_code' => 'ADMIN',
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
    ])->assertOk();

    $this->postJson('/api/auth/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});
