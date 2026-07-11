---
name: sync-orval
description: Menjalankan Orval untuk regenerate TypeScript client dari docs/openapi.json terbaru, lalu memverifikasi API Facade masih kompatibel. Gunakan setelah skill generate-openapi.
---

# Sync Orval

## Kapan skill ini dipakai

Setelah `docs/openapi.json` ter-update (setelah skill `generate-openapi`), untuk membuat generated TypeScript client frontend up-to-date.

## Langkah Kerja

1. Jalankan Orval:
   ```bash
   npx orval --config orval.config.ts
   ```
2. Cek output di `resources/js/api/generated/` — pastikan tidak ada error generate (tipe tidak lengkap, endpoint hilang, dsb).
3. Jalankan **type check** frontend:
   ```bash
   vue-tsc --noEmit
   ```
   untuk memastikan tidak ada breaking change di API Facade atau component yang memakai tipe lama.
4. Jika ada **breaking change** (field dihapus/rename, request shape berubah):
   - Perbarui **API Facade** terkait di `modules/<module>/api/`.
   - Perbarui **Query/Mutation composable** yang memakai tipe tersebut.
   - Perbarui **Form model** di component (ikuti skill `build-form`).
5. **Jangan** mengedit file di `resources/js/api/generated/` secara manual untuk "menambal" error — perbaikan harus dari sisi backend + generate ulang (ikuti rule `generated-files-protection`).

## Checklist

- [ ] Orval berhasil generate tanpa error.
- [ ] `vue-tsc --noEmit` lolos tanpa error tipe baru yang tidak disengaja.
- [ ] Jika ada breaking change, API Facade & composable terkait sudah disesuaikan.
