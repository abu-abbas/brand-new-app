---
name: error-handling-and-request-tracing
trigger: always_on
description: Mewajibkan Error Definition Framework pada setiap module Laravel serta request ID UUID v4 untuk tracing dan logging internal.
---

# Error Definition & Request Tracing (Wajib)

## Source of truth

- Setiap pembuatan atau perubahan module, endpoint, FormRequest, Service/Action, domain exception, atau error response **WAJIB** menjalankan skill `build-error-definitions`.
- PHP/Laravel adalah source of truth. Error didefinisikan sekali melalui string backed enum `ErrorCode` dan attribute `ErrorDefinition`.
- Tambahkan error pada enum konteks bisnis pemiliknya. Jangan membuat enum global atau mengelompokkan error berdasarkan halaman/controller.
- Frontend memakai generated error code dan melakukan branching berdasarkan `code`, bukan `message`.

## Kontrak response publik

Application error:

```json
{
  "message": "Draft sedang dikunci.",
  "code": "SM-WF-001",
  "retryable": false
}
```

Validation error (`422`):

```json
{
  "message": "Nomor surat wajib diisi.",
  "errors": {
    "nomor_surat": [
      {
        "code": "SM-VAL-001",
        "message": "Nomor surat wajib diisi.",
        "retryable": false
      }
    ]
  }
}
```

- HTTP status berasal dari definition; validation selalu `422`.
- Validation tidak memiliki top-level `code` karena satu request dapat mengandung beberapa definition.
- Category, severity, rule name, runtime context, exception class, file, previous exception, dan stack trace **DILARANG** masuk response, termasuk saat debug aktif.
- `ApplicationException` dan `ErrorValidationException` dirender terpusat. Service/Action melempar exception; Controller tidak membuat error JSON manual.
- Exception di luar Error Definition Framework tetap ditangani Laravel kecuali sudah mempunyai mapping eksplisit. Jangan menciptakan kode generik tanpa definition.

## Perilaku frontend

- `400`, `403`, `404`, `409`, `429`: tampilkan pesan spesifik melalui `useConfirmDialog()` bila membutuhkan blocking alert dan bukan inline validation.
- `401`: bersihkan auth store dan redirect ke login tanpa toast.
- `422`: teruskan structured `errors` ke Element Plus Form; jangan ditelan interceptor.
- `500`, `503`: tampilkan pesan generik. Ambil request ID dari header `X-Request-Id` bila perlu referensi laporan.
- Gunakan `retryable` untuk menawarkan retry; jangan menebak dari message.

## Request ID dan logging

- Middleware paling awal menetapkan UUID v4, menyimpan `request_id` pada Laravel log context, dan mengirim header `X-Request-Id`.
- Teruskan request ID ke Queue Job yang dipicu request tersebut.
- Runtime exception context hanya berisi identifier minimum dan tidak dikirim ke client.
- Sanitasi nested context dan redaksi credential/data sensitif. Gunakan `logging.additional_sensitive_keys` untuk key domain.
- Structured log memuat error code, category, severity, retryable, HTTP status, sanitized context, dan exception tanpa duplicate reporting.

## Quality gate

- Jalankan `php artisan error-definition:lint` dan `php artisan error-definition:generate` jika command tersedia.
- Jangan edit generated `error-codes.ts` atau `error-catalog.json` manual.
- Test minimal membuktikan code, status, response shape, validation mapping, dan context internal tidak bocor.
