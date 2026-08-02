<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum RoleError: string implements ErrorCode
{
    #[ErrorDefinition(message: 'Halaman harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PAGE_TYPE = 'ROLE-VAL-001';

    #[ErrorDefinition(message: 'Halaman minimal bernilai 1.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PAGE_MIN = 'ROLE-VAL-002';

    #[ErrorDefinition(message: 'Jumlah per halaman harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_TYPE = 'ROLE-VAL-003';

    #[ErrorDefinition(message: 'Jumlah per halaman minimal bernilai 1.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_MIN = 'ROLE-VAL-004';

    #[ErrorDefinition(message: 'Jumlah per halaman maksimal 100.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_MAX = 'ROLE-VAL-005';

    #[ErrorDefinition(message: 'Pencarian harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_TYPE = 'ROLE-VAL-006';

    #[ErrorDefinition(message: 'Pencarian maksimal 100 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_MAX = 'ROLE-VAL-007';

    #[ErrorDefinition(message: 'Kolom pencarian harus berupa daftar.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELDS_TYPE = 'ROLE-VAL-008';

    #[ErrorDefinition(message: 'Setiap kolom pencarian harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELD_TYPE = 'ROLE-VAL-009';

    #[ErrorDefinition(message: 'Kolom pencarian tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELD = 'ROLE-VAL-010';

    #[ErrorDefinition(message: 'Kolom pengurutan harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_BY_TYPE = 'ROLE-VAL-011';

    #[ErrorDefinition(message: 'Kolom pengurutan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_BY = 'ROLE-VAL-012';

    #[ErrorDefinition(message: 'Arah pengurutan harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_DIRECTION_TYPE = 'ROLE-VAL-013';

    #[ErrorDefinition(message: 'Arah pengurutan harus asc atau desc.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_DIRECTION = 'ROLE-VAL-014';

    #[ErrorDefinition(message: 'Pilihan data terhapus harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_INCLUDE_DELETED_TYPE = 'ROLE-VAL-015';

    #[ErrorDefinition(message: 'Pilihan data terhapus tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_INCLUDE_DELETED = 'ROLE-VAL-016';

    #[ErrorDefinition(message: 'Kode group wajib diisi.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case CODE_REQUIRED = 'ROLE-VAL-017';

    #[ErrorDefinition(message: 'Kode group harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case CODE_STRING = 'ROLE-VAL-018';

    #[ErrorDefinition(message: 'Kode group maksimal 100 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case CODE_MAX = 'ROLE-VAL-019';

    #[ErrorDefinition(message: 'Kode group hanya boleh berisi huruf, angka, strip, dan garis bawah.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case CODE_FORMAT = 'ROLE-VAL-020';

    #[ErrorDefinition(message: 'Kode group sudah digunakan oleh group aktif.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case CODE_UNIQUE = 'ROLE-VAL-021';

    #[ErrorDefinition(message: 'Nama group wajib diisi.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_REQUIRED = 'ROLE-VAL-022';

    #[ErrorDefinition(message: 'Nama group harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_STRING = 'ROLE-VAL-023';

    #[ErrorDefinition(message: 'Nama group maksimal 255 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_MAX = 'ROLE-VAL-024';

    #[ErrorDefinition(message: 'Pilihan butuh region harus berupa boolean.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NEED_REGION_BOOLEAN = 'ROLE-VAL-025';

    #[ErrorDefinition(message: 'Pilihan butuh unit harus berupa boolean.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NEED_UNIT_BOOLEAN = 'ROLE-VAL-026';

    #[ErrorDefinition(message: 'Pilihan terkunci harus berupa boolean.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case LOCKED_BOOLEAN = 'ROLE-VAL-027';

    #[ErrorDefinition(message: 'Periode aktif harus berupa array.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ACTIVE_PERIODE_ARRAY = 'ROLE-VAL-028';

    #[ErrorDefinition(message: 'Daftar fitur harus berupa array.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case FEATURES_ARRAY = 'ROLE-VAL-029';

    #[ErrorDefinition(message: 'Setiap fitur harus berupa alias teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case FEATURES_ITEM_STRING = 'ROLE-VAL-030';

    #[ErrorDefinition(message: 'Level harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case LEVEL_INTEGER = 'ROLE-VAL-034';

    #[ErrorDefinition(message: 'Level minimal bernilai 0.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case LEVEL_MIN = 'ROLE-VAL-035';

    #[ErrorDefinition(message: 'Level melebihi batas maksimal.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case LEVEL_MAX = 'ROLE-VAL-036';

    #[ErrorDefinition(message: 'Tanggal awal pembaruan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_FROM_FORMAT = 'ROLE-VAL-031';

    #[ErrorDefinition(message: 'Tanggal akhir pembaruan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_TO_FORMAT = 'ROLE-VAL-032';

    #[ErrorDefinition(message: 'Tanggal akhir pembaruan tidak boleh sebelum tanggal awal.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_TO_BEFORE_FROM = 'ROLE-VAL-033';

    #[ErrorDefinition(message: 'Group tidak dapat dipulihkan karena kode sudah digunakan oleh group aktif.', category: ErrorCategory::BUSINESS_RULE, httpStatus: 409, severity: ErrorSeverity::LOW)]
    case RESTORE_CODE_CONFLICT = 'ROLE-BIZ-001';

    #[ErrorDefinition(message: 'Group yang terkunci tidak dapat dihapus.', category: ErrorCategory::BUSINESS_RULE, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ROLE_LOCKED_CANNOT_DELETE = 'ROLE-BIZ-002';

    #[ErrorDefinition(message: 'Kode group yang terkunci tidak dapat diubah.', category: ErrorCategory::BUSINESS_RULE, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ROLE_LOCKED_CANNOT_CHANGE_CODE = 'ROLE-BIZ-003';
}
