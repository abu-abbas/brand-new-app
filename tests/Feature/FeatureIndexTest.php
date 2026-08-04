<?php

use App\Constants\RoleConstant;
use App\Models\Feature;
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

it('filters menu items by user permissions for non-admin users', function () {
    Feature::query()->create([
        'v_name' => 'Menu Beranda',
        'v_alias' => 'beranda',
        'e_type' => 'menu',
    ]);
    Feature::query()->create([
        'v_name' => 'Menu Rahasia Admin',
        'v_alias' => 'manajemen-fitur',
        'e_type' => 'menu',
        'b_is_restricted' => true,
    ]);

    $regularUser = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $regularUser->v_userid,
        'v_role_code' => 'USER_ROLE',
        'v_created_by' => 'system',
    ]);
    DB::table('tm_roles')->insert([
        'v_code' => 'USER_ROLE',
        'v_name' => 'User Role',
        'i_level' => 1,
        'v_created_by' => 'system',
    ]);
    DB::table('tr_role_features')->insert([
        'v_code' => 'USER_ROLE',
        'v_alias' => 'beranda',
    ]);

    $this->actingAs($regularUser)
        ->getJson('/api/features?type=menu')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.alias', 'beranda');
});

it('hides restricted feature options from non-root users', function () {
    Feature::query()->create([
        'v_name' => 'Publik',
        'v_alias' => 'publik',
        'e_type' => 'menu',
        'b_is_restricted' => false,
    ]);
    Feature::query()->create([
        'v_name' => 'Terbatas',
        'v_alias' => 'terbatas',
        'e_type' => 'menu',
        'b_is_restricted' => true,
    ]);
    DB::table('tm_roles')->insert([
        'v_code' => 'USER_ROLE',
        'v_name' => 'User Role',
        'i_level' => 10,
        'v_created_by' => 'system',
    ]);
    $user = User::factory()->create();
    DB::table('tr_user_roles')->insert([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'USER_ROLE',
        'v_created_by' => 'system',
    ]);

    $this->actingAs($user)
        ->getJson('/api/features/options')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.alias', 'publik');
});

it('lists active and soft-deleted features when requested', function () {
    Feature::query()->create([
        'v_name' => 'Aktif',
        'v_alias' => 'active-feature',
        'e_type' => 'menu',
    ]);

    $deleted = Feature::query()->create([
        'v_name' => 'Terhapus',
        'v_alias' => 'deleted-feature',
        'e_type' => 'crud',
    ]);
    $deleted->delete();

    $withDeleted = $this->getJson('/api/features?include_deleted=true')
        ->assertOk()
        ->assertJsonCount(3, 'data');

    expect(collect($withDeleted->json('data'))->firstWhere('alias', 'deleted-feature')['deleted_at'])
        ->not->toBeNull();

    $this->getJson('/api/features?include_deleted=false')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters features by type and rejects an invalid type', function () {
    Feature::query()->create([
        'v_name' => 'Menu',
        'v_alias' => 'menu',
        'e_type' => 'menu',
    ]);
    Feature::query()->create([
        'v_name' => 'Aksi',
        'v_alias' => 'aksi',
        'e_type' => 'crud',
    ]);

    $response = $this->getJson('/api/features?type=crud')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    expect(collect($response->json('data'))->pluck('alias'))->toContain('aksi');
    expect(collect($response->json('data'))->pluck('type')->unique()->all())->toBe(['crud']);

    $this->getJson('/api/features?type=invalid')
        ->assertUnprocessable()
        ->assertJsonPath('errors.type.0.code', 'FEAT-VAL-027');
});

it('filters features by updated date range', function () {
    $older = Feature::query()->create([
        'v_name' => 'Lama',
        'v_alias' => 'lama',
        'e_type' => 'menu',
    ]);
    $newer = Feature::query()->create([
        'v_name' => 'Baru',
        'v_alias' => 'baru',
        'e_type' => 'menu',
    ]);

    DB::table('tm_features')
        ->where('i_id', $older->i_id)
        ->update(['dt_updated_at' => '2026-07-01 10:00:00']);
    DB::table('tm_features')
        ->where('i_id', $newer->i_id)
        ->update(['dt_updated_at' => '2026-07-15 10:00:00']);

    $this->getJson('/api/features?updated_at_from=2026-07-10&updated_at_to=2026-07-20')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.alias', 'baru');

    $this->getJson('/api/features?updated_at_from=2026-07-20&updated_at_to=2026-07-10')
        ->assertUnprocessable()
        ->assertJsonPath('errors.updated_at_to.0.code', 'FEAT-VAL-045');
});

