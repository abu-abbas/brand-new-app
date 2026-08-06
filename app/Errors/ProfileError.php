<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum ProfileError: string implements ErrorCode
{
    #[ErrorDefinition(
        message: 'Halaman harus berupa angka.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PAGE_TYPE = 'PRF-VAL-001';

    #[ErrorDefinition(
        message: 'Halaman minimal bernilai 1.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PAGE_MIN = 'PRF-VAL-002';

    #[ErrorDefinition(
        message: 'Jumlah per halaman harus berupa angka.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_TYPE = 'PRF-VAL-003';

    #[ErrorDefinition(
        message: 'Jumlah per halaman minimal bernilai 1.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_MIN = 'PRF-VAL-004';

    #[ErrorDefinition(
        message: 'Jumlah per halaman maksimal 50.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_MAX = 'PRF-VAL-005';
}
