<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withHeader('Referer', config('app.url'));
    $this->actingAs(User::factory()->create());
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
