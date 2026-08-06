<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('pengguna dapat mengambil log aktivitas miliknya', function () {
    $user = User::factory()->create([
        'v_userid' => 'user123',
    ]);

    DB::table('tr_user_roles')->insert([
        'v_userid' => $user->v_userid,
        'v_role_code' => 'STAF',
        'v_created_by' => 'system',
    ]);

    ActivityLog::create([
        'v_subject_type' => User::class,
        'v_subject_id' => $user->v_userid,
        'v_event' => 'user.login',
        'v_causer_id' => $user->v_userid,
        'v_ip_address' => '127.0.0.1',
        'j_properties' => ['browser' => 'Chrome'],
        'dt_created_at' => now(),
    ]);

    ActivityLog::create([
        'v_subject_type' => User::class,
        'v_subject_id' => 'other_user',
        'v_event' => 'user.login',
        'v_causer_id' => 'other_user',
        'v_ip_address' => '127.0.0.1',
        'j_properties' => [],
        'dt_created_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/profile/activity-logs');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.causer_name', $user->v_username)
        ->assertJsonPath('data.0.event', 'user.login');
});

test('tamu tidak dapat mengakses log aktivitas profile', function () {
    $response = $this->getJson('/api/profile/activity-logs');

    $response->assertUnauthorized();
});
