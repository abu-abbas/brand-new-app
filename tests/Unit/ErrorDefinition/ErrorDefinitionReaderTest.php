<?php

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\ResolvedErrorDefinition;
use App\Errors\UserManagementError;

it('reads ErrorDefinition attribute from enum case and returns ResolvedErrorDefinition', function () {
    $reader = new ErrorDefinitionReader;
    $resolved = $reader->read(UserManagementError::USER_LOCKED);

    expect($resolved)
        ->toBeInstanceOf(ResolvedErrorDefinition::class)
        ->and($resolved->code)->toBe('UM-BUS-001')
        ->and($resolved->message)->toBe('Pengguna dalam status terkunci dan tidak dapat diubah.')
        ->and($resolved->category->value)->toBe('WORKFLOW')
        ->and($resolved->httpStatus)->toBe(409)
        ->and($resolved->severity->value)->toBe('MEDIUM')
        ->and($resolved->retryable)->toBeFalse();
});

it('returns cached result for subsequent reads of the same enum case', function () {
    $reader = new ErrorDefinitionReader;
    $first = $reader->read(UserManagementError::USER_LOCKED);
    $second = $reader->read(UserManagementError::USER_LOCKED);

    expect($first)->toBe($second);
});

it('returns toPublicArray with only message, code, and retryable', function () {
    $reader = new ErrorDefinitionReader;
    $resolved = $reader->read(UserManagementError::USER_NOT_FOUND);
    $public = $resolved->toPublicArray();

    expect($public)->toHaveKeys(['message', 'code', 'retryable'])
        ->and($public)->not->toHaveKeys(['category', 'severity', 'httpStatus']);
});
