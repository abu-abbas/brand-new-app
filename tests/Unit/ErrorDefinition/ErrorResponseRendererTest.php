<?php

use App\Core\ErrorDefinition\ErrorResponseRenderer;
use App\Core\ErrorDefinition\Exceptions\ApplicationException;
use App\Core\ErrorDefinition\Exceptions\ErrorValidationException;
use App\Core\ErrorDefinition\ResolvedErrorDefinition;
use App\Core\ErrorDefinition\ResolvedValidationError;
use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorSeverity;

it('renders ApplicationException with only message, code, retryable and correct HTTP status', function () {
    $definition = new ResolvedErrorDefinition(
        code: 'TEST-WF-001',
        message: 'Draft sedang dikunci.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
        retryable: false,
    );

    $exception = new ApplicationException(
        definition: $definition,
        context: ['sensitive_data' => 'should_not_appear'],
    );

    $renderer = new ErrorResponseRenderer();
    $response = $renderer->application($exception);

    expect($response->getStatusCode())->toBe(409);

    $data = $response->getData(true);
    expect($data)->toBe([
        'message' => 'Draft sedang dikunci.',
        'code' => 'TEST-WF-001',
        'retryable' => false,
    ]);

    // Context, category, severity TIDAK boleh bocor ke response
    expect($data)->not->toHaveKey('context')
        ->and($data)->not->toHaveKey('category')
        ->and($data)->not->toHaveKey('severity');
});

it('renders ErrorValidationException with structured errors grouped by attribute', function () {
    $def1 = new ResolvedErrorDefinition(
        code: 'TEST-VAL-001',
        message: 'Field wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
        retryable: false,
    );

    $def2 = new ResolvedErrorDefinition(
        code: 'TEST-VAL-002',
        message: 'Format tidak valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
        retryable: false,
    );

    $validationErrors = [
        new ResolvedValidationError(
            attribute: 'email',
            rule: 'required',
            definition: $def1,
            message: $def1->message,
        ),
        new ResolvedValidationError(
            attribute: 'email',
            rule: 'email',
            definition: $def2,
            message: $def2->message,
        ),
    ];

    $exception = new ErrorValidationException(
        validator: validator([], []),
        validationErrors: $validationErrors,
    );

    $renderer = new ErrorResponseRenderer();
    $response = $renderer->validation($exception);

    expect($response->getStatusCode())->toBe(422);

    $data = $response->getData(true);
    expect($data)->toHaveKey('message')
        ->and($data)->toHaveKey('errors')
        ->and($data['errors']['email'])->toHaveCount(2)
        ->and($data['errors']['email'][0])->toBe([
            'code' => 'TEST-VAL-001',
            'message' => 'Field wajib diisi.',
            'retryable' => false,
        ]);
});
