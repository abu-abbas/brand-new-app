<?php

use App\Jobs\SendImpersonationEndedNotification;
use App\Jobs\SendImpersonationStartedNotification;
use App\Models\Feature;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\ImpersonateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['captcha.disable' => true]);
    $this->withHeader('Referer', config('app.url'));
    Queue::fake();

    // Create Admin Role (Level 100) with impersonate-pengguna permission
    $adminRole = Role::create([
        'v_code' => 'ROLE_ADMIN',
        'v_name' => 'Role Administrator',
        'i_level' => 100,
        'b_need_region' => false,
        'b_need_unit' => false,
        'b_locked' => false,
    ]);

    $featureImpersonate = Feature::create([
        'v_name' => 'Impersonate Pengguna',
        'v_alias' => 'impersonate-pengguna',
        'e_type' => 'crud',
    ]);
    $featureUserMgmt = Feature::create([
        'v_name' => 'Manajemen Pengguna',
        'v_alias' => 'manajemen-pengguna',
        'e_type' => 'crud',
    ]);

    $adminRole->features()->sync([$featureImpersonate->v_alias, $featureUserMgmt->v_alias]);

    // Create Operator Role (Level 50) with only user management permission
    $operatorRole = Role::create([
        'v_code' => 'ROLE_OPERATOR',
        'v_name' => 'Role Operator',
        'i_level' => 50,
        'b_need_region' => false,
        'b_need_unit' => false,
        'b_locked' => false,
    ]);

    $operatorRole->features()->sync([$featureUserMgmt->v_alias]);

    // Create Staff Role (Level 20)
    $staffRole = Role::create([
        'v_code' => 'ROLE_STAFF',
        'v_name' => 'Role Staff',
        'i_level' => 20,
        'b_need_region' => false,
        'b_need_unit' => false,
        'b_locked' => false,
    ]);

    // Create Admin User
    $this->admin = User::create([
        'v_userid' => 'admin01',
        'v_username' => 'Admin Utama',
        'v_email' => 'admin@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);

    UserRole::create([
        'v_userid' => 'admin01',
        'v_role_code' => 'ROLE_ADMIN',
    ]);

    // Create Target User (Single-group)
    $this->targetSingle = User::create([
        'v_userid' => 'operator01',
        'v_username' => 'Operator Satu',
        'v_email' => 'operator01@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);

    UserRole::create([
        'v_userid' => 'operator01',
        'v_role_code' => 'ROLE_OPERATOR',
    ]);

    // Create Target User (Multi-group)
    $this->targetMulti = User::create([
        'v_userid' => 'multistaff01',
        'v_username' => 'Multi Staff',
        'v_email' => 'multistaff@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);

    UserRole::create([
        'v_userid' => 'multistaff01',
        'v_role_code' => 'ROLE_OPERATOR',
    ]);

    UserRole::create([
        'v_userid' => 'multistaff01',
        'v_role_code' => 'ROLE_STAFF',
    ]);
});

it('allows admin to impersonate single-group target without target_group_id', function () {
    $this->actingAs($this->admin);

    $res = $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")
        ->assertOk()
        ->assertJsonPath('data.userid', 'operator01')
        ->assertJsonPath('data.is_impersonating', true)
        ->assertJsonPath('data.impersonated_active_group', 'ROLE_OPERATOR')
        ->assertJsonPath('data.impersonator.userid', 'admin01');

    expect(session('impersonator_id'))->toBe('admin01');

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_started',
        'v_causer_id' => 'operator01',
        'v_impersonator_id' => 'admin01',
    ]);

    Queue::assertPushed(SendImpersonationStartedNotification::class);
});

it('requires target_group_id for multi-group target', function () {
    $this->actingAs($this->admin);

    // Missing target_group_id -> IMP-BR-002
    $this->postJson("/api/users/{$this->targetMulti->hash_id}/impersonate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'IMP-BR-002');

    // Invalid target_group_id -> IMP-BR-004
    $this->postJson("/api/users/{$this->targetMulti->hash_id}/impersonate", [
        'target_group_id' => 'ROLE_INVALID',
    ])
        ->assertStatus(422)
        ->assertJsonPath('code', 'IMP-BR-004');

    // Valid target_group_id -> succeeds
    $this->postJson("/api/users/{$this->targetMulti->hash_id}/impersonate", [
        'target_group_id' => 'ROLE_STAFF',
    ])
        ->assertOk()
        ->assertJsonPath('data.active_group_id', 'ROLE_STAFF')
        ->assertJsonPath('data.impersonated_active_group', 'ROLE_STAFF');
});

it('validates target_group_id boundaries correctly', function () {
    $this->actingAs($this->admin);

    // Non-string -> IMP-VAL-001
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate", [
        'target_group_id' => 12345,
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.target_group_id.0.code', 'IMP-VAL-001');

    // Exceeds max 100 chars -> IMP-VAL-002
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate", [
        'target_group_id' => str_repeat('A', 101),
    ])
        ->assertStatus(422)
        ->assertJsonPath('errors.target_group_id.0.code', 'IMP-VAL-002');
});

it('rejects self impersonation', function () {
    $this->actingAs($this->admin);

    $this->postJson("/api/users/{$this->admin->hash_id}/impersonate")
        ->assertStatus(422)
        ->assertJsonPath('code', 'IMP-BR-001');
});

it('rejects impersonation when user lacks impersonate-pengguna permission', function () {
    $noPermUser = User::create([
        'v_userid' => 'noperm01',
        'v_username' => 'No Perm User',
        'v_email' => 'noperm@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);
    UserRole::create(['v_userid' => 'noperm01', 'v_role_code' => 'ROLE_OPERATOR']);

    $this->actingAs($noPermUser);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-001');
});

it('rejects impersonation when admin level is less than or equal to target level', function () {
    // Operator dengan permission impersonate mencoba impersonate operator lain (same level = 50)
    $operatorWithPerm = User::create([
        'v_userid' => 'operperm01',
        'v_username' => 'Operator Perm User',
        'v_email' => 'operperm@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);
    UserRole::create(['v_userid' => 'operperm01', 'v_role_code' => 'ROLE_OPERATOR']);
    Role::where('v_code', 'ROLE_OPERATOR')->first()->features()->attach('impersonate-pengguna');

    // Target: operator lain (same level) -> dalam scope tapi level tidak cukup
    $this->actingAs($operatorWithPerm);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-003');
});

it('rejects nested impersonation', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    $this->actingAs($this->targetSingle);
    $this->postJson("/api/users/{$this->targetMulti->hash_id}/impersonate", ['target_group_id' => 'ROLE_STAFF'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'IMP-WF-001');
});

it('enforces permission ceiling and blocks group switching / sensitive actions', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    $this->actingAs($this->targetSingle);

    // Group switch attempt -> IMP-AUTH-004
    $this->postJson('/api/auth/active-group', ['group_id' => 'ROLE_ADMIN'])
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-004');

    // Sensitive action attempt (change password) -> IMP-AUTH-004
    $this->putJson('/api/auth/password', [
        'current_password' => 'password123',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-004');
});

it('dispatches impersonation ended notification when user logs out during impersonation', function () {
    Queue::fake();

    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    $this->actingAs($this->targetSingle);
    $this->postJson('/api/auth/logout')->assertNoContent();

    Queue::assertPushed(SendImpersonationEndedNotification::class);
});

it('enforces fixed hard cutoff TTL (60 minutes)', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    $this->actingAs($this->targetSingle);

    // Request at minute 59 -> succeeds
    Carbon::setTestNow(Carbon::now()->addMinutes(59));
    $this->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.userid', 'operator01');

    // Request at minute 61 -> IMP-WF-003 (409) & restores Admin
    Carbon::setTestNow(Carbon::now()->addMinutes(2)); // Total +61 minutes
    $this->getJson('/api/auth/me')
        ->assertStatus(409)
        ->assertJsonPath('code', 'IMP-WF-003');

    // Session is restored to Admin
    expect(session('impersonator_id'))->toBeNull();

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_expired',
        'v_causer_id' => 'operator01',
        'v_impersonator_id' => 'admin01',
    ]);

    Queue::assertPushed(SendImpersonationEndedNotification::class);
    Carbon::setTestNow();
});

function setImpersonateTestSession($test, $targetUser, $adminUserId = 'admin01')
{
    $test->actingAs($targetUser);
    session([
        'impersonator_id' => $adminUserId,
        'impersonated_active_group' => 'ROLE_OPERATOR',
        'impersonate_started_at' => \Illuminate\Support\Carbon::now()->toIso8601String(),
    ]);
}

it('invalidates session real-time if target role assignment is deleted mid-session', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    setImpersonateTestSession($this, $this->targetSingle);

    // Delete target role assignment mid-session
    UserRole::where('v_userid', 'operator01')->delete();

    // Next request -> IMP-WF-004 (409) & restores Admin
    $this->getJson('/api/auth/me')
        ->assertStatus(409)
        ->assertJsonPath('code', 'IMP-WF-004');

    expect(session('impersonator_id'))->toBeNull();

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_invalidated',
        'v_causer_id' => 'operator01',
        'v_impersonator_id' => 'admin01',
    ]);
});

