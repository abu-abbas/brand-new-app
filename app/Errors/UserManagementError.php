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
}
