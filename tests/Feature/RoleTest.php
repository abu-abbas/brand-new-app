<?php

use App\Constants\RoleConstant;
use App\Enums\PermissionType;
use App\Models\Feature;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Referer', config('app.url'));
    DB::table('tm_roles')->insert([
        'v_code' => 'ADM_SYS',
        'v_name' => 'ZZZ Root',
        'i_level' => RoleConstant::ROOT_LEVEL,
        'v_created_by' => 'system',
    ]);
    $admin = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $admin->v_userid,
        'v_role_code' => 'ADM_SYS',
        'v_created_by' => 'system',
    ]);
    $this->actingAs($admin);
});

it('lists active and soft-deleted roles when requested', function () {
    Role::query()->create([
        'v_code' => 'admin',
        'v_name' => 'Administrator',
        'b_need_region' => false,
        'b_need_unit' => false,
        'b_locked' => true,
    ]);

    $deleted = Role::query()->create([
        'v_code' => 'staff',
        'v_name' => 'Staff',
        'b_need_region' => true,
        'b_need_unit' => true,
    ]);
    $deleted->delete();

    $this->getJson('/api/roles?include_deleted=true')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.2.deleted_at', fn ($val) => $val !== null);

    $this->getJson('/api/roles?include_deleted=false')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('searches roles by feature name or alias', function () {
    $feature = Feature::query()->create([
        'v_alias' => 'user-management',
        'v_name' => 'Manajemen Pengguna',
        'e_type' => PermissionType::MENU,
    ]);

    $role1 = Role::query()->create([
        'v_code' => 'admin',
        'v_name' => 'Administrator',
    ]);
    $role1->features()->attach($feature->v_alias);

    Role::query()->create([
        'v_code' => 'operator',
        'v_name' => 'Operator',
    ]);

    $this->getJson('/api/roles?search=Manajemen')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'admin');
});

it('returns options list of active roles', function () {
    Role::query()->create([
        'v_code' => 'superadmin',
        'v_name' => 'Superadmin',
        'b_need_region' => false,
        'b_need_unit' => false,
        'v_active_periode' => ['start' => '2026-01-01', 'end' => '2026-12-31'],
        'b_locked' => true,
    ]);

    $this->getJson('/api/roles/options')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.code', 'superadmin')
        ->assertJsonPath('data.0.need_region', false)
        ->assertJsonPath('data.0.need_unit', false)
        ->assertJsonPath('data.0.active_periode.start', '2026-01-01')
        ->assertJsonPath('data.0.locked', true);
});

it('returns only lower-level role options for non-root users', function () {
    Role::query()->create(['v_code' => 'MANAGER', 'v_name' => 'Manager', 'i_level' => 50]);
    Role::query()->create(['v_code' => 'STAFF', 'v_name' => 'Staff', 'i_level' => 10]);
    Role::query()->create(['v_code' => 'PEER', 'v_name' => 'Peer', 'i_level' => 50]);
    $user = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'MANAGER',
        'v_created_by' => 'system',
    ]);

    $this->actingAs($user)
        ->getJson('/api/roles/options')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'STAFF');
});

it('does not let a permission alias bypass a model policy', function () {
    $permission = Feature::query()->create([
        'v_name' => 'Generic Update',
        'v_alias' => 'update',
        'e_type' => PermissionType::CRUD,
    ]);
    $manager = Role::query()->create(['v_code' => 'MANAGER', 'v_name' => 'Manager', 'i_level' => 50]);
    $manager->features()->attach($permission->v_alias);
    $target = Role::query()->create(['v_code' => 'MANAGER_STAFF', 'v_name' => 'Staff', 'i_level' => 10]);
    $user = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $user->v_userid,
        'v_role_code' => $manager->v_code,
        'v_created_by' => 'system',
    ]);

    expect($user->can('update', $target))->toBeFalse();
});

