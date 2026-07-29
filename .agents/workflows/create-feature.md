---
description: Membangun 1 fitur/module baru secara lengkap: backend API -> generate OpenAPI -> sync Orval -> frontend UI -> testing -> ringkasan akhir ke user
---

# Create Feature

Workflow utama untuk membangun fitur baru end-to-end di project ini. Panggil dengan `/create-feature`.

> Catatan: Laravel project sudah di-generate (scaffold bawaan Laravel 13 sudah ada), jadi workflow ini **TIDAK** mencakup instalasi/inisialisasi project dari nol — langsung mulai dari tahap Backend.

## Steps

1. **Discovery**
   - Ikuti rule `feature-delivery-standard`.
   - Telusuri alur existing end-to-end dan inventarisasi custom component yang dapat dipakai ulang.
   - Klarifikasi hanya keputusan bisnis material yang tidak dapat ditemukan dari repository.
2. **Backend**
   - Jalankan skill `build-api-and-sync` untuk membuat Migration (jika perlu), Model, FormRequest, Policy,
     Controller, API Resource, Service/Action, dan Route.
   - Jalankan skill `build-error-definitions` untuk menginventarisasi failure nyata, menambah enum error milik module, memetakan seluruh validation rule, dan memakai `ApplicationException` untuk kegagalan bisnis.
   - Jika fitur butuh RBAC baru, jalankan juga skill `build-rbac-and-security`.
   - Jika ada proses berat/async, jalankan skill `build-backend-services`.
   - Jangan memakai `abort()` atau response JSON manual untuk error module; ikuti renderer sentral dari `build-error-definitions`.
3. **Generate contract**
   - Jalankan EDF lint/generate, `npm run generate:api`, lalu verifikasi OpenAPI dan tipe Orval.
4. **Frontend**
   - Jalankan skill `build-frontend-components` dan `shadcn-vue`.
   - Gunakan custom `DataTable`, `Modal`, `DatePicker`, `Combobox`, dan `ConfirmDialog` yang sudah ada.
   - Gunakan shadcn-vue untuk visual form; Element Plus hanya untuk Form validation orchestration.
   - Pastikan API Facade, Query, Mutation module baru sudah dibuat sesuai rule `folder-convention` & `architecture-layering`.
5. **Testing**
   - Jalankan skill `debug-and-test` untuk menambahkan test backend dan frontend proporsional terhadap risiko.
   - Verifikasi minimal satu validation mapping dan setiap business failure baru mengembalikan `code`, status, serta `retryable` yang benar tanpa membocorkan runtime context.
   - Jalankan seluruh quality gate pada rule `feature-delivery-standard`.
6. **Summary**
   - Berikan ringkasan ke user (dalam Bahasa Indonesia, ikuti rule `bahasa-indonesia`) yang mencakup:
     - Endpoint API yang dibuat/diubah.
     - File-file utama yang dibuat/diubah per layer (backend & frontend).
     - Test yang ditambahkan.
     - Hal yang masih perlu dicek manual oleh user (jika ada), misal migration perlu dijalankan, seeder permission baru, dsb.
