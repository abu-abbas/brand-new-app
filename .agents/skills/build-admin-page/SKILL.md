---
name: build-admin-page
description: Membangun halaman admin CRUD lengkap (list, create, edit, detail, delete) untuk sebuah resource/module. Gunakan saat user minta halaman manajemen data seperti "halaman kelola user", "halaman master produk", dsb.
---

# Build Admin Page

## Kapan skill ini dipakai

Saat user minta halaman admin/manajemen data untuk sebuah resource (contoh: "buatkan halaman kelola kategori produk").

## Langkah Kerja

1. Pastikan **API backend sudah ada** (jika belum, jalankan skill `build-api` terlebih dahulu).
2. Susun struktur halaman di `modules/<module>/pages/`:
   - `IndexPage.vue` — list data + DataTable (lihat skill `build-datatable`) + tombol Create.
   - `FormPage.vue` atau `FormDialog.vue` — form create/edit (lihat skill `build-form`), dipakai bersama untuk create & edit.
   - `DetailPage.vue` (opsional, jika ada halaman detail terpisah dari edit).
3. Gunakan **shadcn-vue** untuk layout halaman (Card, Breadcrumb, Tabs jika ada beberapa section), **Element Plus** untuk form input & tabel jika tidak custom.
4. Aksi hapus data **wajib** konfirmasi dulu lewat **SweetAlert2** (`Swal.fire({ icon: 'warning', ... })`), baru trigger mutation delete.
5. Setelah create/update/delete berhasil: tampilkan SweetAlert2 sukses (toast) dan invalidate query list terkait (`queryClient.invalidateQueries`).
6. Tambahkan pengecekan `auth.can('module.create')`, `auth.can('module.update')`, `auth.can('module.delete')` untuk show/hide tombol aksi.
7. Daftarkan route halaman di `modules/<module>/routes.ts` dengan meta permission yang sesuai.

## Checklist

- [ ] List, Create, Edit, Delete semua berfungsi lewat API Facade (bukan akses langsung Orval/Axios).
- [ ] Delete pakai konfirmasi SweetAlert2.
- [ ] Query di-invalidate setelah mutation sukses supaya list ter-refresh.
- [ ] Permission check sudah dipasang di tombol aksi.
