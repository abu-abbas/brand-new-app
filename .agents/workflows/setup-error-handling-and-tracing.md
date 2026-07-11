---
description: Memasang middleware request ID (UUID v4), exception handler sentral, contoh domain exception, propagasi ke Queue Job, dan interceptor Axios di frontend — semua sekaligus dalam satu alur, sesuai rule `error-handling-and-request-tracing`
---

# Setup Error Handling & Request Tracing

Workflow **sekali jalan** (biasanya di awal project atau saat infrastruktur error handling belum ada) untuk memasang
sistem error handling terpusat + request ID tracing secara lengkap. Panggil dengan `/setup-error-handling-and-tracing`.

## Steps

1. **Backend — Middleware Request ID**
   - Jalankan skill `build-error-handling-tracing`, bagian Middleware Request ID.
   - Pastikan `AssignRequestId` terdaftar **paling awal** di `bootstrap/app.php`.

2. **Backend — Exception Handler Sentral**
   - Lanjutkan skill `build-error-handling-tracing`, bagian Exception Handler.
   - Petakan minimal: `ValidationException` (422), `AuthorizationException` (403), `ModelNotFoundException` (404),
     `AuthenticationException` (401), fallback exception tak terduga (500).
   - Pastikan response `production` **tidak pernah** bocorkan stack trace/pesan teknis.

3. **Backend — Contoh Domain Exception**
   - Buat minimal 1 contoh domain exception (misal `DuplicateEmailException`) mengikuti pola di skill
     `build-error-handling-tracing` bagian 3, sebagai referensi untuk exception-exception berikutnya.

4. **Backend — Queue Job Propagation**
   - Jika project sudah/akan punya Queue Job, pastikan pola dispatch selalu meneruskan `request_id`
     (lihat skill `build-queue-job` dan `build-error-handling-tracing` bagian 4).

5. **Frontend — Axios Interceptor**
   - Buat/perbarui interceptor sentral sesuai pemetaan status code lengkap di rule `error-handling-and-request-tracing`.
   - Pastikan **422 tidak ditelan interceptor** — harus diteruskan ke form (ikuti skill `build-form`).
   - Pastikan tidak ada `alert()`/`confirm()` native — semua lewat SweetAlert2 (rule `ui-component-priority`).

6. **Verifikasi**
   - Trigger manual masing-masing skenario (401, 403, 404, 409, 422, 429, 500) lewat endpoint uji coba, pastikan:
     - Response envelope konsisten (`message`, `errors`, `error_code`, `trace_id`).
     - Header `X-Request-Id` selalu ada di response.
     - Log backend (`storage/logs/laravel.log`) menampilkan `request_id` yang sama dengan `trace_id` di response.
   - Jalankan skill `write-tests` untuk menambahkan test yang memverifikasi bentuk envelope error ini
     (minimal untuk 422 dan 1 domain exception).

7. **Summary**
   - Ringkas ke user (Bahasa Indonesia) file-file yang dibuat/diubah: middleware, Handler, contoh domain exception,
     interceptor Axios, dan test yang ditambahkan.
