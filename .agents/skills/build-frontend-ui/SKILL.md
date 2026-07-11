---
name: build-frontend-ui
description: Membangun UI Vue 3 + TypeScript baru (halaman/komponen) mengikuti arsitektur module, prioritas shadcn-vue untuk layout dan Element Plus untuk form. Gunakan saat user minta buat halaman/komponen/UI baru di frontend.
---

# Build Frontend UI

## Kapan skill ini dipakai

Gunakan saat user meminta membuat halaman baru, komponen baru, atau layout UI baru di sisi Vue 3.

## Langkah Kerja

1. **Tentukan module**: cek apakah fitur ini masuk module yang sudah ada di `resources/js/modules/` atau perlu module baru (ikuti rule `folder-convention`).
2. **Pilih komponen dasar** sesuai rule `ui-component-priority`:
   - Struktur halaman/layout -> shadcn-vue (`Card`, `Tabs`, `Sheet`, `Breadcrumb`, dsb). Sebelum menambah component baru, cek skill resmi shadcn-vue (`npx skills add unovue/shadcn-vue` jika belum ter-install) untuk tahu alias import, component yang sudah ada, dan gunakan `shadcn-vue add <component>` alih-alih copy manual.
   - Form kompleks -> Element Plus.
   - Styling -> Tailwind utility class, hindari CSS custom.
3. **Buat Page component** di `modules/<module>/pages/`, gunakan `<script setup lang="ts">`.
4. **Ambil data lewat Query/Mutation**, bukan langsung Axios/Orval (ikuti rule `architecture-layering`). Panggil composable dari `modules/<module>/queries` atau `mutations`.
5. **Notifikasi & error** wajib pakai SweetAlert2 (rule `ui-component-priority`), jangan native alert.
6. **Daftarkan route** di `modules/<module>/routes.ts`, tambahkan meta `permission` jika perlu proteksi.
7. Cek `auth.can(permission)` dari store `permission` untuk conditional render UI (button/menu), tapi tetap ingat backend adalah pengaman utama.

## Checklist Sebelum Selesai

- [ ] Tidak ada import langsung ke Orval client / Axios di component.
- [ ] Tidak ada `alert()`/`confirm()` native.
- [ ] Sudah menggunakan Tailwind, bukan CSS manual.
- [ ] Loading state & empty state sudah ditangani (dari status TanStack Query).
- [ ] Route sudah terdaftar dan permission (jika perlu) sudah diset.
