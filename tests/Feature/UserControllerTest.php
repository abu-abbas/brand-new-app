<?php

use App\Constants\RoleConstant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Referer', config('app.url'));

    // Roles seed
    DB::table('tm_roles')->insert([
        'v_code' => 'ADM_SYS',
        'v_name' => 'System Administrator',
        'i_level' => RoleConstant::ROOT_LEVEL,
        'v_created_by' => 'system',
    ]);

    $admin = User::factory()->create(['v_userid' => 'root']);
    DB::table('tr_user_roles')->insert([
        'v_userid' => $admin->v_userid,
        'v_role_code' => 'ADM_SYS',
        'v_created_by' => 'system',
    ]);

    $this->actingAs($admin);
});

it('lists paginated users', function () {
    User::factory()->count(3)->create();

    $this->getJson('/api/users')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'userid',
                    'username',
                    'email',
                    'is_active',
                    'roles',
                ],
            ],
            'meta' => ['total', 'current_page', 'last_page'],
        ]);
});

it('creates a new user with roles and scope', function () {
    $response = $this->postJson('/api/users', [
        'v_userid' => '199901012026011001',
        'v_username' => 'Pengguna Baru',
        'v_email' => 'pengguna.baru@jakarta.go.id',
        'v_password' => 'secret123',
        'b_is_active' => true,
        'b_use_other' => false,
        'roles' => [
            [
                'v_role_code' => 'ADM_SYS',
                'v_wilayah' => '10',
                'v_unit' => '000003890',
            ],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.userid', '199901012026011001')
        ->assertJsonPath('data.username', 'Pengguna Baru');

    $this->assertDatabaseHas('tm_users', [
        'v_userid' => '199901012026011001',
        'v_username' => 'Pengguna Baru',
    ]);

    $this->assertDatabaseHas('tr_user_roles', [
        'v_userid' => '199901012026011001',
        'v_role_code' => 'ADM_SYS',
        'v_wilayah' => '10',
        'v_unit' => '000003890',
    ]);
});

it('updates an existing user profile and roles', function () {
    $user = User::factory()->create([
        'v_username' => 'Nama Lama',
    ]);

    $response = $this->putJson("/api/users/{$user->hash_id}", [
        'v_username' => 'Nama Diubah',
        'v_email' => 'diubah@jakarta.go.id',
        'b_is_active' => true,
        'roles' => [
            [
                'v_role_code' => 'ADM_SYS',
                'v_wilayah' => '20',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.username', 'Nama Diubah');

    $this->assertDatabaseHas('tm_users', [
        'i_id' => $user->i_id,
        'v_username' => 'Nama Diubah',
    ]);
});

it('toggles user active status', function () {
    $user = User::factory()->create([
        'b_is_active' => true,
    ]);

    $this->patchJson("/api/users/{$user->hash_id}/toggle-status")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    expect($user->fresh()->b_is_active)->toBeFalse();
});

it('soft deletes a user', function () {
    $user = User::factory()->create();

    $this->deleteJson("/api/users/{$user->hash_id}")
        ->assertOk();

    expect($user->fresh()->dt_deleted_at)->not->toBeNull();
});

it('fetches reference mock data for wilayah and perangkat daerah', function () {
    $this->getJson('/api/references/wilayah')
        ->assertOk()
        ->assertJsonCount(8, 'data');

    $this->getJson('/api/references/perangkat-daerah')
        ->assertOk()
        ->assertJsonCount(14, 'data');
});

it('prevents non-root user from assigning role with level equal or higher than self', function () {
    // Role level 50 untuk user biasa
    DB::table('tm_roles')->insert([
        'v_code' => 'ADMIN_STAFF',
        'v_name' => 'Staff Admin',
        'i_level' => 50,
        'v_created_by' => 'system',
    ]);

    // Role level 90 (lebih tinggi)
    DB::table('tm_roles')->insert([
        'v_code' => 'CHIEF_ADMIN',
        'v_name' => 'Chief Admin',
        'i_level' => 90,
        'v_created_by' => 'system',
    ]);

    $staffUser = User::factory()->create(['v_userid' => 'staff1']);
    DB::table('tr_user_roles')->insert([
        'v_userid' => $staffUser->v_userid,
        'v_role_code' => 'ADMIN_STAFF',
        'v_created_by' => 'system',
    ]);

    // Berikan permission ubah-pengguna ke staffUser
    DB::table('tm_features')->insert([
        'v_alias' => 'ubah-pengguna',
        'v_name' => 'Ubah Pengguna',
        'e_type' => 'action',
        'si_order' => 1,
        'b_show_on_sidebar' => false,
    ]);

    DB::table('tr_role_features')->insert([
        'v_role_code' => 'ADMIN_STAFF',
        'v_feature_alias' => 'ubah-pengguna',
    ]);

    $this->actingAs($staffUser);

    $targetUser = User::factory()->create(['v_userid' => 'target1']);

    $response = $this->putJson("/api/users/{$targetUser->hash_id}", [
        'v_username' => 'Target Changed',
        'roles' => [
            ['v_role_code' => 'CHIEF_ADMIN'],
        ],
    ]);

    $response->assertStatus(403);
});

it('prevents user from deactivating self', function () {
    $user = User::factory()->create(['b_is_active' => true]);
    $this->actingAs($user);

    $response = $this->patchJson("/api/users/{$user->hash_id}/toggle-status");
    $response->assertStatus(422);

    expect($user->fresh()->b_is_active)->toBeTrue();
});

it('prevents user from deleting self', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->deleteJson("/api/users/{$user->hash_id}");
    $response->assertStatus(422);

    expect($user->fresh()->dt_deleted_at)->toBeNull();
});

