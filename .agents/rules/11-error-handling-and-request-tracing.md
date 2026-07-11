---
name: error-handling-and-request-tracing
trigger: always_on
description: Standar response envelope error, pemetaan HTTP status code ke aksi frontend, dan request ID (UUID v4) untuk tracing yang wajib diikuti di seluruh backend dan frontend.
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

- `message` — **selalu ada**. Bahasa Indonesia, ramah user, tidak berisi stack trace/detail teknis internal.
- `errors` — **hanya untuk validation error (422)**. Format bawaan Laravel: `field -> array pesan`.
- `error_code` — string konstan (bukan sekadar HTTP status number) supaya frontend bisa membuat logic spesifik tanpa parsing teks pesan. Contoh: `VALIDATION_ERROR`, `DUPLICATE_EMAIL`, `RESOURCE_NOT_FOUND`, `INSUFFICIENT_PERMISSION`, `RATE_LIMITED`, `SERVER_ERROR`.
- `trace_id` — **wajib diisi** dari `request_id` yang di-assign oleh middleware (lihat bagian 3). Dipakai untuk korelasi ke log backend saat debugging/laporan bug dari user.

Semua exception (custom maupun tak terduga) **HARUS** melewati satu titik sentral (`app/Exceptions/Handler.php` di Laravel 13, method `render`/`renderable`) yang menyeragamkan bentuk ini — jangan ada Controller yang bikin bentuk response error sendiri-sendiri.

## 2. Pemetaan HTTP Status Code -> Aksi Frontend

| Status  | Makna                                | Sumber Backend                     | Aksi Frontend                                                                                                                                                              |
| ------- | ------------------------------------ | ---------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **400** | Bad request umum                     | Custom exception                   | SweetAlert2 toast error dari `message`                                                                                                                                     |
| **401** | Belum login / session habis          | Middleware `auth:sanctum`          | Redirect ke login + clear `auth` store. **Tanpa toast** (hindari spam saat token expired berulang)                                                                         |
| **403** | Tidak punya izin                     | Policy/Gate deny                   | SweetAlert2 "Anda tidak memiliki akses"                                                                                                                                    |
| **404** | Data tidak ditemukan                 | Model binding gagal / `abort(404)` | SweetAlert2 atau redirect ke halaman not-found, tergantung konteks                                                                                                         |
| **409** | Konflik (duplikat/state tidak valid) | Custom domain exception            | SweetAlert2 dengan `message` spesifik dari backend                                                                                                                         |
| **422** | Validasi gagal                       | FormRequest                        | **Inline error per field** di Element Plus form (map dari `errors`), **bukan** cuma toast                                                                                  |
| **429** | Rate limit                           | Laravel throttle middleware        | SweetAlert2 "Terlalu banyak percobaan, coba lagi nanti"                                                                                                                    |
| **500** | Server error tak terduga             | Unhandled exception                | SweetAlert2 generic "Terjadi kesalahan, coba lagi". **Jangan** tampilkan pesan teknis/stack trace ke user, tapi tampilkan `trace_id` kecil di pesan supaya user bisa lapor |
| **503** | Maintenance / service down           | `app()->down()`                    | Halaman maintenance khusus                                                                                                                                                 |

**Prinsip kunci**: 422 diperlakukan berbeda dari error lain — itu bagian normal alur form (bukan "kesalahan sistem"), jadi wajib jadi inline error per field.

## 3. Request ID — Wajib UUID v4, Di-inject via Middleware

- Setiap request masuk **WAJIB** memiliki `request_id` unik, format **UUID v4** (`Str::uuid()` di Laravel — ini sudah default UUID v4, jangan pakai UUID v1 karena membocorkan MAC address/timestamp mesin).
- Jika request datang dengan header `X-Request-Id` dari luar (load balancer/API gateway/proxy), **pakai ID itu**, jangan generate baru — supaya trace tetap nyambung lintas layer infrastruktur.
- `request_id` **WAJIB** di-inject ke:
  1. **Log context** (`Log::withContext(['request_id' => $requestId])`) — supaya semua log dalam request itu otomatis membawa `request_id` tanpa perlu passing manual di setiap `Log::info()`/`Log::error()`.
  2. **Response header** `X-Request-Id` — supaya frontend bisa membaca dan menyimpannya (untuk fitur "Lapor Masalah").
  3. **Error envelope** sebagai `trace_id` (lihat bagian 1).
  4. **Queue Job** yang di-dispatch dari request tersebut — teruskan `request_id` lewat constructor Job supaya log job async tetap bisa ditelusuri balik ke request pemicunya (lihat skill `build-queue-job`).

## 4. Frontend — Axios Interceptor Sentral

Semua penanganan error HTTP **terpusat di satu Axios interceptor** (bukan diulang di tiap component/composable):

- `401` -> clear `auth` store + redirect login, tanpa toast.
- `422` -> **jangan** ditangani generic di interceptor, teruskan (`Promise.reject`) supaya form yang memetakan `errors` ke field masing-masing (ikuti skill `build-form`).
- `403`, `409`, `429`, `400` -> SweetAlert2 toast dengan `message` dari backend.
- `500`/`503` -> SweetAlert2 generic + (opsional) tampilkan potongan `trace_id` kecil di detail alert untuk keperluan lapor bug.
- Simpan `X-Request-Id` dari response terakhir yang error (misal di state sementara) supaya bisa ditampilkan/dikirim user saat melaporkan masalah.

## Larangan

- **DILARANG** membuat bentuk response error custom yang berbeda dari envelope di atas di Controller manapun.
- **DILARANG** menampilkan stack trace, nama class exception, atau query SQL mentah ke user (boleh muncul di `storage/logs/laravel.log`, tidak boleh di response API saat environment `production`).
- **DILARANG** melewatkan Queue Job tanpa meneruskan `request_id`-nya jika job tersebut dipicu langsung dari sebuah HTTP request.
