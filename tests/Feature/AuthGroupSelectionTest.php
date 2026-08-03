<?php

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user dengan 1 role otomatis mendapatkan active group di session saat login', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);

    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'ROLE_OPERATOR',
    ]);

    $response = $this->withSession([])->postJson('/api/auth/login', [
        'username' => $user->v_userid,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.active_group_id', 'ROLE_OPERATOR')
        ->assertJsonPath('data.has_multiple_groups', false);
});

test('user dengan multiple roles dan tanpa default group belum memiliki active group di session setelah login pertama', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);

    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ROLE_ADMIN']);
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ROLE_OPERATOR']);

    $response = $this->withSession([])->postJson('/api/auth/login', [
        'username' => $user->v_userid,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.active_group_id', null)
        ->assertJsonPath('data.has_multiple_groups', true);
});

test('user dapat memilih active group via POST /api/auth/active-group', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);

    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ROLE_ADMIN']);
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ROLE_OPERATOR']);

    $this->actingAs($user, 'web');

    $response = $this->withSession([])->postJson('/api/auth/active-group', [
        'group_id' => 'ROLE_ADMIN',
        'remember' => true,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.active_group_id', 'ROLE_ADMIN')
        ->assertJsonPath('data.default_group_id', 'ROLE_ADMIN');

    $this->assertDatabaseHas('tm_users', [
        'v_userid' => $user->v_userid,
        'v_default_group_id' => 'ROLE_ADMIN',
    ]);
});

test('gagal memilih group yang tidak dimiliki user', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);

    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ROLE_OPERATOR']);

    $this->actingAs($user, 'web');

    $response = $this->withSession([])->postJson('/api/auth/active-group', [
        'group_id' => 'ROLE_SUPER_ADMIN',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'AUTH-ACC-002');
});