it('creates a feature and only requires aliases to be unique among active records', function () {
    $deleted = Feature::query()->create([
        'v_name' => 'Lama',
        'v_alias' => 'pengaturan-fitur',
        'e_type' => 'menu',
    ]);
    $deleted->delete();

    $this->postJson('/api/features', [
        'name' => 'Pengaturan Fitur',
        'alias' => 'pengaturan-fitur',
        'type' => 'menu',
        'route' => '/settings/features',
        'icon' => 'ListTree',
        'order' => 2,
        'description' => 'Pengaturan fitur aplikasi.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.alias', 'pengaturan-fitur')
        ->assertJsonPath('data.show_on_sidebar', true);

    $this->postJson('/api/features', [
        'name' => 'Duplikat',
        'alias' => 'pengaturan-fitur',
        'type' => 'menu',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.alias.0.code', 'FEAT-VAL-024');
});

it('stores sidebar visibility only for menus', function () {
    $this->postJson('/api/features', [
        'name' => 'Aksi',
        'alias' => 'aksi',
        'type' => 'crud',
        'show_on_sidebar' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.show_on_sidebar', false);

    $this->postJson('/api/features', [
        'name' => 'Menu Invalid',
        'alias' => 'menu-invalid',
        'type' => 'menu',
        'show_on_sidebar' => 'yes',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.show_on_sidebar.0.code', 'FEAT-VAL-041');
});

it('rejects a menu whose parent is not a menu', function () {
    Feature::query()->create([
        'v_name' => 'Aksi',
        'v_alias' => 'aksi',
        'e_type' => 'crud',
    ]);

    $this->postJson('/api/features', [
        'name' => 'Menu Anak',
        'alias' => 'menu-anak',
        'type' => 'menu',
        'parent' => 'aksi',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.parent.0.code', 'FEAT-VAL-031');
});

it('allows a menu whose parent is a menu', function () {
    Feature::query()->create([
        'v_name' => 'Pengaturan',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);

    $this->postJson('/api/features', [
        'name' => 'Fitur',
        'alias' => 'fitur',
        'type' => 'menu',
        'parent' => 'pengaturan',
    ])
        ->assertCreated()
        ->assertJsonPath('data.parent', 'pengaturan');
});

it('updates a feature without changing its alias', function () {
    $parent = Feature::query()->create([
        'v_name' => 'Pengaturan',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);
    $child = Feature::query()->create([
        'v_name' => 'Fitur',
        'v_alias' => 'fitur',
        'e_type' => 'menu',
        'v_parent' => 'pengaturan',
    ]);

    $this->putJson("/api/features/{$parent->v_alias}", [
        'name' => 'Konfigurasi',
        'alias' => 'konfigurasi',
        'type' => 'menu',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Konfigurasi')
        ->assertJsonPath('data.alias', 'pengaturan');

    expect($child->refresh()->v_parent)->toBe('pengaturan');
});

it('rejects a feature as its own parent', function () {
    $feature = Feature::query()->create([
        'v_name' => 'Pengaturan',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);

    $this->putJson("/api/features/{$feature->v_alias}", [
        'name' => 'Pengaturan',
        'alias' => 'pengaturan',
        'type' => 'menu',
        'parent' => 'pengaturan',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.parent.0.code', 'FEAT-VAL-042');
});

it('soft deletes a feature', function () {
    $feature = Feature::query()->create([
        'v_name' => 'Pengaturan',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);

    $this->deleteJson("/api/features/{$feature->v_alias}")
        ->assertNoContent();

    expect(Feature::withTrashed()->find($feature->i_id)?->trashed())->toBeTrue();
});

it('restores a soft-deleted feature when its alias is available', function () {
    $feature = Feature::query()->create([
        'v_name' => 'Pengaturan',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);
    $feature->delete();

    $this->postJson("/api/features/{$feature->v_alias}/restore")
        ->assertOk()
        ->assertJsonPath('data.deleted_at', null);

    expect($feature->refresh()->trashed())->toBeFalse();
});

it('rejects restore when the alias is already active', function () {
    $deleted = Feature::query()->create([
        'v_name' => 'Pengaturan Lama',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);
    $deleted->delete();

    Feature::query()->create([
        'v_name' => 'Pengaturan Baru',
        'v_alias' => 'pengaturan',
        'e_type' => 'menu',
    ]);

    $this->postJson("/api/features/{$deleted->v_alias}/restore")
        ->assertConflict()
        ->assertJsonPath('code', 'FEAT-BIZ-001')
        ->assertJsonMissingPath('context');

    expect($deleted->refresh()->trashed())->toBeTrue();
});
