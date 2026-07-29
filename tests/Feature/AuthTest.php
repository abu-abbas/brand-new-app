<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

it('logs in an active user and returns the current session user', function () {
    $user = User::factory()->create([
        'username' => 'pegawai',
        'password' => 'rahasia',
        'is_active' => true,
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
    ])
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);

    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.username', 'pegawai');
});

it('rejects invalid credentials without revealing account status', function () {
    User::factory()->create([
        'username' => 'nonaktif',
        'password' => 'rahasia',
        'is_active' => false,
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
        'username' => 'pegawai',
        'password' => 'rahasia',
        'is_active' => true,
    ]);

    $this->postJson('/api/auth/login', [
        'username' => 'pegawai',
        'password' => 'rahasia',
    ])->assertOk();

    $this->postJson('/api/auth/logout')
        ->assertNoContent();

    $this->assertGuest('web');
});
