---
name: build-api
description: Membangun endpoint API baru di Laravel 13 (Controller, FormRequest, API Resource, Route, Policy) yang siap di-generate ke OpenAPI lewat Scramble. Gunakan saat user minta buat/ubah endpoint backend.
---

# Build API

## Kapan skill ini dipakai

Saat user minta membuat endpoint API baru atau mengubah endpoint yang sudah ada.

## Langkah Kerja

1. **Route**: daftarkan di `routes/api.php`, kelompokkan per resource dengan middleware `auth:sanctum` dan (jika perlu) `can:` middleware untuk otorisasi (ikuti rule `auth-authorization`).
2. **FormRequest**: buat class FormRequest terpisah untuk validasi input (`php artisan make:request Store<Resource>Request`), jangan validasi inline di Controller.
3. **Policy**: buat/perbarui Policy (`php artisan make:policy <Resource>Policy --model=<Resource>`) untuk aturan otorisasi per aksi (viewAny, view, create, update, delete).
4. **Controller**: hanya orkestrasi — panggil `$this->authorize()`, delegasikan business logic ke **Service/Action class** (jangan taruh logic kompleks langsung di Controller), lalu return **API Resource**.
5. **API Resource**: buat Resource class (`php artisan make:resource <Resource>Resource`) untuk transformasi response, konsisten shape-nya.
6. **Anotasi Scramble**: tambahkan docblock yang jelas (summary, tag, response example) supaya OpenAPI schema yang di-generate akurat (ikuti rule `openapi-first-flow`).
7. Setelah endpoint selesai, lanjut ke skill `generate-openapi` lalu `sync-orval` supaya frontend dapat client type terbaru.

## Checklist

- [ ] Validasi pakai FormRequest, bukan inline di Controller.
- [ ] Otorisasi lewat Policy/Gate, dipanggil eksplisit di Controller/middleware.
- [ ] Response lewat API Resource, bukan return model/array mentah.
- [ ] Docblock Scramble sudah cukup jelas untuk generate OpenAPI yang akurat.
