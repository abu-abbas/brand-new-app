---
name: write-tests
description: Menulis test otomatis (Pest/PHPUnit untuk backend, Vitest untuk frontend, Playwright untuk E2E) sesuai strategi testing project. Gunakan saat user minta tambahkan test untuk fitur/perubahan tertentu.
---

# Write Tests

## Kapan skill ini dipakai

Saat user minta menambahkan test untuk fitur baru atau perubahan kode, atau setelah menyelesaikan skill `build-api`/`build-frontend-ui`.

## Langkah Kerja

Tentukan jenis test yang dibutuhkan mengikuti rule `testing-strategy`:

### Backend — Pest/PHPUnit

1. Buat feature test untuk endpoint API baru (`tests/Feature/<Resource>Test.php`):
   - Test happy path (berhasil dengan data valid).
   - Test validasi gagal (data invalid -> `422`).
   - Test otorisasi (`403` untuk user tanpa permission, `401` untuk unauthenticated).
2. Gunakan Laravel factory untuk data dummy, jangan hardcode data di banyak test.
3. Untuk business logic murni (Service/Action), buat unit test terpisah tanpa perlu HTTP request penuh.

### Frontend — Vitest

1. Test composable (Query/Mutation, API Facade) dengan mocking response API.
2. Test component penting (form validation, conditional render berdasarkan permission) menggunakan `@vue/test-utils`.

### E2E — Playwright

1. Hanya untuk alur krusial (login, submit form penting, checkout, dsb) — tidak perlu untuk setiap perubahan kecil (ikuti rule `testing-strategy`).
2. Fokus pada skenario end-to-end nyata: user login -> navigasi -> isi form -> submit -> verifikasi hasil.

## Checklist

- [ ] Test mencakup happy path + minimal 1 edge case (validasi/otorisasi).
- [ ] Backend test pakai factory, tidak hardcode data berulang.
- [ ] Jenis test yang ditambahkan sesuai risiko perubahan (tidak berlebihan, tidak kurang).
