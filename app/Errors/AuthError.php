<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum AuthError: string implements ErrorCode
{
    #[ErrorDefinition(
        message: 'Username wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_REQUIRED = 'AUTH-VAL-001';

    #[ErrorDefinition(
        message: 'Username harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_STRING = 'AUTH-VAL-002';

    #[ErrorDefinition(
        message: 'Username maksimal 255 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_MAX = 'AUTH-VAL-003';

    #[ErrorDefinition(
        message: 'Password wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_REQUIRED = 'AUTH-VAL-004';

    #[ErrorDefinition(
        message: 'Password harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_STRING = 'AUTH-VAL-005';

    #[ErrorDefinition(
        message: 'Password maksimal 255 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_MAX = 'AUTH-VAL-006';

    #[ErrorDefinition(
        message: 'Kode keamanan wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_REQUIRED = 'AUTH-VAL-007';

    #[ErrorDefinition(
        message: 'Kode keamanan harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_STRING = 'AUTH-VAL-008';

    #[ErrorDefinition(
        message: 'Kode keamanan maksimal 10 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_MAX = 'AUTH-VAL-009';

    #[ErrorDefinition(
        message: 'Kode keamanan tidak valid atau sudah kedaluwarsa.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_INVALID = 'AUTH-VAL-010';

    #[ErrorDefinition(
        message: 'Kunci kode keamanan wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_KEY_REQUIRED = 'AUTH-VAL-011';

    #[ErrorDefinition(
        message: 'Kunci kode keamanan harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_KEY_STRING = 'AUTH-VAL-012';

    #[ErrorDefinition(
        message: 'Kunci kode keamanan tidak valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case CAPTCHA_KEY_SIZE = 'AUTH-VAL-013';

    #[ErrorDefinition(
        message: 'Username atau password tidak valid.',
        category: ErrorCategory::AUTHENTICATION,
        httpStatus: 401,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_CREDENTIALS = 'AUTH-LOGIN-001';

    #[ErrorDefinition(
        message: 'Terlalu banyak percobaan login. Coba lagi dalam satu menit.',
        category: ErrorCategory::AUTHENTICATION,
        httpStatus: 429,
        severity: ErrorSeverity::LOW,
        retryable: true,
    )]
    case TOO_MANY_ATTEMPTS = 'AUTH-LOGIN-002';

    #[ErrorDefinition(
        message: 'Anda belum memiliki group, silakan hubungi admin untuk informasi lebih lanjut.',
        category: ErrorCategory::AUTHENTICATION,
        httpStatus: 403,
        severity: ErrorSeverity::LOW,
    )]
    case NO_ROLE_ASSIGNED = 'AUTH-LOGIN-003';

    #[ErrorDefinition(
        message: 'Anda tidak memiliki hak akses untuk melakukan tindakan ini.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::LOW,
    )]
    case UNAUTHORIZED_ACTION = 'AUTH-ACC-001';

    #[ErrorDefinition(
        message: 'Group wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case GROUP_REQUIRED = 'AUTH-VAL-014';

    #[ErrorDefinition(
        message: 'Group harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case GROUP_STRING = 'AUTH-VAL-015';

    #[ErrorDefinition(
        message: 'Group yang dipilih tidak valid atau Anda tidak memiliki akses ke group tersebut.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_GROUP = 'AUTH-ACC-002';

    #[ErrorDefinition(
        message: 'Format field ingat pilihan tidak valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case REMEMBER_BOOLEAN = 'AUTH-VAL-016';

    #[ErrorDefinition(
        message: 'Sesi tidak valid atau sudah berakhir.',
        category: ErrorCategory::AUTHENTICATION,
        httpStatus: 401,
        severity: ErrorSeverity::LOW,
    )]
    case UNAUTHENTICATED = 'AUTH-ACC-003';
}