it('restores admin identity on manual leave', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    setImpersonateTestSession($this, $this->targetSingle);

    $this->postJson('/api/impersonate/leave')
        ->assertOk()
        ->assertJsonPath('data.userid', 'admin01')
        ->assertJsonPath('data.is_impersonating', false);

    expect(session('impersonator_id'))->toBeNull();

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_type' => 'User',
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_stopped',
        'v_causer_id' => 'operator01',
        'v_impersonator_id' => 'admin01',
    ]);
});

it('allows concurrent impersonation by different admins independently', function () {
    // Create Admin 2
    $admin2 = User::create([
        'v_userid' => 'admin02',
        'v_username' => 'Admin Kedua',
        'v_email' => 'admin2@example.com',
        'v_password' => Hash::make('password123'),
        'b_is_active' => true,
        'b_use_other' => false,
        'dt_last_updated_password' => Carbon::now(),
    ]);
    UserRole::create(['v_userid' => 'admin02', 'v_role_code' => 'ROLE_ADMIN']);

    // Admin 1 starts impersonate via HTTP
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    setImpersonateTestSession($this, $this->targetSingle);

    // Admin 1 leaves impersonate
    $this->postJson('/api/impersonate/leave')->assertOk();

    // Admin 2 starts impersonate via service layer langsung
    // (session-based test tidak bisa mensimulasikan 2 browser session terpisah)
    session()->forget(['impersonator_id', 'impersonator_active_group', 'impersonated_active_group', 'impersonate_started_at']);
    Auth::guard('web')->login($admin2);
    session(['active_group_id' => 'ROLE_ADMIN']);

    $impersonateService = app(ImpersonateService::class);
    $impersonateService->start($admin2, $this->targetSingle, null);

    // Both activity logs are distinct by v_impersonator_id
    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_started',
        'v_impersonator_id' => 'admin01',
    ]);

    $this->assertDatabaseHas('tm_activity_log', [
        'v_subject_id' => 'operator01',
        'v_event' => 'impersonation_started',
        'v_impersonator_id' => 'admin02',
    ]);
});

