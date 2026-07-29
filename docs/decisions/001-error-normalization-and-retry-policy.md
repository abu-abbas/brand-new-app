# 001. Error Normalization & Retry Policy

## Status

Accepted

## Context

Backend menggunakan Error Definition Framework (EDF) untuk mendefinisikan error
secara terstruktur lewat PHP Backed Enum + Attribute (`errorCode`, `category`,
`severity`, `httpStatus`). Exception `ApplicationException` ditangani secara
global lewat handler di `bootstrap/app.php`, sehingga Controller tidak perlu
`try/catch` manual dan response error selalu konsisten:

```json
{
  "message": "Pengguna dalam status terkunci dan tidak dapat diubah.",
  "code": "UM-BUS-001",
  "retryable": false
}
```

Di sisi frontend, request-tracing sudah berjalan lewat header `X-Request-Id`
yang dibuat oleh Axios interceptor dan digunakan ulang oleh backend untuk log
context dan response header — sehingga trace end-to-end frontend → backend
sudah konsisten.

Namun, response interceptor Axios belum memanfaatkan kontrak error dari
backend secara konsisten:

- Error `422` (validation) di-passthrough mentah, terpisah dari error lainnya.
- Firewall block (WAF/proxy HTML response) ditangani secara manual dengan
  shape khusus, berbeda dari error EDF biasa.
- Field `retryable` dari backend tidak dibaca konsisten; ada default ganda
  yang saling bertentangan (normalizer vs `DataTable`), yang menyebabkan
  response `429` dengan `retryable: true` tetap diblokir oleh pengecekan
  hardcoded `status < 500` di `DataTable`.
- Belum ada validasi format terhadap `X-Request-Id` yang diterima dari client,
  membuka risiko log forging/log injection.
- Belum ada mekanisme idempotency (`Idempotency-Key`) di backend. API aktif
  hanya terdiri dari endpoint `GET`, sehingga auto-retry pada mutation belum
  relevan.

## Decision

### 1. Validasi `X-Request-Id` di boundary

Request ID dari client divalidasi sebagai UUID v4 sebelum dipakai. ID yang
tidak valid tidak menghasilkan `400`; backend men-generate UUID baru agar
tetap toleran terhadap consumer lama atau rusak.

```php
$incoming = $request->header('X-Request-Id');

$requestId = is_string($incoming)
    && strlen($incoming) === 36
    && Str::isUuid($incoming, 4)
        ? strtolower($incoming)
        : (string) Str::uuid();
```

Hanya `$requestId` tervalidasi yang boleh masuk ke log context dan response
header. Request ID tidak boleh dipakai langsung sebagai filename atau path.

### 2. Satu kontrak error publik: `AppError`

Semua kegagalan request (EDF application error, EDF validation error,
network/timeout error, dan firewall HTML block) dinormalisasi menjadi satu
shape di response interceptor Axios:

```typescript
interface AppError {
  message: string;
  code?: string;
  status?: number;
  retryable: boolean;
  validationErrors?: Record<string, unknown>;
  requestId?: string;
  supportId?: string;
  whatsappUrl?: string;
  retryAfterMs?: number;
  cause?: unknown;
}
```

Semua error, termasuk `422`, keluar sebagai `AppError`. `severity` dari EDF
tetap backend-only untuk menentukan level logging. Satu fungsi
`normalizeAppError(error)` di layer Axios menjadi satu-satunya pintu produksi
`AppError`; tidak ada class hierarchy per kategori error.

Exception EDF untuk kegagalan bisnis dilempar dari Service/Action layer.
Controller hanya mengorkestrasi request sukses dan tidak membentuk response
error manual. Endpoint `testError()` di Controller hanya demonstrasi, bukan
pola implementasi bisnis.

### 3. Retryable: source of truth eksplisit

```text
retryable eksplisit dari backend
  → kalau tidak ada: network/timeout, 429, atau 5xx
```

```typescript
const retryable =
  data?.retryable ??
  (!status || status === 429 || status >= 500);
```

### 4. Retryable bukan auto-retry

Axios interceptor hanya menormalisasi error. Keputusan retry dilakukan di
consumer, misalnya `DataTable` melalui TanStack Query. Auto-retry hanya
diizinkan untuk operasi read-only (`GET`/`HEAD`). Mutation tetap
no-auto-retry sampai ada jaminan idempotency di backend.

Urutan keputusan retry:

```text
retryable eksplisit dari backend
  → fallback network/timeout, 429, atau 5xx
  → consumer memastikan operasi GET/HEAD
  → 429 menghormati Retry-After
  → fallback exponential backoff
```

### 5. Idempotency ditunda, bukan dihilangkan

Membangun idempotency framework sekarang berarti membangun solusi untuk
masalah yang belum ada. Begitu endpoint mutation critical pertama
(submit/approve/payment-like) dibuat, `Idempotency-Key` middleware wajib ada
sebelum endpoint tersebut live. Desain minimalnya menyimpan hasil berdasarkan
kombinasi key + actor + endpoint dan me-replay response yang sama untuk
request duplikat.

## Consequences

Positif:

- Satu shape error untuk form, query, mutation, dan DataTable.
- Response `429` dapat di-retry sesuai kontrak dan `Retry-After`.
- Trace request ID tetap konsisten dan lebih aman terhadap log injection.
- Trigger implementasi idempotency terdokumentasi jelas.

Trade-off:

- Mutation tetap tidak di-auto-retry sampai idempotency tersedia.
- Perilaku `Str::isUuid($value, $version)` perlu diverifikasi ulang saat versi
  Laravel dinaikkan.

## Follow-up

- [x] Validasi UUID v4 di `AssignRequestId`.
- [x] Normalisasi seluruh error, termasuk `422` dan firewall, menjadi
      `AppError`.
- [x] Jadikan `429` retryable secara default dan hormati `Retry-After`.
- [x] Hapus pengecekan `status < 500` yang menimpa kontrak backend.
- [x] Dokumentasikan lokasi throw exception EDF.
- [ ] Implementasikan `Idempotency-Key` sebelum mutation critical pertama
      dibuat.

## Related work

- [Perbaiki mapping EDF untuk `search_fields`](todo/fix-search-fields-edf-mapping.md).
- [Pin TypeScript/vue-tsc dan tambahkan script typecheck](todo/pin-typescript-vue-tsc.md).
