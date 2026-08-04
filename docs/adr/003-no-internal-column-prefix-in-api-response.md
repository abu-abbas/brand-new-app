# 003. No Internal Column Prefix in API Response

## Status

Accepted

## Context

Database pada proyek ini menggunakan konvensi prefix penamaan kolom berdasarkan tipe data, antara lain:
- `v_` : varchar / string (misal: `v_name`, `v_code`, `v_alias`)
- `dt_` : datetime / timestamp (misal: `dt_created_at`, `dt_deleted_at`)
- `b_` : boolean (misal: `b_is_active`, `b_locked`, `b_need_region`)
- `si_` : small integer (misal: `si_order`)
- `e_` : enum (misal: `e_type`)
- `i_` : integer / ID (misal: `i_id`, `i_level`)
- `ti_` : tiny integer
- `bi_` : big integer

Penggunaan prefix ini bertujuan untuk mempermudah identifikasi tipe data pada layer database dan model Eloquent. Namun, **prefix internal database ini DILARANG KERAS bocor ke response API publik/frontend**.

Bocornya prefix ke response API menyebabkan beberapa masalah:
1. **Mengekspos Skema Database**: Detail implementasi internal database menjadi konsumsi publik/frontend.
2. **Keterikatan Kuat (Tight Coupling)**: Perubahan nama kolom database akan langsung merusak kontrak API (*breaking change*) jika frontend bergantung pada key ber-prefix.
3. **Standar API Tidak Bersih**: Frontend terpaksa mengolah key yang tidak standar (seperti `v_name` alih-alih `name`, `b_is_active` alih-alih `is_active`).

Oleh karena itu, setiap penyiapan data response di `JsonResource` (API Resource) wajib melakukan transposisi nama field menjadi nama yang bersih (*clean API contract*).

## Decision

1. **Dilarang Menggunakan Key Ber-Prefix di API Resource**: Setiap key array yang di-return oleh method `toArray()` pada class `JsonResource` tidak boleh diawali dengan prefix kolom database (`v_`, `dt_`, `b_`, `si_`, `e_`, `i_`, `ti_`, `bi_`).
2. **Penangan di API Resource Layer**: Transposisi dari field ber-prefix menjadi key API bersih dilakukan secara eksplisit di `JsonResource`.
   ```php
   // ❌ Salah — prefix bocor ke response API
   return [
       'v_name' => $this->v_name,
       'b_is_active' => $this->b_is_active,
   ];

   // ✅ Benar — key bersih, value mengambil dari properti model
   return [
       'name' => toTitleCase($this->v_name),
       'is_active' => (bool) $this->b_is_active,
   ];
   ```
3. **Pengecualian Eksplisit**: Jika ada kebutuhan khusus (misal kompatibilitas legacy API), key ber-prefix wajib diberi komentar eksplisit `// @allow-raw-key <alasan_bisnis>` pada baris sebelum key tersebut.
4. **Penegakan Otomatis via PHPStan**: Dibuat rule PHPStan `NoInternalColumnPrefixInResourceRule.php` yang akan mendeteksi dan menolak key ber-prefix di method `toArray()` milik class `*Resource`.

---

## Review Checklist for Pull Requests

- [ ] Semua key array pada method `toArray()` di `*Resource` sudah bebas dari prefix database (`v_`, `dt_`, `b_`, `si_`, `e_`, `i_`, `ti_`, `bi_`).
- [ ] Apabila ditemukan key ber-prefix di response API, lakukan refactoring pada API Resource terkait.
- [ ] Jika ada key yang terpaksa dipertahankan ber-prefix, pastikan terdapat komentar `// @allow-raw-key <alasan>` di atasnya.

---

## Static Analysis & Verification

### Static Analysis (PHPStan)
```bash
composer analyse
```

### Scan Script Manual / CI
```bash
php artisan app:scan-resource-column-prefix app
```

## Implementation Status

- [x] Buat PHPStan rule `NoInternalColumnPrefixInResourceRule` untuk mendeteksi key array ber-prefix kolom DB di `JsonResource`.
- [x] Buat Artisan scanner `app:scan-resource-column-prefix` untuk audit manual.
- [x] Mendaftarkan rule ke `phpstan-resource-keys.neon` dan `phpstan.neon`.
- [x] Verifikasi seluruh `JsonResource` (0 kebocoran key).
- [x] Pastikan `composer analyse` lulus 100% tanpa error.

---

## Consequences

### Positif
- Kontrak API bersih dan terisolasi dari skema/tabel database internal.
- Perubahan nama kolom database tidak merusak antarmuka frontend selama transposisi di API Resource diperbarui.
- Terhindar dari insiden kebocoran skema DB secara eksplisit di Scramble OpenAPI / TypeScript generated client.

### Trade-off
- Developer harus menulis pemetaan key secara manual di setiap `JsonResource`.
