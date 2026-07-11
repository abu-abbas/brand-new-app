---
name: generate-openapi
description: Menjalankan proses generate OpenAPI spec dari Laravel menggunakan Scramble dan memvalidasi hasilnya. Gunakan setelah menambah/mengubah endpoint API di backend.
---

# Generate OpenAPI

## Kapan skill ini dipakai

Setelah Controller/FormRequest/API Resource baru dibuat atau diubah (biasanya dipanggil setelah skill `build-api`).

## Langkah Kerja

1. Jalankan command export Scramble, contoh:
   ```bash
   php artisan scramble:export --path=docs/openapi.json
   ```
   (sesuaikan nama command dengan versi Scramble yang terpasang di project — cek `composer.json` / `php artisan list` jika berbeda).
2. **Validasi hasil `docs/openapi.json`**:
   - Pastikan endpoint baru muncul dengan tag yang sesuai.
   - Pastikan request body schema dan response schema tidak kosong/`{}` — jika kosong, berarti anotasi docblock di Controller/Resource kurang lengkap, perbaiki di sumbernya (ikuti rule `openapi-first-flow`), lalu generate ulang.
3. **Jangan edit `docs/openapi.json` secara manual** (ikuti rule `generated-files-protection`) — semua perbaikan harus lewat kode backend.
4. Setelah OpenAPI valid, lanjut ke skill `sync-orval` untuk regenerate TypeScript client.

## Checklist

- [ ] Command generate berjalan tanpa error.
- [ ] Endpoint baru/ubahan sudah muncul di `docs/openapi.json` dengan schema yang benar.
- [ ] Tidak ada schema kosong/`any` yang seharusnya bertipe jelas.
