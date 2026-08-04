<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum UserManagementError: string implements ErrorCode
{
    // --- Validation Errors ---

    #[ErrorDefinition(
        message: 'Halaman harus berupa angka.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PAGE_TYPE = 'UM-VAL-001';

    #[ErrorDefinition(
        message: 'Halaman minimal bernilai 1.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PAGE_MIN = 'UM-VAL-002';

    #[ErrorDefinition(
        message: 'Jumlah per halaman harus berupa angka.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_TYPE = 'UM-VAL-003';

    #[ErrorDefinition(
        message: 'Jumlah per halaman minimal bernilai 1.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_MIN = 'UM-VAL-004';

    #[ErrorDefinition(
        message: 'Jumlah per halaman maksimal 100.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_PER_PAGE_MAX = 'UM-VAL-005';

    #[ErrorDefinition(
        message: 'Pencarian harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SEARCH_TYPE = 'UM-VAL-006';

    #[ErrorDefinition(
        message: 'Pencarian maksimal 100 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SEARCH_MAX = 'UM-VAL-007';

    #[ErrorDefinition(
        message: 'Kolom pengurutan harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SORT_BY_TYPE = 'UM-VAL-008';

    #[ErrorDefinition(
        message: 'Kolom pengurutan tidak valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SORT_BY = 'UM-VAL-009';

    #[ErrorDefinition(
        message: 'Arah pengurutan harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SORT_DIRECTION_TYPE = 'UM-VAL-010';

    #[ErrorDefinition(
        message: 'Arah pengurutan harus asc atau desc.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SORT_DIRECTION = 'UM-VAL-011';

    #[ErrorDefinition(
        message: 'Filter aktif harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_ACTIVE_TYPE = 'UM-VAL-012';

    #[ErrorDefinition(
        message: 'Filter aktif harus bernilai true, false, 1, atau 0.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_ACTIVE_VALUE = 'UM-VAL-013';

    #[ErrorDefinition(
        message: 'Kolom pencarian harus berupa daftar.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SEARCH_FIELDS_TYPE = 'UM-VAL-014';

    #[ErrorDefinition(
        message: 'Setiap kolom pencarian harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case INVALID_SEARCH_FIELD_TYPE = 'UM-VAL-015';

    #[ErrorDefinition(
        message: 'User ID wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USER_ID_REQUIRED = 'UM-VAL-016';

    #[ErrorDefinition(
        message: 'User ID sudah terdaftar.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USER_ID_ALREADY_EXISTS = 'UM-VAL-017';

    #[ErrorDefinition(
        message: 'Nama pengguna wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_REQUIRED = 'UM-VAL-018';

    #[ErrorDefinition(
        message: 'Password wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_REQUIRED = 'UM-VAL-019';

    #[ErrorDefinition(
        message: 'User ID harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USER_ID_STRING = 'UM-VAL-020';

    #[ErrorDefinition(
        message: 'User ID maksimal 100 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USER_ID_MAX = 'UM-VAL-021';

    #[ErrorDefinition(
        message: 'Nama pengguna harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_STRING = 'UM-VAL-022';

    #[ErrorDefinition(
        message: 'Nama pengguna maksimal 255 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USERNAME_MAX = 'UM-VAL-023';

    #[ErrorDefinition(
        message: 'Format email tidak valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case EMAIL_INVALID = 'UM-VAL-024';

    #[ErrorDefinition(
        message: 'Email maksimal 255 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case EMAIL_MAX = 'UM-VAL-025';

    #[ErrorDefinition(
        message: 'Email wajib diisi untuk pengguna lokal.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case EMAIL_REQUIRED = 'UM-VAL-043';

    #[ErrorDefinition(
        message: 'Email yang Anda masukkan sudah terdaftar.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case EMAIL_UNIQUE = 'UM-VAL-044';

    #[ErrorDefinition(
        message: 'Status aktif wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case IS_ACTIVE_REQUIRED = 'UM-VAL-045';

    #[ErrorDefinition(
        message: 'Jenis autentikasi eksternal wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USE_OTHER_REQUIRED = 'UM-VAL-046';

    #[ErrorDefinition(
        message: 'Password harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_STRING = 'UM-VAL-026';

    #[ErrorDefinition(
        message: 'Password minimal 6 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PASSWORD_MIN = 'UM-VAL-027';

    #[ErrorDefinition(
        message: 'Status aktif harus berupa boolean.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case IS_ACTIVE_BOOLEAN = 'UM-VAL-028';

    #[ErrorDefinition(
        message: 'Auth eksternal harus berupa boolean.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case USE_OTHER_BOOLEAN = 'UM-VAL-029';

    #[ErrorDefinition(
        message: 'Roles harus berupa daftar.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case ROLES_ARRAY = 'UM-VAL-030';

    #[ErrorDefinition(
        message: 'Kode role wajib diisi.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case ROLE_CODE_REQUIRED = 'UM-VAL-031';

    #[ErrorDefinition(
        message: 'Kode role harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case ROLE_CODE_STRING = 'UM-VAL-032';

    #[ErrorDefinition(
        message: 'Role yang dipilih tidak ditemukan.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case ROLE_CODE_EXISTS = 'UM-VAL-033';

    #[ErrorDefinition(
        message: 'Wilayah harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case WILAYAH_STRING = 'UM-VAL-034';

    #[ErrorDefinition(
        message: 'Wilayah maksimal 50 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case WILAYAH_MAX = 'UM-VAL-035';

    #[ErrorDefinition(
        message: 'Unit harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case UNIT_STRING = 'UM-VAL-036';

    #[ErrorDefinition(
        message: 'Unit maksimal 50 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case UNIT_MAX = 'UM-VAL-037';

    #[ErrorDefinition(
        message: 'Pelaksana harus berupa teks.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PELAKSANA_STRING = 'UM-VAL-038';

    #[ErrorDefinition(
        message: 'Pelaksana maksimal 10 karakter.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case PELAKSANA_MAX = 'UM-VAL-039';

    #[ErrorDefinition(
        message: 'Tanggal berlaku dari harus berupa tanggal valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case VALID_FROM_DATE = 'UM-VAL-040';

    #[ErrorDefinition(
        message: 'Tanggal berlaku sampai harus berupa tanggal valid.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case VALID_UNTIL_DATE = 'UM-VAL-041';

    #[ErrorDefinition(
        message: 'Tanggal berlaku sampai harus sama atau setelah tanggal berlaku dari.',
        category: ErrorCategory::VALIDATION,
        httpStatus: 422,
        severity: ErrorSeverity::LOW,
    )]
    case VALID_UNTIL_AFTER_OR_EQUAL = 'UM-VAL-042';

    // --- Business / Workflow Errors ---

    #[ErrorDefinition(
        message: 'Pengguna dalam status terkunci dan tidak dapat diubah.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 409,
        severity: ErrorSeverity::MEDIUM,
        retryable: false
    )]
    case USER_LOCKED = 'UM-BUS-001';

    // --- Not Found Errors ---

    #[ErrorDefinition(
        message: 'Data pengguna tidak ditemukan.',
        category: ErrorCategory::NOT_FOUND,
        httpStatus: 404,
        severity: ErrorSeverity::LOW,
    )]
    case USER_NOT_FOUND = 'UM-NF-001';

    #[ErrorDefinition(
        message: 'Pengguna integrasi tidak dapat dihapus.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 422,
        severity: ErrorSeverity::MEDIUM,
        retryable: false
    )]
    case CANNOT_DELETE_EXTERNAL_USER = 'UM-BUS-002';

    #[ErrorDefinition(
        message: 'Anda tidak dapat menetapkan role dengan level yang lebih tinggi atau setara dengan level Anda.',
        category: ErrorCategory::AUTHORIZATION,
        httpStatus: 403,
        severity: ErrorSeverity::HIGH,
        retryable: false
    )]
    case CANNOT_ASSIGN_HIGHER_ROLE = 'UM-AUTH-001';

    #[ErrorDefinition(
        message: 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 422,
        severity: ErrorSeverity::MEDIUM,
        retryable: false
    )]
    case CANNOT_DEACTIVATE_SELF = 'UM-BUS-003';

    #[ErrorDefinition(
        message: 'Anda tidak dapat menghapus akun Anda sendiri.',
        category: ErrorCategory::WORKFLOW,
        httpStatus: 422,
        severity: ErrorSeverity::MEDIUM,
        retryable: false
    )]
    case CANNOT_DELETE_SELF = 'UM-BUS-004';
}
