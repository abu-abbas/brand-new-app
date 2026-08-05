<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum ImpersonateError: string implements ErrorCode
{
    // --- Business Rules ---

    #[ErrorDefinition(
        message: 'Tidak dapat melakukan impersonate ke akun sendiri.',
        category: ErrorCategory::BUSINESS_RULE,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case SELF_IMPERSONATION_PROHIBITED = 'IMP-BR-001';

    #[ErrorDefinition(
        message: 'Group target wajib dipilih untuk pengguna multi-group.',
        category: ErrorCategory::BUSINESS_RULE,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case TARGET_GROUP_REQUIRED = 'IMP-BR-002';

    #[ErrorDefinition(
        message: 'Pengguna target tidak aktif atau tidak memiliki assignment group yang berlaku.',
        category: ErrorCategory::BUSINESS_RULE,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case TARGET_INACTIVE_OR_NO_ASSIGNMENT = 'IMP-BR-003';

    #[ErrorDefinition(
        message: 'Group yang dipilih tidak valid atau tidak dimiliki oleh pengguna target.',
        category: ErrorCategory::BUSINESS_RULE,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_TARGET_GROUP = 'IMP-BR-004';

    // --- Authorization ---

    #[ErrorDefinition(
        message: 'Anda tidak memiliki hak akses impersonate pengguna.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::MEDIUM,
    )]
    case IMPERSONATE_PERMISSION_DENIED = 'IMP-AUTH-001';

    #[ErrorDefinition(
        message: 'Pengguna target berada di luar scope organisasi yang dapat Anda akses.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::MEDIUM,
    )]
    case TARGET_OUT_OF_SCOPE = 'IMP-AUTH-002';

    #[ErrorDefinition(
        message: 'Level group Anda tidak mencukupi untuk melakukan impersonate ke pengguna target.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::MEDIUM,
    )]
    case INSUFFICIENT_ADMIN_LEVEL = 'IMP-AUTH-003';

    #[ErrorDefinition(
        message: 'Aksi sensitif atau pergantian group tidak diizinkan selama sesi impersonate.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::MEDIUM,
    )]
    case SENSITIVE_ACTION_BLOCKED = 'IMP-AUTH-004';

    // --- Workflow & Session State ---

    #[ErrorDefinition(
        message: 'Sesi impersonate bertingkat tidak diperbolehkan.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case NESTED_IMPERSONATION_PROHIBITED = 'IMP-WF-001';

    #[ErrorDefinition(
        message: 'Anda tidak sedang berada dalam sesi impersonate.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 400,
        severity: ErrorSeverity::LOW,
    )]
    case NOT_IMPERSONATING = 'IMP-WF-002';

    #[ErrorDefinition(
        message: 'Batas waktu sesi impersonate telah berakhir. Identitas Admin Anda telah dipulihkan.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
    )]
    case SESSION_EXPIRED = 'IMP-WF-003';

    #[ErrorDefinition(
        message: 'Akses group target tidak valid lagi. Sesi diputus dan identitas Admin Anda dipulihkan.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
    )]
    case SESSION_INVALIDATED = 'IMP-WF-004';

    // --- Validation ---

    #[ErrorDefinition(
        message: 'Group target harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case TARGET_GROUP_MUST_BE_STRING = 'IMP-VAL-001';

    #[ErrorDefinition(
        message: 'Group target maksimal 100 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case TARGET_GROUP_MAX_LENGTH = 'IMP-VAL-002';
}
