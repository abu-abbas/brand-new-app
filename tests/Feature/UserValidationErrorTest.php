<?php

use App\Constants\RoleConstant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Referer', config('app.url'));
    DB::table('tm_roles')->insert([
        'v_code' => 'ROOT',
        'v_name' => 'Root',
        'i_level' => RoleConstant::ROOT_LEVEL,
        'v_created_by' => 'system',
    ]);
    $root = User::factory()->create(['v_userid' => 'root']);
    DB::table('tr_user_roles')->insert([
        'v_userid' => $root->v_userid,
        'v_role_code' => 'ROOT',
        'v_created_by' => 'system',
    ]);
    $this->actingAs($root);
});

it('returns structured EDF errors for invalid search fields', function () {
    $this->getJson('/api/users?search_fields=invalid')
        ->assertUnprocessable()
        ->assertJsonPath('errors.search_fields.0.code', 'UM-VAL-014');

    $response = $this->json('GET', '/api/users', ['search_fields' => [123]])
        ->assertUnprocessable();

    expect($response->json('errors')['search_fields.0'][0]['code'])
        ->toBe('UM-VAL-015');
});
