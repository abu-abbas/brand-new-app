<?php

namespace Tests\Fixtures\ErrorDefinition;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum ManualValidationError: string implements ErrorCode
{
    #[ErrorDefinition(
        message: ':attribute wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case FIELD_UJI_REQUIRED = 'TEST-VAL-100';

    #[ErrorDefinition(
        message: 'Username sudah digunakan.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_TAKEN = 'TEST-VAL-101';
}
