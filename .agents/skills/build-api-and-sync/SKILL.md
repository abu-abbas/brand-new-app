---
name: build-api-and-sync
description: Alur pembangunan API Laravel 13 (Controller, FormRequest, API Resource, Route, Policy), integrasi Error Definition Framework, generate spec OpenAPI (Scramble), dan sinkronisasi type-safe client frontend (Orval).
---

# Build API & Sync

## Kapan skill ini dipakai

Dipakai saat membuat endpoint API baru di backend atau mengubah endpoint yang sudah ada, kemudian menyelaraskannya ke frontend agar type-safe.

## Langkah Kerja

### 1. Backend API (Laravel 13)

1. **Route**: Daftarkan rute di `routes/api.php`, kelompokkan per resource dengan middleware `auth:sanctum`. Gunakan middleware otorisasi `can:` jika diperlukan.
2. **FormRequest**: Buat class FormRequest terpisah (`php artisan make:request Store<Resource>Request`) untuk validasi input. Hindari validasi inline di Controller.
   - Jalankan skill `build-error-definitions`: gunakan `HasErrorDefinitions` dan petakan setiap rule melalui `errorCodes()`.
3. **Policy**: Buat/perbarui Policy (`php artisan make:policy <Resource>Policy --model=<Resource>`) untuk mendefinisikan otorisasi per aksi (viewAny, view, create, update, delete).
4. **Controller**: Controller bertindak sebagai orkestrator bersih. Panggil `$this->authorize()`, delegasikan logika bisnis ke **Service/Action class** (hindari penulisan logika rumit di controller), lalu kembalikan **API Resource**.
   - Service/Action melempar `ApplicationException` berdasarkan error enum module untuk business failure; jangan membentuk error response manual.
5. **API Resource**: Buat API Resource (`php artisan make:resource <Resource>Resource`) untuk mentransformasi bentuk response agar konsisten.
6. **Anotasi Scramble**: Tulis docblock yang jelas (summary, tag, response example) agar OpenAPI schema terdeteksi dengan tepat oleh Scramble.

### 2. Generate OpenAPI (Scramble)

1. Jalankan perintah ekspor spec OpenAPI dari backend:
   ```bash
   php artisan scramble:export --path=docs/openapi.json
   ```
2. **Validasi hasil `docs/openapi.json`**:
   - Pastikan endpoint baru muncul dengan tag yang sesuai.
   - Pastikan schema request/response body terisi dengan benar (tidak kosong/`{}` atau bertipe `any`). Perbaiki docblock/tipe di controller/resource jika terdeteksi kosong.
   - **Dilarang** mengedit berkas `docs/openapi.json` secara manual.

### 3. Sync TypeScript Client (Orval)

1. Jalankan regenerasi client TypeScript menggunakan Orval:
   ```bash
   npx orval --config orval.config.ts
   ```
2. Cek berkas hasil generate di `resources/js/api/generated/` (jangan pernah mengedit berkas-berkas generated ini secara manual).
3. Jalankan pengecekan tipe statis frontend untuk memastikan tidak ada breaking changes:
   ```bash
   npx vue-tsc --noEmit
   ```
4. Jika ada perubahan schema/breaking change, perbarui **API Facade** terkait di `modules/<module>/api/`, query/mutation composables, dan model form pada komponen Vue yang memakai model tipe tersebut.

## Checklist

- [ ] Validasi menggunakan FormRequest (bukan inline di Controller).
- [ ] Semua validation rule mempunyai Error Definition dan mapping `attribute.rule`.
- [ ] Business failure memakai error enum module dan `ApplicationException`.
- [ ] Otorisasi dipanggil eksplisit melalui Policy/Gate di backend.
- [ ] Output response menggunakan API Resource.
- [ ] Berkas `docs/openapi.json` berhasil diekspor dan divalidasi.
- [ ] Orval berhasil me-regenerate TypeScript client tanpa error type-check.
