# Fix: EDF mapping untuk `search_fields`

## Status

Done

## Label

`bug`, `backend`, `edf`

## Deskripsi

Full backend test suite gagal pada validasi/mapping error terkait field
`search_fields`. Kegagalan ini ditemukan saat verifikasi implementasi
[ADR-001](../001-error-normalization-and-retry-policy.md), tetapi
tidak berhubungan langsung dengan perubahan ADR tersebut.

## Detail kegagalan

- Mapping EDF untuk `search_fields.array` belum ada.
- Mapping EDF untuk `search_fields.*.string` belum ada.

## Dampak

Backend test suite tidak 100% hijau dan kegagalan berisiko dianggap normal
kalau tidak segera diperbaiki.

## Langkah reproduksi

1. Jalankan `php artisan test`.
2. Amati kegagalan `ErrorDefinitionLinterTest` untuk rule `search_fields`.

## Yang perlu dilakukan

- [x] Identifikasi FormRequest yang mendefinisikan kedua validation rule.
- [x] Tambahkan Error Definition dan mapping untuk `search_fields.array`.
- [x] Tambahkan Error Definition dan mapping untuk
      `search_fields.*.string`.
- [x] Jalankan linter/generator EDF sesuai kontrak project.
- [x] Pastikan full backend test suite kembali hijau.
