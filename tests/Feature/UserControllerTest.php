<?php

use App\Constants\RoleConstant;
use App\Models\User;
use App\Models\UserRole;
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

it('fetches reference data for wilayah and perangkat daerah', function () {
    $this->getJson('/api/references/wilayah')
        ->assertOk()
        ->assertJsonCount(8, 'data');

    $this->getJson('/api/references/perangkat-daerah')
        ->assertOk()
        ->assertJsonCount(14, 'data');
});

it('returns names for every assigned unit through the reference contract', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'ADMIN_OPD',
        'v_name' => 'Admin OPD',
        'i_level' => 10,
        'b_need_unit' => true,
        'v_created_by' => 'system',
    ]);
    $user = User::factory()->create();
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'ADMIN_OPD',
        'v_unit' => '000003890',
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'ADMIN_OPD',
        'v_unit' => '000003891',
    ]);

    $this->actingAs($user)
        ->getJson('/api/references/perangkat-daerah')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.name', 'Dinas Komunikasi, Informatika dan Statistik')
        ->assertJsonPath('data.1.name', 'Unit Pengelola Layanan Pengadaan Secara Elektronik');
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
        'v_code' => 'ADMIN_STAFF',
        'v_alias' => 'ubah-pengguna',
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

it('ignores expired assignments when checking organization scope', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'MANAGER',
        'v_name' => 'Manager',
        'i_level' => 50,
        'v_created_by' => 'system',
    ]);
    DB::table('tm_features')->insert([
        'v_alias' => 'manajemen-pengguna',
        'v_name' => 'Manajemen Pengguna',
        'e_type' => 'menu',
    ]);
    DB::table('tr_role_features')->insert([
        'v_code' => 'MANAGER',
        'v_alias' => 'manajemen-pengguna',
    ]);
    $manager = User::factory()->create();
    UserRole::create([
        'v_userid' => $manager->v_userid,
        'v_role_code' => 'MANAGER',
        'v_unit' => 'ACTIVE_UNIT',
    ]);
    UserRole::create([
        'v_userid' => $manager->v_userid,
        'v_role_code' => 'MANAGER',
        'v_unit' => 'EXPIRED_UNIT',
        'dt_valid_until' => today()->subDay(),
    ]);
    $target = User::factory()->create(['v_kolok' => 'EXPIRED_UNIT']);

    expect($manager->can('view', $target))->toBeFalse();
});

it('prevents user from deactivating self', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'ADMIN_STAFF',
        'v_name' => 'Staff Admin',
        'i_level' => 50,
        'v_created_by' => 'system',
    ]);
    DB::table('tm_features')->insert([
        'v_alias' => 'ubah-pengguna',
        'v_name' => 'Ubah Pengguna',
        'e_type' => 'action',
    ]);
    $user = User::factory()->create(['b_is_active' => true]);
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ADMIN_STAFF']);
    DB::table('tr_role_features')->insert([
        'v_code' => 'ADMIN_STAFF',
        'v_alias' => 'ubah-pengguna',
    ]);
    $this->actingAs($user);

    $response = $this->patchJson("/api/users/{$user->hash_id}/toggle-status");
    $response->assertStatus(422);

    expect($user->fresh()->b_is_active)->toBeTrue();
});

it('prevents user from deleting self', function () {
    DB::table('tm_roles')->insert([
        'v_code' => 'ADMIN_STAFF',
        'v_name' => 'Staff Admin',
        'i_level' => 50,
        'v_created_by' => 'system',
    ]);
    DB::table('tm_features')->insert([
        'v_alias' => 'hapus-pengguna',
        'v_name' => 'Hapus Pengguna',
        'e_type' => 'action',
    ]);
    $user = User::factory()->create();
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ADMIN_STAFF']);
    DB::table('tr_role_features')->insert([
        'v_code' => 'ADMIN_STAFF',
        'v_alias' => 'hapus-pengguna',
    ]);
    $this->actingAs($user);

    $response = $this->deleteJson("/api/users/{$user->hash_id}");
    $response->assertStatus(403);

    expect($user->fresh()->dt_deleted_at)->toBeNull();
});
