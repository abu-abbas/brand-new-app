---
description: Versi lebih ringkas dari `/create-feature`, khusus untuk resource CRUD standar (contoh: master data kategori, tag, satuan, dsb) tanpa business logic kompleks
---

## Steps

1. Konfirmasi ke user: nama resource, field-field, apakah butuh permission khusus atau ikut permission module induk.
2. Call `/create-feature` sebagai basis, tapi lewati langkah `build-queue-job` (tidak relevan untuk CRUD sederhana).
3. Backend: jalankan skill `build-api` (Migration + Model + FormRequest + Policy + Controller + Resource + Route), lalu `build-error-definitions` untuk enum error module dan mapping setiap validation rule.
4. Generate OpenAPI (skill `generate-openapi`) lalu sync Orval (skill `sync-orval`).
5. Frontend: jalankan skill `build-admin-page` (otomatis mencakup `build-datatable` + `build-form`).
6. Testing: jalankan skill `write-tests`, minimal feature test untuk CRUD (index, store, update, destroy) + validasi terstruktur + otorisasi.
7. Summary singkat ke user: endpoint yang tersedia, halaman admin yang bisa diakses, permission key yang dipakai.
