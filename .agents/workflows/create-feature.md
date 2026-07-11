---
description: Membangun 1 fitur/module baru secara lengkap: backend API -> generate OpenAPI -> sync Orval -> frontend UI -> testing -> ringkasan akhir ke user
---

# Create Feature

Workflow utama untuk membangun fitur baru end-to-end di project ini. Panggil dengan `/create-feature`.

> Catatan: Laravel project sudah di-generate (scaffold bawaan Laravel 13 sudah ada), jadi workflow ini **TIDAK** mencakup instalasi/inisialisasi project dari nol — langsung mulai dari tahap Backend.

## Steps

1. **Klarifikasi kebutuhan**: Pastikan Agent memahami nama resource/module, field-field data, aturan bisnis (validasi, permission apa saja yang terlibat). Jika belum jelas, tanyakan ke user sebelum lanjut.
2. **Backend**
   - Jalankan skill `build-api` untuk membuat Migration (jika perlu tabel baru), Model, FormRequest, Policy, Controller, API Resource, dan Route.
   - Jika fitur butuh RBAC baru, jalankan juga skill `build-rbac`.
   - Jika ada proses berat/async (kirim email, export, dsb), jalankan skill `build-queue-job`.
   - Jika fitur ini punya error/kondisi khusus (misal duplikat, konflik state), buat domain exception mengikuti skill `build-error-handling-tracing` bagian 3, jangan pakai `abort()` custom di Controller.
3. **Generate OpenAPI**
   - Jalankan skill `generate-openapi` untuk export `docs/openapi.json` dari Scramble.
   - Verifikasi schema endpoint baru sudah benar.
4. **Generate Orval**
   - Jalankan skill `sync-orval` untuk regenerate TypeScript client dari OpenAPI terbaru.
   - Pastikan `vue-tsc --noEmit` lolos.
5. **Frontend**
   - Jalankan skill `build-frontend-ui` dan/atau `build-admin-page` untuk membangun halaman/komponen.
   - Gunakan skill `build-datatable` jika ada tampilan list/tabel, dan `build-form` untuk form create/edit.
   - Pastikan API Facade, Query, Mutation module baru sudah dibuat sesuai rule `folder-convention` & `architecture-layering`.
6. **Testing**
   - Jalankan skill `write-tests` untuk menambahkan test backend (Pest/PHPUnit) dan frontend (Vitest) sesuai rule `testing-strategy`.
7. **Summary**
   - Berikan ringkasan ke user (dalam Bahasa Indonesia, ikuti rule `bahasa-indonesia`) yang mencakup:
     - Endpoint API yang dibuat/diubah.
     - File-file utama yang dibuat/diubah per layer (backend & frontend).
     - Test yang ditambahkan.
     - Hal yang masih perlu dicek manual oleh user (jika ada), misal migration perlu dijalankan, seeder permission baru, dsb.