it('prevents non-root users from assigning features they do not own', function () {
    $createPermission = Feature::query()->create([
        'v_name' => 'Tambah Group',
        'v_alias' => 'tambah-group',
        'e_type' => PermissionType::CRUD,
    ]);
    Feature::query()->create([
        'v_name' => 'Restricted Action',
        'v_alias' => 'restricted-action',
        'e_type' => PermissionType::CRUD,
    ]);
    $manager = Role::query()->create(['v_code' => 'MANAGER', 'v_name' => 'Manager', 'i_level' => 50]);
    $manager->features()->attach($createPermission->v_alias);
    $user = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $user->v_userid,
        'v_role_code' => $manager->v_code,
        'v_created_by' => 'system',
    ]);

    $this->actingAs($user)
        ->postJson('/api/roles', [
            'code' => 'STAFF',
            'name' => 'Staff',
            'features' => ['restricted-action'],
        ])
        ->assertForbidden()
        ->assertJsonPath('code', 'ROLE-AUTH-001');
});

it('uses the current active group when replacing a stale role prefix', function () {
    $createPermission = Feature::query()->create([
        'v_name' => 'Tambah Group',
        'v_alias' => 'tambah-group',
        'e_type' => PermissionType::CRUD,
    ]);
    $suban = Role::query()->create(['v_code' => 'SUBAN', 'v_name' => 'Suban', 'i_level' => 50]);
    $adminOpd = Role::query()->create(['v_code' => 'ADM_OPD', 'v_name' => 'Admin OPD', 'i_level' => 50]);
    $suban->features()->attach($createPermission->v_alias);
    $adminOpd->features()->attach($createPermission->v_alias);
    $user = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        [
            'v_userid' => $user->v_userid,
            'v_role_code' => $suban->v_code,
            'v_created_by' => 'system',
        ],
        [
            'v_userid' => $user->v_userid,
            'v_role_code' => $adminOpd->v_code,
            'v_created_by' => 'system',
        ],
    ]);
    $this->actingAs($user);

    $this->postJson('/api/auth/active-group', ['group_id' => 'SUBAN'])->assertOk();
    $this->postJson('/api/auth/active-group', ['group_id' => 'ADM_OPD'])
        ->assertOk()
        ->assertJsonPath('data.active_group_id', 'ADM_OPD');

    $this->postJson('/api/roles', [
        'code' => 'SUBAN_OPERATOR',
        'name' => 'Operator',
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'ADM_OPD_OPERATOR');

    $this->assertDatabaseHas('tm_roles', ['v_code' => 'ADM_OPD_OPERATOR']);
    $this->assertDatabaseMissing('tm_roles', ['v_code' => 'ADM_OPD_SUBAN_OPERATOR']);
});

it('shows single role detail with features', function () {
    $feature = Feature::query()->create([
        'v_name' => 'User Management',
        'v_alias' => 'user-management',
        'e_type' => 'menu',
    ]);

    $role = Role::query()->create([
        'v_code' => 'manager',
        'v_name' => 'Manager',
    ]);
    $role->features()->sync([$feature->v_alias]);

    $this->getJson("/api/roles/{$role->hash_id}")
        ->assertOk()
        ->assertJsonPath('data.code', 'manager')
        ->assertJsonPath('data.features.0.alias', 'user-management');
});

