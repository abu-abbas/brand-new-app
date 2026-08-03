<?php

namespace App\Errors;

use App\Core\ErrorDefinition\ErrorCategory;
use App\Core\ErrorDefinition\ErrorCode;
use App\Core\ErrorDefinition\ErrorDefinition;
use App\Core\ErrorDefinition\ErrorSeverity;

enum FeatureError: string implements ErrorCode
{
    #[ErrorDefinition(message: 'Halaman harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PAGE_TYPE = 'FEAT-VAL-001';

    #[ErrorDefinition(message: 'Halaman minimal bernilai 1.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PAGE_MIN = 'FEAT-VAL-002';

    #[ErrorDefinition(message: 'Jumlah per halaman harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_TYPE = 'FEAT-VAL-003';

    #[ErrorDefinition(message: 'Jumlah per halaman minimal bernilai 1.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_MIN = 'FEAT-VAL-004';

    #[ErrorDefinition(message: 'Jumlah per halaman maksimal 100.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_PER_PAGE_MAX = 'FEAT-VAL-005';

    #[ErrorDefinition(message: 'Pencarian harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_TYPE = 'FEAT-VAL-006';

    #[ErrorDefinition(message: 'Pencarian maksimal 100 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_MAX = 'FEAT-VAL-007';

    #[ErrorDefinition(message: 'Kolom pencarian harus berupa daftar.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELDS_TYPE = 'FEAT-VAL-008';

    #[ErrorDefinition(message: 'Setiap kolom pencarian harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELD_TYPE = 'FEAT-VAL-009';

    #[ErrorDefinition(message: 'Kolom pencarian tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SEARCH_FIELD = 'FEAT-VAL-010';

    #[ErrorDefinition(message: 'Kolom pengurutan harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_BY_TYPE = 'FEAT-VAL-011';

    #[ErrorDefinition(message: 'Kolom pengurutan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_BY = 'FEAT-VAL-012';

    #[ErrorDefinition(message: 'Arah pengurutan harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_DIRECTION_TYPE = 'FEAT-VAL-013';

    #[ErrorDefinition(message: 'Arah pengurutan harus asc atau desc.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_SORT_DIRECTION = 'FEAT-VAL-014';

    #[ErrorDefinition(message: 'Pilihan data terhapus harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_INCLUDE_DELETED_TYPE = 'FEAT-VAL-015';

    #[ErrorDefinition(message: 'Pilihan data terhapus tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case INVALID_INCLUDE_DELETED = 'FEAT-VAL-016';

    #[ErrorDefinition(message: 'Nama fitur wajib diisi.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_REQUIRED = 'FEAT-VAL-017';

    #[ErrorDefinition(message: 'Nama fitur harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_STRING = 'FEAT-VAL-018';

    #[ErrorDefinition(message: 'Nama fitur maksimal 100 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case NAME_MAX = 'FEAT-VAL-019';

    #[ErrorDefinition(message: 'Alias wajib diisi.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ALIAS_REQUIRED = 'FEAT-VAL-020';

    #[ErrorDefinition(message: 'Alias harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ALIAS_STRING = 'FEAT-VAL-021';

    #[ErrorDefinition(message: 'Alias maksimal 150 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ALIAS_MAX = 'FEAT-VAL-022';

    #[ErrorDefinition(message: 'Alias hanya boleh berisi huruf kecil, angka, dan tanda hubung.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ALIAS_FORMAT = 'FEAT-VAL-023';

    #[ErrorDefinition(message: 'Alias sudah digunakan oleh fitur aktif.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ALIAS_UNIQUE = 'FEAT-VAL-024';

    #[ErrorDefinition(message: 'Tipe fitur wajib dipilih.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case TYPE_REQUIRED = 'FEAT-VAL-025';

    #[ErrorDefinition(message: 'Tipe fitur harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case TYPE_STRING = 'FEAT-VAL-026';

    #[ErrorDefinition(message: 'Tipe fitur tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case TYPE_INVALID = 'FEAT-VAL-027';

    #[ErrorDefinition(message: 'Fitur induk harus berupa alias.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case PARENT_STRING = 'FEAT-VAL-028';

    #[ErrorDefinition(message: 'Alias fitur induk maksimal 150 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case PARENT_MAX = 'FEAT-VAL-029';

    #[ErrorDefinition(message: 'Fitur induk tidak ditemukan atau sudah dihapus.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case PARENT_NOT_FOUND = 'FEAT-VAL-030';

    #[ErrorDefinition(message: 'Menu hanya dapat menjadi child dari fitur bertipe menu.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case MENU_PARENT_INVALID = 'FEAT-VAL-031';

    #[ErrorDefinition(message: 'Route harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ROUTE_STRING = 'FEAT-VAL-032';

    #[ErrorDefinition(message: 'Route maksimal 250 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ROUTE_MAX = 'FEAT-VAL-033';

    #[ErrorDefinition(message: 'Icon harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ICON_STRING = 'FEAT-VAL-034';

    #[ErrorDefinition(message: 'Icon maksimal 50 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ICON_MAX = 'FEAT-VAL-035';

    #[ErrorDefinition(message: 'Urutan harus berupa angka.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ORDER_INTEGER = 'FEAT-VAL-036';

    #[ErrorDefinition(message: 'Urutan minimal bernilai 1.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ORDER_MIN = 'FEAT-VAL-037';

    #[ErrorDefinition(message: 'Urutan maksimal bernilai 32767.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case ORDER_MAX = 'FEAT-VAL-038';

    #[ErrorDefinition(message: 'Deskripsi harus berupa teks.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case DESCRIPTION_STRING = 'FEAT-VAL-039';

    #[ErrorDefinition(message: 'Deskripsi maksimal 100 karakter.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case DESCRIPTION_MAX = 'FEAT-VAL-040';

    #[ErrorDefinition(message: 'Pilihan tampil pada sidebar harus berupa boolean.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case SHOW_ON_SIDEBAR_BOOLEAN = 'FEAT-VAL-041';

    #[ErrorDefinition(message: 'Fitur tidak dapat menjadi induk bagi dirinya sendiri.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case PARENT_SELF = 'FEAT-VAL-042';

    #[ErrorDefinition(message: 'Tanggal awal pembaruan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_FROM_FORMAT = 'FEAT-VAL-043';

    #[ErrorDefinition(message: 'Tanggal akhir pembaruan tidak valid.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_TO_FORMAT = 'FEAT-VAL-044';

    #[ErrorDefinition(message: 'Tanggal akhir pembaruan tidak boleh sebelum tanggal awal.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case UPDATED_AT_TO_BEFORE_FROM = 'FEAT-VAL-045';

    #[ErrorDefinition(message: 'Fitur tidak dapat dipulihkan karena alias sudah digunakan oleh fitur aktif.', category: ErrorCategory::BUSINESS_RULE, httpStatus: 409, severity: ErrorSeverity::LOW)]
    case RESTORE_ALIAS_CONFLICT = 'FEAT-BIZ-001';

    #[ErrorDefinition(message: 'Pilihan pembatasan akses harus berupa boolean.', category: ErrorCategory::VALIDATION, httpStatus: 422, severity: ErrorSeverity::LOW)]
    case IS_RESTRICTED_BOOLEAN = 'FEAT-VAL-046';
}
