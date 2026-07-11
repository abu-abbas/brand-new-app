---
name: build-error-handling-tracing
description: Membangun/memperbarui infrastruktur error handling terpusat dan request ID tracing (UUID v4) di Laravel — middleware, exception handler, custom domain exception, serta interceptor Axios di frontend. Gunakan saat setup awal project atau saat menambah jenis error/exception baru.
---

# Build Error Handling & Request Tracing

## Kapan skill ini dipakai

- Saat **setup awal** infrastruktur error handling project (biasanya sekali di awal, lihat juga workflow `/setup-error-handling`).
- Saat perlu menambah **domain exception baru** (misal `DuplicateEmailException`, `InsufficientStockException`).
- Saat debugging butuh menelusuri request lewat `request_id`/`trace_id` (dipakai berdampingan dengan skill `debug-application`).

## Langkah Kerja

### 1. Middleware Request ID

Buat `app/Http/Middleware/AssignRequestId.php`:

```php
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-Id') ?? (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
```

Daftarkan **paling awal** di global middleware stack (`bootstrap/app.php` pada Laravel 13), sebelum middleware lain yang mungkin melakukan logging.

> Gunakan `Str::uuid()` (UUID v4) — **jangan** `Str::orderedUuid()`/UUID v1 karena membocorkan info mesin. Jika `request_id` juga akan dipakai sebagai primary key tabel audit log (bukan sekadar header), pertimbangkan `Str::ulid()` untuk keuntungan index database yang sortable — tapi untuk sekadar tracing header, UUID v4 sudah cukup.

### 2. Exception Handler Sentral

Di `app/Exceptions/Handler.php`, buat method `render`/`renderable` yang menyeragamkan **semua** exception ke envelope (ikuti rule `error-handling-and-request-tracing`):

```php
public function render($request, Throwable $e)
{
    if ($request->expectsJson()) {
        $traceId = $request->attributes->get('request_id');
        [$status, $errorCode, $message, $errors] = $this->mapException($e);

        return response()->json(array_filter([
            'message' => $message,
            'errors' => $errors,
            'error_code' => $errorCode,
            'trace_id' => $traceId,
        ], fn ($v) => $v !== null), $status);
    }

    return parent::render($request, $e);
}
```

Buat method privat `mapException()` yang memetakan tiap tipe exception (`ValidationException` -> 422, `AuthorizationException` -> 403, `ModelNotFoundException` -> 404, custom domain exception -> status masing-masing, default -> 500 dengan pesan generic).

### 3. Custom Domain Exception

Untuk error bisnis spesifik, buat Exception class sendiri daripada `abort()` tersebar:

```php
class DuplicateEmailException extends Exception
{
    public function errorCode(): string { return 'DUPLICATE_EMAIL'; }
    public function statusCode(): int { return 409; }
}
```

Lempar exception ini dari Service/Action, biarkan Handler sentral yang membentuk response-nya — **jangan** `return response()->json(...)` manual dari dalam Service.

### 4. Propagasi ke Queue Job

Saat dispatch Job dari sebuah request, teruskan `request_id`:

```php
ProcessExportJob::dispatch($data, request()->attributes->get('request_id'));
```

Di dalam Job, panggil `Log::withContext(['request_id' => $this->requestId])` di awal `handle()` (ikuti skill `build-queue-job`).

### 5. Frontend — Axios Interceptor

Buat/perbarui `resources/js/app/http/axios-interceptor.ts` (atau lokasi setup Axios utama) sesuai pemetaan status code di rule `error-handling-and-request-tracing`:

- `401` -> clear auth store, redirect login, tanpa toast.
- `422` -> `Promise.reject(error)` tanpa toast (diserahkan ke form).
- `403/409/429/400` -> SweetAlert2 toast dari `message`.
- `>=500` -> SweetAlert2 generic + tampilkan `trace_id` singkat.
- Simpan `X-Request-Id` terakhir untuk fitur "Lapor Masalah" (opsional, jika ada).

## Checklist

- [ ] Middleware `AssignRequestId` terpasang paling awal di stack.
- [ ] `request_id` memakai UUID v4 (`Str::uuid()`), bukan versi lain.
- [ ] Semua exception (custom & tak terduga) melewati satu Handler sentral, bentuk response konsisten.
- [ ] `trace_id` di response = `request_id` dari middleware.
- [ ] Queue Job yang dipicu dari request meneruskan `request_id`.
- [ ] Axios interceptor menangani 401/422/403/409/429/5xx sesuai pemetaan, tidak ada penanganan error ad-hoc di component.
