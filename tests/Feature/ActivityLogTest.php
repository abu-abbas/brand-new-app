<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserRole;
use App\Services\FeatureService;
use App\Services\RoleService;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::create([
        'v_userid' => 'admin_test',
        'v_username' => 'Admin Tester',
        'v_email' => 'admintest@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);

    UserRole::create([
        'v_userid' => 'admin_test',
        'v_role_code' => 'ADMIN',
    ]);

    $this->actingAs($this->admin);
});

it('records activity log on user creation, update, toggle status, and deletion', function () {
    $userService = app(UserService::class);

    // Create
    $user = $userService->createUser([
        'userid' => 'testuser01',
        'username' => 'Test User',
        'email' => 'testuser01@example.com',
        'is_active' => true,
        'is_external' => false,
    ], 'admin_test');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'testuser01',
        'v_event' => 'created',
        'v_causer_id' => 'admin_test',
    ]);

    // Update
    $userService->updateUser($user, [
        'username' => 'Test User Updated',
    ], 'admin_test');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'testuser01',
        'v_event' => 'updated',
        'v_causer_id' => 'admin_test',
    ]);

    // Toggle Status
    $userService->toggleUserStatus($user, 'admin_test');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'testuser01',
        'v_event' => 'updated',
    ]);

    // Delete
    $userService->deleteUser($user, 'admin_test');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'testuser01',
        'v_event' => 'deleted',
    ]);
});

it('records activity log on role mutations', function () {
    $roleService = app(RoleService::class);

    $role = $roleService->create([
        'code' => 'ROLE_MUTATION_TEST',
        'name' => 'Role Mutation Test',
        'level' => 10,
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Role',
        'v_subject_id' => $role->v_code,
        'v_event' => 'created',
    ]);

    $roleService->update($role, [
        'name' => 'Role Mutation Test Updated',
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Role',
        'v_subject_id' => $role->v_code,
        'v_event' => 'updated',
    ]);

    $roleService->delete($role);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Role',
        'v_subject_id' => $role->v_code,
        'v_event' => 'deleted',
    ]);
});

it('records activity log on feature mutations', function () {
    $featureService = app(FeatureService::class);

    $feature = $featureService->create([
        'name' => 'Feature Test',
        'alias' => 'feature-test',
        'type' => 'crud',
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Feature',
        'v_subject_id' => 'feature-test',
        'v_event' => 'created',
    ]);

    $featureService->update($feature, [
        'name' => 'Feature Test Updated',
        'type' => 'crud',
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Feature',
        'v_subject_id' => 'feature-test',
        'v_event' => 'updated',
    ]);

    $featureService->delete($feature);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Feature',
        'v_subject_id' => 'feature-test',
        'v_event' => 'deleted',
    ]);
});

it('records activity log on user login and logout', function () {
    $this->withHeader('Referer', config('app.url'));

    $this->postJson('/api/auth/login', [
        'username' => 'admin_test',
        'password' => 'password123',
    ])->assertOk();

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'admin_test',
        'v_event' => 'login',
        'v_causer_id' => 'admin_test',
    ]);

    $this->postJson('/api/auth/logout')->assertNoContent();

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'admin_test',
        'v_event' => 'logout',
        'v_causer_id' => 'admin_test',
    ]);
});

it('records role assignment details in activity log on user creation and update', function () {
    $userService = app(UserService::class);

    $user = $userService->createUser([
        'userid' => 'testuser_roles',
        'username' => 'User Roles Test',
        'email' => 'roles_test@example.com',
        'is_active' => true,
        'is_external' => false,
        'roles' => [
            ['role_code' => 'ADMIN'],
        ],
    ], 'admin_test');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'testuser_roles',
        'v_event' => 'created',
    ]);

    $log = ActivityLog::where('v_subject_id', 'testuser_roles')
        ->where('v_event', 'created')
        ->first();

    expect($log->j_properties['roles'])->toContain('ADMIN');

    $userService->updateUser($user, [
        'roles' => [
            ['role_code' => 'OPERATOR'],
        ],
    ], 'admin_test');

    $updateLog = ActivityLog::where('v_subject_id', 'testuser_roles')
        ->where('v_event', 'updated')
        ->latest('i_id')
        ->first();

    expect($updateLog->j_properties['roles_before'])->toContain('ADMIN');
    expect($updateLog->j_properties['roles_after'])->toContain('OPERATOR');
});
