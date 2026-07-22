---
name: build-backend-services
description: Panduan setup infrastruktur Error Definition Framework, pelacakan request ID (tracing UUID v4), dan pembuatan sistem antrean (Queue Job & Horizon) terintegrasi pada backend Laravel.
---

# Build Backend Services

## Kapan skill ini dipakai

Dipakai saat mengatur penanganan eror global, mendesain exception kustom, melacak request log dengan Request ID, atau memproses tugas asinkron di latar belakang (background jobs).

## Langkah Kerja

### 1. Request ID Tracing (UUID v4)

1. **AssignRequestId Middleware**: Buat middleware global (`app/Http/Middleware/AssignRequestId.php`) untuk menyisipkan header `X-Request-Id` menggunakan UUID v4 (`Str::uuid()`).
   - Daftarkan **paling awal** di global middleware stack (`bootstrap/app.php`) agar mencakup semua logging.
   - Inject `request_id` ke dalam log context (`Log::withContext(['request_id' => $requestId])`) agar otomatis terekam pada file log.
2. **Propagasi**: Teruskan `request_id` saat meluncurkan job async dari request context agar trace log antar-proses sinkron.

### 2. Penanganan Error Terpusat (Exception Handler)

1. Jalankan skill `build-error-definitions` dan baca reference kontraknya sebelum mengubah exception handling.
2. Daftarkan renderer sentral untuk `ApplicationException` dan `ErrorValidationException`; pertahankan scope exception Laravel lain kecuali ada mapping eksplisit.
3. Lempar `ApplicationException` yang membawa resolved definition dari Service/Action. Hindari exception class satu-per-kondisi dan `return response()->json()` manual.
4. Pastikan response publik hanya memuat `message`, `code`, `retryable`; validation memuat top-level `message` dan structured `errors` per field.
5. Simpan request ID pada header dan log context, bukan body response Error Definition.

### 3. Queue Job & Horizon (Tugas Latar Belakang)

1. **Job Definition**: Buat Job class (`php artisan make:job <NamaJob>`) yang mengimplementasikan interface `ShouldQueue`.
2. **Horizon Config**: Tentukan nama antrean (`queue` name, misal `exports`) agar konkurensi dapat dikelola lewat file konfigurasi `config/horizon.php`.
3. **Logic Delegation**: Delegasikan logika kerja yang berat dari `handle()` ke Service/Action class yang bersangkutan agar mudah diuji secara unit-testing.
4. **Retry & Recovery**: Tentukan properti `$tries` dan `$backoff` untuk penanganan kegagalan otomatis. Sediakan method `failed(Throwable $exception)` untuk mengirimkan alert/log akhir saat job gagal setelah batas retry habis.

## Checklist

- [ ] Middleware `AssignRequestId` dipasang di urutan pertama global stack.
- [ ] Log context mendeteksi dan merekam `request_id` secara otomatis.
- [ ] Renderer menghasilkan kontrak application dan validation error sesuai `build-error-definitions`.
- [ ] Domain failure memakai enum error module, resolved definition, dan status yang tepat.
- [ ] Queue Job didelegasikan ke class Service/Action, bukan inline di handle().
- [ ] Horizon memantau antrean kustom yang dialokasikan.
- [ ] Queue Job menyimpan property `$tries` & `$backoff` serta penanganan `failed()`.
