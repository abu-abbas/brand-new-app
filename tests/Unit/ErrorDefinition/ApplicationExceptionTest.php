<?php

use App\Core\ErrorDefinition\ErrorDefinitionReader;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Core\ErrorDefinition\ResolvedErrorDefinition;
use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorSeverity;

it('is a final class extending RuntimeException', function () {
    $ref = new ReflectionClass(ApplicationException::class);

    expect($ref->isFinal())->toBeTrue()
        ->and($ref->getParentClass()->getName())->toBe(RuntimeException::class);
});

it('carries ResolvedErrorDefinition and context', function () {
    $definition = new ResolvedErrorDefinition(
        code: 'TEST-001',
        message: 'Test error message.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
        retryable: false,
    );

    $exception = new ApplicationException(
        definition: $definition,
        context: ['user_id' => 42],
    );

    expect($exception->definition)->toBe($definition)
        ->and($exception->context)->toBe(['user_id' => 42])
        ->and($exception->getMessage())->toBe('Test error message.')
        ->and($exception->getCode())->toBe(0); // code selalu 0
});

it('does not expose category, severity, or context in definition toPublicArray', function () {
    $definition = new ResolvedErrorDefinition(
        code: 'TEST-001',
        message: 'Test error.',
        category: ErrorCategory::SYSTEM,
        httpStatus: 500,
        severity: ErrorSeverity::CRITICAL,
        retryable: true,
    );

    $public = $definition->toPublicArray();

    expect($public)->toBe([
        'message' => 'Test error.',
        'code' => 'TEST-001',
        'retryable' => true,
    ]);
});
