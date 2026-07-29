---
name: feature-delivery-standard
trigger: always_on
description: Standar wajib pembangunan feature end-to-end agar UI, API, validasi, generated contract, UX, dan quality gate selalu lengkap serta konsisten.
---

# Feature Delivery Standard (Wajib)

Gunakan aturan ini setiap kali membuat atau mengubah feature/module, termasuk perubahan kecil pada filter,
form, aksi tabel, lifecycle data, atau endpoint.

## 1. Pahami alur nyata sebelum mengubah

1. Telusuri seluruh alur yang terdampak:
   `route -> page -> component -> query/mutation -> API Facade -> generated client -> FormRequest -> controller -> service -> model -> database`.
2. Cari pola dan custom component yang sudah ada sebelum menulis implementasi baru.
3. Tanyakan hanya keputusan bisnis yang material dan tidak dapat ditemukan dari repository. Jangan berhenti
   untuk detail yang aman disimpulkan dari pola project.
4. Jangan membuat UI palsu. Kontrol filter, aksi, atau validasi harus terhubung sampai backend bila datanya
   berasal dari server.

## 2. Backend sebagai source of truth

1. Gunakan migration/model yang mengikuti konvensi tabel existing.
2. Validasi request wajib berada di FormRequest.
3. Jalankan `build-error-definitions`: setiap rule yang dapat gagal wajib mempunyai Error Definition dan
   mapping `attribute.rule`.
4. Controller hanya mengorkestrasi FormRequest, Service/Action, dan API Resource.
5. Query, lifecycle data, serta business rule berada di Service/Action.
6. Business failure memakai `ApplicationException`; jangan membentuk response error manual.
7. Soft delete, restore, uniqueness, parent-child, dan otorisasi harus divalidasi di server, bukan hanya UI.
8. Tambahkan test backend terkecil yang membuktikan happy path, validation/business failure, status HTTP,
   error code, dan tidak bocornya context internal.

## 3. Contract API wajib digenerate

Setelah source Laravel berubah:

1. Jalankan `php artisan error-definition:lint --strict`.
2. Jalankan `php artisan error-definition:generate` bila catalog berubah.
3. Jalankan `npm run generate:api`.
4. Periksa `docs/openapi.json` dan `resources/js/api/generated/` hasil generate.
5. Jangan mengedit artifact generated secara manual.
6. Perbarui API Facade, query, mutation, dan form model memakai tipe hasil Orval.

## 4. Frontend modular dan konsisten

1. Simpan feature di `resources/js/modules/<module>/` dengan page, component, facade, query/mutation, dan
   route sesuai kebutuhan nyata; jangan membuat folder kosong untuk kebutuhan spekulatif.
2. Component Vue tidak boleh memanggil Axios atau generated client secara langsung.
3. Prioritas UI:
   - custom component existing di `components/custom-ui/`;
   - component shadcn-vue existing;
   - tambah shadcn-vue melalui CLI bila belum ada;
   - Element Plus hanya untuk engine kompleks yang memang sudah dipakai project.
4. Tampilan field tetap memakai custom/shadcn-vue. Element Plus boleh dipakai untuk `Form` dan validation
   orchestration tanpa mengganti visual control shadcn-vue.
5. List server memakai custom `DataTable` + TanStack Query. Form mutation wajib invalidate query terkait.
6. Konfirmasi memakai `useConfirmDialog()`. Modal memakai custom `Modal` bila pola module membutuhkannya.
7. Pastikan loading, empty state, error inline `422`, disabled/read-only state, dark mode, responsive layout,
   keyboard/accessibility dasar, overlay stacking, dan viewport pendek ditangani.
8. Jika memperbaiki komponen reusable, perbaiki di shared component dan cek seluruh caller; jangan patch
   satu halaman bila akar masalahnya global.

## 5. Definition of done

Sebelum menyatakan selesai:

1. Format hanya file yang relevan.
2. Jalankan backend test yang terdampak; jalankan full backend suite untuk perubahan lintas layer.
3. Jalankan unit/component test frontend yang terdampak; jalankan full frontend suite untuk perubahan
   shared component.
4. Jalankan `npm run typecheck`.
5. Jalankan `npm run lint -- --quiet`.
6. Jalankan `npm run build`.
7. Jalankan `git diff --check`.
8. Periksa ulang `git status --short` dan diff agar perubahan user tidak tertimpa.
9. Jangan mengklaim sukses bila salah satu gate wajib gagal.
