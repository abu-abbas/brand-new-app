<?php

use App\Core\ErrorDefinition\ContextSanitizer;

it('redacts baseline sensitive keys', function () {
    $sanitizer = new ContextSanitizer;

    $result = $sanitizer->sanitize([
        'user_id' => 42,
        'password' => 'secret123',
        'token' => 'abc-def',
        'api_key' => 'key123',
    ]);

    expect($result['user_id'])->toBe(42)
        ->and($result['password'])->toBe('[REDACTED]')
        ->and($result['token'])->toBe('[REDACTED]')
        ->and($result['api_key'])->toBe('[REDACTED]');
});

it('redacts nested sensitive keys recursively', function () {
    $sanitizer = new ContextSanitizer;

    $result = $sanitizer->sanitize([
        'user' => [
            'name' => 'John',
            'password' => 'secret',
            'meta' => [
                'secret' => 'deep-secret',
                'email' => 'john@example.com',
            ],
        ],
    ]);

    expect($result['user']['name'])->toBe('John')
        ->and($result['user']['password'])->toBe('[REDACTED]')
        ->and($result['user']['meta']['secret'])->toBe('[REDACTED]')
        ->and($result['user']['meta']['email'])->toBe('john@example.com');
});

it('adds domain-specific sensitive keys', function () {
    $sanitizer = new ContextSanitizer(['nomor_rekening', 'nik']);

    $result = $sanitizer->sanitize([
        'name' => 'John',
        'nomor_rekening' => '1234567890',
        'nik' => '3201234567890001',
    ]);

    expect($result['name'])->toBe('John')
        ->and($result['nomor_rekening'])->toBe('[REDACTED]')
        ->and($result['nik'])->toBe('[REDACTED]');
});

it('is case-insensitive for key matching', function () {
    $sanitizer = new ContextSanitizer;

    $result = $sanitizer->sanitize([
        'Password' => 'secret',
        'TOKEN' => 'abc',
        'Authorization' => 'Bearer xyz',
    ]);

    expect($result['Password'])->toBe('[REDACTED]')
        ->and($result['TOKEN'])->toBe('[REDACTED]')
        ->and($result['Authorization'])->toBe('[REDACTED]');
});

it('converts non-scalar non-array values to string', function () {
    $sanitizer = new ContextSanitizer;
    $obj = new class
    {
        public function __toString(): string
        {
            return 'stringified';
        }
    };

    $result = $sanitizer->sanitize([
        'object' => $obj,
        'null_val' => null,
    ]);

    expect($result['object'])->toBe('stringified')
        ->and($result['null_val'])->toBeNull();
});
