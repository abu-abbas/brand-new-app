<?php

use App\Support\Obfuscator;

test('it encodes integer to obfuscated string of min length 5', function () {
    $code1 = Obfuscator::encode(1, 'test-salt');
    $code2 = Obfuscator::encode(2, 'test-salt');
    $code1000 = Obfuscator::encode(1000, 'test-salt');

    expect(strlen($code1))->toBeGreaterThanOrEqual(5);
    expect(strlen($code2))->toBeGreaterThanOrEqual(5);
    expect(strlen($code1000))->toBeGreaterThanOrEqual(5);

    expect($code1)->not->toBe($code2);
});

test('it decodes obfuscated string back to original integer', function () {
    $salt = 'my-secret-salt-2026';

    for ($id = 1; $id <= 100; $id++) {
        $encoded = Obfuscator::encode($id, $salt);
        $decoded = Obfuscator::decode($encoded, $salt);

        expect($decoded)->toBe($id, "Failed for ID {$id} with hash {$encoded}");
    }
});

test('it returns null for invalid hash strings', function () {
    expect(Obfuscator::decode('invalid!!!', 'salt'))->toBeNull();
    expect(Obfuscator::decode('', 'salt'))->toBeNull();
});
