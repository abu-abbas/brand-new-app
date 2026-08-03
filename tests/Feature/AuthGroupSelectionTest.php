<?php

use App\Constants\RoleConstant;
use App\Http\Resources\UserResource;
use App\Models\Feature;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Referer', config('app.url'));
});

test('user dengan 1 role otomatis mendapatkan active group di session saat login', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);

    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'ROLE_OPERATOR',
    ]);

    $response = $this->postJson('/api/auth/login', [
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

    $response = $this->postJson('/api/auth/login', [
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

    $response = $this->postJson('/api/auth/active-group', [
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

    $response = $this->postJson('/api/auth/active-group', [
        'group_id' => 'ROLE_SUPER_ADMIN',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('code', 'AUTH-ACC-002');
});

test('hanya assignment dalam periode berlaku dan belum dihapus yang memengaruhi sesi', function () {
    $user = User::factory()->create();

    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'OPEN']);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'BOUNDARY',
        'dt_valid_from' => today(),
        'dt_valid_until' => today(),
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'EXPIRED',
        'dt_valid_until' => today()->subDay(),
    ]);
    UserRole::create([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'FUTURE',
        'dt_valid_from' => today()->addDay(),
    ]);
    $deleted = UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'DELETED']);
    $deleted->delete();

    $this->actingAs($user, 'web')
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.roles', ['OPEN', 'BOUNDARY'])
        ->assertJsonCount(2, 'data.user_roles');

    expect(DB::table('tr_user_roles')->whereNotNull('dt_deleted_at')->count())->toBe(1);
});

test('role di luar periode aktif tidak tersedia saat login', function () {
    $user = User::factory()->create([
        'v_password' => bcrypt('password123'),
        'b_is_active' => true,
    ]);
    Role::query()->create([
        'v_code' => 'EXPIRED',
        'v_name' => 'Expired',
        'v_active_periode' => [today()->subDays(2)->toDateString(), today()->subDay()->toDateString()],
    ]);
    Role::query()->create([
        'v_code' => 'ACTIVE',
        'v_name' => 'Active',
        'v_active_periode' => ['start' => today()->toDateString(), 'end' => today()->toDateString()],
    ]);
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'EXPIRED']);
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => 'ACTIVE']);

    $this->postJson('/api/auth/login', [
        'username' => $user->v_userid,
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonPath('data.roles', ['ACTIVE'])
        ->assertJsonPath('data.active_group_id', 'ACTIVE')
        ->assertJsonCount(1, 'data.user_roles');
});

test('is_root hanya diekspos untuk user dengan level root', function () {
    Role::query()->create(['v_code' => 'STAFF', 'v_name' => 'Staff', 'i_level' => 10]);
    Role::query()->create(['v_code' => 'ROOT', 'v_name' => 'Root', 'i_level' => RoleConstant::ROOT_LEVEL]);
    $staff = User::factory()->create();
    $root = User::factory()->create();
    UserRole::create(['v_userid' => $staff->v_userid, 'v_role_code' => 'STAFF']);
    UserRole::create(['v_userid' => $root->v_userid, 'v_role_code' => 'ROOT']);

    $staffData = UserResource::make($staff->load('userRoles.roleModel.features'))->resolve();
    $rootData = UserResource::make($root->load('userRoles.roleModel.features'))->resolve();

    expect($staffData)->not->toHaveKey('is_root')
        ->and($rootData['is_root'])->toBeTrue();
});

test('atribut otorisasi berulang memakai cache instance yang sama', function () {
    $feature = Feature::query()->create([
        'v_name' => 'Dashboard',
        'v_alias' => 'dashboard',
        'e_type' => 'menu',
    ]);
    $role = Role::query()->create(['v_code' => 'STAFF', 'v_name' => 'Staff', 'i_level' => 10]);
    $role->features()->attach($feature->v_alias);
    $user = User::factory()->create();
    UserRole::create(['v_userid' => $user->v_userid, 'v_role_code' => $role->v_code]);
    $user = $user->fresh();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $user->getRolesList();
    $user->getPermissionsList();
    $user->role_level;
    $firstPassQueries = count(DB::getQueryLog());

    $user->getRolesList();
    $user->getPermissionsList();
    $user->role_level;

    expect(count(DB::getQueryLog()))->toBe($firstPassQueries);
});
