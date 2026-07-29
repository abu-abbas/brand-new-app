<?php

use Illuminate\Support\Str;

it('only reuses valid UUID v4 request IDs', function () {
    $valid = '550e8400-e29b-41d4-a716-446655440000';

    $this->withHeader('X-Request-Id', strtoupper($valid))
        ->get('/')
        ->assertHeader('X-Request-Id', $valid);

    $generated = $this->withHeader('X-Request-Id', '; DROP TABLE--')
        ->get('/')
        ->headers->get('X-Request-Id');

    expect($generated)
        ->not->toBe('; DROP TABLE--')
        ->and(Str::isUuid($generated, 4))->toBeTrue();

    $uuid7 = (string) Str::uuid7();
    $replacement = $this->withHeader('X-Request-Id', $uuid7)
        ->get('/')
        ->headers->get('X-Request-Id');

    expect($replacement)
        ->not->toBe($uuid7)
        ->and(Str::isUuid($replacement, 4))->toBeTrue();
});