it('creates a role with features and active_periode', function () {
    $feature1 = Feature::query()->create([
        'v_name' => 'Fitur 1',
        'v_alias' => 'fitur-1',
        'e_type' => 'menu',
    ]);
    $feature2 = Feature::query()->create([
        'v_name' => 'Fitur 2',
        'v_alias' => 'fitur-2',
        'e_type' => 'crud',
    ]);

    $this->postJson('/api/roles', [
        'code' => 'operator',
        'name' => 'Operator System',
        'need_region' => true,
        'need_unit' => false,
        'active_periode' => ['start' => '2026-01-01', 'end' => '2026-12-31'],
        'locked' => false,
        'features' => ['fitur-1', 'fitur-2'],
    ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'operator')
        ->assertJsonPath('data.need_region', true)
        ->assertJsonPath('data.features.0.alias', 'fitur-1');

    $createdRole = Role::query()->where('v_code', 'operator')->first();
    expect($createdRole)->not->toBeNull();
    expect($createdRole->features->pluck('v_alias')->toArray())->toEqualCanonicalizing(['fitur-1', 'fitur-2']);
});

it('validates unique code only among active records', function () {
    $deleted = Role::query()->create([
        'v_code' => 'supervisor',
        'v_name' => 'Supervisor Terhapus',
    ]);
    $deleted->delete();

    // Harus sukses karena yang lama sudah di soft-delete
    $this->postJson('/api/roles', [
        'code' => 'supervisor',
        'name' => 'Supervisor Baru',
    ])->assertCreated();

    // Harus gagal karena sudah ada record aktif dengan code sama
    $this->postJson('/api/roles', [
        'code' => 'supervisor',
        'name' => 'Supervisor Duplikat',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0.code', 'ROLE-VAL-021');
});

it('updates role details and feature sync', function () {
    $f1 = Feature::query()->create(['v_name' => 'F1', 'v_alias' => 'f1', 'e_type' => 'menu']);
    $f2 = Feature::query()->create(['v_name' => 'F2', 'v_alias' => 'f2', 'e_type' => 'menu']);

    $role = Role::query()->create([
        'v_code' => 'editor',
        'v_name' => 'Editor',
        'b_need_region' => false,
    ]);
    $role->features()->sync([$f1->v_alias]);

    $this->putJson("/api/roles/{$role->hash_id}", [
        'code' => 'editor-v2',
        'name' => 'Content Editor',
        'need_region' => true,
        'features' => ['f2'],
    ])
        ->assertOk()
        ->assertJsonPath('data.code', 'editor-v2')
        ->assertJsonPath('data.name', 'Content Editor')
        ->assertJsonPath('data.need_region', true)
        ->assertJsonPath('data.features.0.alias', 'f2');
});

it('prevents updating code when role is locked', function () {
    $role = Role::query()->create([
        'v_code' => 'superadmin',
        'v_name' => 'Superadmin System',
        'b_locked' => true,
    ]);

    $this->putJson("/api/roles/{$role->hash_id}", [
        'code' => 'superadmin-new',
        'name' => 'Superadmin System Updated',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.code.0.code', 'ROLE-BIZ-003');
});

it('prevents deleting role when role is locked', function () {
    $role = Role::query()->create([
        'v_code' => 'superadmin',
        'v_name' => 'Superadmin System',
        'b_locked' => true,
    ]);

    $this->deleteJson("/api/roles/{$role->hash_id}")
        ->assertUnprocessable()
        ->assertJsonPath('code', 'ROLE-BIZ-002');

    expect($role->fresh()->trashed())->toBeFalse();
});

it('soft deletes role when unlocked', function () {
    $role = Role::query()->create([
        'v_code' => 'tester',
        'v_name' => 'QA Tester',
        'b_locked' => false,
    ]);

    $this->deleteJson("/api/roles/{$role->hash_id}")
        ->assertNoContent();

    expect($role->fresh()->trashed())->toBeTrue();
});

it('restores soft-deleted role when code is available', function () {
    $role = Role::query()->create([
        'v_code' => 'tester',
        'v_name' => 'QA Tester',
    ]);
    $role->delete();

    $this->postJson("/api/roles/{$role->hash_id}/restore")
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);

    expect($role->fresh()->trashed())->toBeFalse();
});

it('rejects restore when code conflict exists', function () {
    $deleted = Role::query()->create([
        'v_code' => 'guest',
        'v_name' => 'Guest Lama',
    ]);
    $deleted->delete();

    Role::query()->create([
        'v_code' => 'guest',
        'v_name' => 'Guest Baru',
    ]);

    $this->postJson("/api/roles/{$deleted->hash_id}/restore")
        ->assertConflict()
        ->assertJsonPath('code', 'ROLE-BIZ-001');

    expect($deleted->fresh()->trashed())->toBeTrue();
});

it('returns an EDF not-found response for an unknown role', function () {
    $this->getJson('/api/roles/999999')
        ->assertNotFound()
        ->assertJsonPath('code', 'ROLE-NF-001');
});
