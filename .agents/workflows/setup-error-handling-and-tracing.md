---
description: Memasang Error Definition Framework, middleware request ID UUID v4, renderer/reporter sentral, propagasi Queue Job, dan interceptor Axios sesuai rule workspace.
---

# Setup Error Handling & Request Tracing

Workflow **sekali jalan** (biasanya di awal project atau saat infrastruktur error handling belum ada) untuk memasang
sistem error handling terpusat + request ID tracing secara lengkap. Panggil dengan `/setup-error-handling-and-tracing`.

## Steps

1. **Backend — Kontrak Error Definition**
   - Jalankan skill `build-error-definitions` dan baca `references/contracts.md`.
   - Implementasikan core types, reader, renderer, reporter, validation integration, linter, dan generator hanya jika belum tersedia di project/dependency.

2. **Backend — Middleware Request ID**
   - Jalankan skill `build-backend-services`, bagian Middleware Request ID.
   - Pastikan `AssignRequestId` terdaftar **paling awal** di `bootstrap/app.php`.

3. **Backend — Renderer dan Reporter Sentral**
   - Daftarkan renderer `ApplicationException` dan `ErrorValidationException` untuk request JSON.
   - Daftarkan structured reporter `ApplicationException` dan cegah duplicate reporting.
   - Biarkan exception Laravel lain mengikuti handler Laravel kecuali ada Error Definition dan mapping eksplisit.
   - Pastikan response di semua environment tidak pernah membocorkan category, severity, runtime context, exception detail, atau stack trace.

4. **Backend — Error Module**
   - Buat satu enum contoh pada konteks bisnis nyata yang sudah ada; jangan membuat dummy exception production.
   - Lempar `ApplicationException` dengan resolved definition dari Service/Action.
   - Terapkan `HasErrorDefinitions` dan `errorCodes()` pada FormRequest module tersebut.

5. **Backend — Queue Job Propagation**
   - Jika project sudah/akan punya Queue Job, pastikan pola dispatch selalu meneruskan `request_id`
     (lihat skill `build-backend-services`).

6. **Frontend — Axios Interceptor**
   - Buat/perbarui interceptor sentral sesuai pemetaan status code lengkap di rule `error-handling-and-request-tracing`.
   - Pastikan **422 tidak ditelan interceptor** — harus diteruskan ke form (ikuti skill `build-form`).
   - Gunakan `code` dan `retryable`; ambil tracing dari header `X-Request-Id`, bukan body.
   - Pastikan tidak ada `alert()`/`confirm()` native — konfirmasi/blocking alert lewat `useConfirmDialog()` (rule `ui-component-priority`).

7. **Verifikasi**
   - Trigger application error dan validation error nyata, lalu pastikan:
     - Application response persis `message`, `code`, `retryable`.
     - Validation response berisi top-level `message` dan structured `errors` per field tanpa top-level `code`.
     - Header `X-Request-Id` selalu ada di response.
     - Log backend menampilkan `request_id` yang sama dengan header dan context sudah disanitasi.
     - Response tidak memuat runtime context atau detail exception.
   - Jalankan `php artisan error-definition:lint` dan `php artisan error-definition:generate` bila command tersedia.
   - Tambahkan test minimal untuk 422 dan satu application error.

8. **Summary**
   - Ringkas file core framework, enum module, FormRequest mapping, middleware, renderer/reporter, generated artifact, interceptor, dan test yang dibuat/diubah.
