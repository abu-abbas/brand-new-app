# 002. Anti Implicit Coalescing on Mutations

## Status

Accepted

## Context

Pada pembuatan atau pembaruan data (mutasi data: `create`, `update`, `delete`, `fill`, `save`), sering kali ditemukan penggunaan operator null coalescing (`??`) atau ternary fallback (`?:`) secara berlebihan atau implisit di controller, service, maupun action layer. Contohnya:

```php
$model->update([
    'status' => $request->input('status') ?? 'draft',
]);
```

Praktik ini menimbulkan beberapa masalah serius terkait integritas data dan arsitektur:

1. **Kerusakan Partial Update (PATCH/PUT)**: Field yang sengaja tidak dikirim di payload partial update bisa secara tidak sengaja ter-overwrite oleh nilai default atau `null`.
2. **Menyamarkan Error Validasi**: Field `required` yang tidak dikirim di payload diselamatkan secara diam-diam oleh fallback, padahal seharusnya request tersebut ditolak oleh FormRequest dengan HTTP status `422`.
3. **Behavior Tidak Deterministik**: Penggunaan fallback berlapis (`$a ?? $b ?? $c`) membuat sumber data utama (*source of truth*) menjadi tidak jelas dan menyulitkan pelacakan bug.
4. **Efek Samping State Global**: Penggunaan fallback ke state global (seperti `Auth::user()->id`) di dalam service layer membuat behavior fungsi berubah-ubah tergantung konteks eksekusi, bukan murni dari data input.

Untuk menegakkan aturan ini secara otomatis dan manual, telah dibangun rule PHPStan (`NoImplicitCoalescingOnMutationsRule.php`) serta scanner otomatis (`app:scan-implicit-coalescing`).

## Decision

1. **Dilarang Implisit Coalescing pada Mutasi Data**: Tidak boleh ada operator `??` atau `?:` pada jalur data yang menuju ke mutasi database (`->update()`, `->save()`, `->fill()`, `::create()`).
2. **Pengecualian Eksplisit**: Jika fallback memang diperlukan secara bisnis, wajib ditandai dengan komentar eksplisit `// @allow-fallback <alasan_bisnis_yang_jelas>`.
3. **Pemeriksaan Key Eksplisit**: Setiap field dari payload yang akan di-assign ke model/database wajib diperiksa keberadaannya secara eksplisit menggunakan `array_key_exists()` atau `$request->has()`, bukan diasumsikan ada atau diselamatkan oleh null coalescing.
4. **Strict Boundaries di FormRequest**: Validasi ketersediaan dan keabsahan field wajib diselesaikan di `FormRequest`.
5. **Determinisme Service/Action**: Service dan Action layer harus bersifat deterministik berdasarkan payload yang diterima.

---

## Review Checklist for Pull Requests

Gunakan checklist ini saat melakukan review PR yang menyentuh pembuatan, pembaruan, atau penghapusan data:

- [ ] Setiap field yang di-assign ke model/DB berasal dari key yang memang dikirim di payload — dicek memakai `array_key_exists()` atau `$request->has()`, bukan diasumsikan ada.
- [ ] Tidak ada `??` / `?:` di jalur data menuju `->update()`, `->save()`, `->fill()`, `::create()`, kecuali ada komentar `// @allow-fallback <alasan>` yang jelas dan alasannya masuk akal secara bisnis.
- [ ] PATCH/PUT partial update: field yang tidak dikirim di payload **tidak** ikut ke-overwrite menjadi `null` atau nilai tebakan.
- [ ] Field required yang tidak ada di payload menyebabkan request gagal di `FormRequest` (422), bukan diselamatkan diam-diam oleh fallback.
- [ ] Tidak ada fallback berlapis (`$a ?? $b ?? $c ?? $d`) — jika ada, tentukan *source of truth* utamanya.
- [ ] Jika reviewer menemukan `@allow-fallback`, pastikan alasannya masuk akal dan direferensikan ke requirement/ticket.

## Tanda Bahaya (Red Flags) Saat Code Review

- `Model::update($request->all())` atau `$model->fill($request->all())` tanpa whitelist / FormRequest validated data.
- Fallback ke `Auth::user()->...` di dalam service/action yang seharusnya hanya memproses data dari payload.
- `$data['field'] ?? null` yang dipakai lagi untuk kondisi `if ($field !== null)` — menyamarkan "field tidak dikirim" menjadi sama dengan "field dikirim eksplisit null".

## Cara Menjalankan Static Analysis & Scan Cepat

### Static Analysis (PHPStan)
```bash
composer analyse
```

### Scan Script Manual / CI
```bash
php artisan app:scan-implicit-coalescing app
```
Periksa hasil `[HIGH]` dan `[MED]` yang berpotensi menyentuh jalur mutasi data.

## Implementation Status

- [x] Buat PHPStan rule `NoImplicitCoalescingOnMutationsRule` untuk mendeteksi `??` / `?:` pada method mutasi.
- [x] Buat Artisan scanner `app:scan-implicit-coalescing` untuk audit manual.
- [x] Integrasikan rule ke `phpstan-coalescing.neon` dan `phpstan.neon`.
- [x] Refaktor seluruh pelanggaran coalescing pada write path (`FeatureService` & `RoleService`).
- [x] Generate `phpstan-baseline.neon` dan pastikan `composer analyse` lulus tanpa error.

---

## Consequences

### Positif
- Integritas data terjaga: partial update (PATCH/PUT) bekerja secara tepat dan deterministik.
- Error validasi tertangkap di boundary (`FormRequest`) sebelum masuk ke logika bisnis.
- Penggunaan fallback memiliki alasan bisnis yang terdokumentasi rapi lewat `@allow-fallback`.

### Trade-off
- Kode mutasi membutuhkan penulisan pengecekan key secara eksplisit (`array_key_exists` / `$request->has()`).
- Pengembang perlu memberikan alasan bisnis yang valid saat menambahkan komentar `@allow-fallback`.
