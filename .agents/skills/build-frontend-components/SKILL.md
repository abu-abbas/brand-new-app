---
name: build-frontend-components
description: Panduan pembuatan antarmuka pengguna (UI) Vue 3 + TypeScript, penanganan input form (Element Plus Form + Mutation), dan visualisasi data tabular (DataTable + Query) secara terintegrasi dan modular.
---

# Build Frontend Components

## Kapan skill ini dipakai

Dipakai saat membuat halaman/view baru, merancang form input (create/edit data), atau membuat tabel data (DataTable) berfitur pencarian/halaman.

## Langkah Kerja

### 1. Struktur UI & Layouting (Vue 3 + Tailwind CSS)
1. **Module Scope**: Tentukan apakah komponen masuk ke folder modul yang sudah ada (`resources/js/modules/<nama-module>/`) atau membuat modul baru.
2. **Prioritas Pemilihan Komponen (shadcn-vue vs Element Plus)**:
   - **shadcn-vue** (Layout/Struktur): `Card`, `Tabs`, `Sheet`, `Breadcrumbs`, dll. Gunakan CLI `npx shadcn-vue add <component>` untuk memasang secara otomatis.
   - **Element Plus** (Form & Data Kompleks): Gunakan untuk komponen input formulir atau visualisasi data tabular.
   - **Tailwind CSS**: Gunakan kelas-kelas utilitas Tailwind secara konsisten. Hindari pembuatan kode CSS manual/kustom.
3. **Penyajian Status**: Tangani status loading (`isLoading` dari TanStack Query) dengan skeleton/spinner dan status kosong (empty state) jika data kosong.
4. **Notifikasi**: Semua notifikasi/toast atau dialog konfirmasi **wajib** menggunakan **SweetAlert2**, dilarang keras memanggil native `alert()` atau `confirm()`.

### 2. Form Handling (Element Plus Form & Mutation)
1. **Model Binding**: Ikat model formulir menggunakan reactive model yang tipenya berasal dari data generated Orval API (request body type) untuk akurasi kontrak tipe data.
2. **Validasi Formulir**:
   - **Client-side**: Gunakan `rules` validasi Element Plus Form (`FormItem` prop) untuk validasi instan.
   - **Server-side**: Tangkap error respon status `422` dari Laravel FormRequest. Petakan pesan error per-field ke properti error `FormItem` yang bersangkutan.
3. **Submit Logic**: Submit memanggil **mutation** dari `modules/<module>/mutations/` yang di dalamnya memanggil **API Facade** terkait. Setelah sukses, lakukan invalidate query data dan jalankan aksi redirect/tutup dialog.

### 3. Visualisasi Data (DataTable & Query)
1. **Komponen Tabel**: Gunakan komponen `Table` + `Pagination` bawaan Element Plus untuk rendering data list.
2. **State & Query Composable**: State parameter (page, search, sortBy) dideklarasikan sebagai **local reactive state** di komponen Vue (bukan di global Pinia store). Kirim parameter reaktif ini ke composable query TanStack.
3. **Debounce Search**: Berikan penundaan input pencarian (debounce 300-500ms) sebelum memicu pembaruan parameter query ke server untuk mencegah request spam.

## Checklist

- [ ] Tidak mengimpor langsung generated Orval client / Axios instance di dalam component.
- [ ] Penggunaan shadcn-vue dipasang dengan CLI, bukan copy manual.
- [ ] Validasi form client-side (Element Plus Form rules) + server-side (FormRequest Laravel) terintegrasi.
- [ ] Error response API `422` terpetakan sebagai error inline per-field di frontend.
- [ ] Notifikasi sukses/gagal/konfirmasi menggunakan SweetAlert2.
- [ ] Pencarian text-search pada tabel sudah melalui proses debounce.
