---
name: error-handling-and-request-tracing
trigger: always_on
description: Standar response envelope error, pemetaan HTTP status code ke aksi frontend, request ID (UUID v4) untuk tracing, serta penanganan batasan sandbox permission AI.
---

# Error Handling & Request Tracing (Wajib)

## 1. Response Envelope — Bentuk Error Wajib Konsisten
Setiap error dari API, apapun jenisnya, **HARUS** memakai bentuk (envelope) yang sama:
```json
{
  "message": "Pesan ringkas, ramah user.",
  "errors": {
    "email": ["Email sudah terdaftar."]
  },
  "error_code": "VALIDATION_ERROR",
  "trace_id": "3f1a9c2e-4b7d-4e2a-9f0a-1c2d3e4f5a6b"
}
```
- `message` — **selalu ada**. Bahasa Indonesia, ramah user, tidak berisi stack trace internal.
- `errors` — **hanya untuk validation error (422)**. Format bawaan Laravel: `field -> array pesan`.
- `error_code` — string konstan. Contoh: `VALIDATION_ERROR`, `DUPLICATE_EMAIL`, `RESOURCE_NOT_FOUND`.
- `trace_id` — **wajib diisi** dari `request_id` yang di-assign oleh middleware.

## 2. Pemetaan HTTP Status Code -> Aksi Frontend
- **400** / **409** / **429** / **403** -> Tampilkan dialog/toast dengan pesan spesifik lewat **SweetAlert2**.
- **401** -> Bersihkan `auth` store, redirect ke halaman login, **tanpa toast** (mencegah spam alert).
- **422** -> Teruskan Promise error untuk di-render sebagai **inline error per-field** di Element Plus Form, **bukan** sekadar toast dialog.
- **500** / **503** -> Tampilkan generic SweetAlert2 "Terjadi kesalahan" beserta potongan kecil kode `trace_id` untuk bahan laporan bug user.

## 3. Request ID Tracing (UUID v4)
- Setiap request masuk **WAJIB** memiliki `request_id` unik berbasis **UUID v4** (`Str::uuid()`).
- Inject `request_id` ke log context Laravel, response header `X-Request-Id`, error envelope `trace_id`, dan teruskan ke Queue Job yang dipicu dari request terkait.

---

# Penanganan Batasan Sandbox AI

1. **Meminta Izin Secara Jelas**: Untuk setiap perintah terminal atau operasi file yang gagal karena permission error, Agent harus memanggil tool `ask_permission` dengan lingkup tersempit yang dibutuhkan.
2. **Batasan Berulang (Anti-Looping)**: Jika perintah/operasi sudah di-approve oleh user namun eksekusi masih gagal karena batasan sandbox (akses path luar, dll), **JANGAN mencobanya berulang-ulang**.
3. **Fallback ke Instruksi Manual**: Jika terhalang batasan sandbox, hentikan percobaan otomatis dan sampaikan secara jujur kepada user. Berikan panduan perintah tertulis yang jelas agar dapat dijalankan secara manual oleh user di mesin lokalnya.