it('blocks sensitive actions including change password and update email attempt', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    setImpersonateTestSession($this, $this->targetSingle);

    // 1. change password attempt -> IMP-AUTH-004 (403) SENSITIVE_ACTION_BLOCKED
    $this->putJson('/api/auth/password', [
        'current_password' => 'password123',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-004');

    // 2. update attempt with email key present -> IMP-AUTH-004 (403) SENSITIVE_ACTION_BLOCKED
    $this->putJson("/api/users/{$this->targetSingle->hash_id}", [
        'username' => 'Operator Satu Baru',
        'email' => 'operator01@example.com',
        'type' => 'local',
        'roles' => ['ROLE_OPERATOR'],
    ])
        ->assertStatus(403)
        ->assertJsonPath('code', 'IMP-AUTH-004');
});

it('allows leave and logout even if target user is deactivated mid-session', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    setImpersonateTestSession($this, $this->targetSingle);

    // Deactivate target user mid-session
    $this->targetSingle->update(['b_is_active' => false]);

    // Leave request must still succeed and restore Admin
    $this->postJson('/api/impersonate/leave')
        ->assertOk()
        ->assertJsonPath('data.userid', 'admin01')
        ->assertJsonPath('data.is_impersonating', false);

    expect(session('impersonator_id'))->toBeNull();
});

it('handles corrupted session state by invalidating session with IMP-WF-004', function () {
    $this->actingAs($this->admin);
    $this->postJson("/api/users/{$this->targetSingle->hash_id}/impersonate")->assertOk();

    $this->actingAs($this->targetSingle);

    // Corrupt session state by forgetting started_at
    session()->forget('impersonate_started_at');

    $this->getJson('/api/auth/me')
        ->assertStatus(409)
        ->assertJsonPath('code', 'IMP-WF-004');
});
