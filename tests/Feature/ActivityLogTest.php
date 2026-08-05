<?php

use App\Models\User;
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
        'v_subject_id' => 'ROLE_MUTATION_TEST',
        'v_event' => 'created',
    ]);

    $roleService->update($role, [
        'name' => 'Role Mutation Test Updated',
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Role',
        'v_subject_id' => 'ROLE_MUTATION_TEST',
        'v_event' => 'updated',
    ]);

    $roleService->delete($role);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'Role',
        'v_subject_id' => 'ROLE_MUTATION_TEST',
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
