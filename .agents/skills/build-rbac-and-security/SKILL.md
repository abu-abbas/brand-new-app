---
name: build-rbac-and-security
description: Alur pembangunan sistem otorisasi Role-Based Access Control (RBAC) terintegrasi pada backend (Policy/Gate) dan UI frontend (permission store, auth.can), serta perancangan halaman administrasi CRUD yang terproteksi.
---

# Build RBAC & Security

## Kapan skill ini dipakai

Dipakai saat menambahkan role baru, permission baru, membatasi hak akses fitur, atau merancang Halaman Admin manajemen data (CRUD) yang membutuhkan proteksi hak akses.

## Langkah Kerja

### 1. Otorisasi Backend (Laravel - Source of Truth)
1. **Format Permission**: Gunakan string konsisten dengan format `<nama-module>.<nama-aksi>` (contoh: `user.view`, `user.create`, `user.update`, `user.delete`).
2. **Policy Assignment**: Hubungkan model dengan Policy (`php artisan make:policy <Model>Policy`). Pengecekan permission didalam method Policy memanggil helper otorisasi user (hindari hardcode nama role).
3. **Route & Controller Protection**: Gunakan middleware `can:<permission>` pada route Laravel atau panggil `$this->authorize('<permission>')` di awal method Controller.
4. **Auth Payload**: Sertakan list permission milik user yang sedang aktif pada muatan payload login/session profile (misalnya melalui `MeResource`).

### 2. Otorisasi Frontend (Pinia & UI UX)
1. **Permission Store**: Simpan daftar permission di Pinia `permission` store saat session inisialisasi login.
2. **Conditional Rendering**: Gunakan helper `auth.can('permission.name')` pada komponen Vue untuk menyembunyikan atau menonaktifkan elemen interaktif (seperti tombol create/edit/delete atau link menu navigasi).
3. **Route Guard**: Pasang properti `meta: { permission: 'permission.name' }` pada deklarasi router di `modules/<module>/routes.ts` dan lakukan pengecekan pada hook router `beforeEach`.
4. *Catatan Penting*: Otorisasi frontend adalah bagian dari kenyamanan UX. Backend tetap bertindak sebagai garda keamanan utama (source of truth).

### 3. Perancangan Halaman Admin CRUD
1. **Layout Halaman**: Strukturkan halaman ke dalam `IndexPage.vue` (tabel data), dan `FormPage.vue` / `FormDialog.vue` (input form).
2. **Aksi Hapus**: Penghapusan data wajib memiliki dialog konfirmasi peringatan terlebih dahulu melalui **SweetAlert2** (`Swal.fire({ icon: 'warning', ... })`) sebelum memicu mutation delete.
3. **Proteksi Aksi**: Tombol tambah, ubah, dan hapus dibungkus dengan pemeriksaan permission matching (`v-if="auth.can('module.create')"`).

## Checklist

- [ ] Format penamaan permission konsisten `<module>.<action>`.
- [ ] Pengecekan otorisasi terpasang kokoh di layer backend (Controller/Policy).
- [ ] Tombol aksi create/edit/delete di frontend disembunyikan jika tidak memiliki izin via `auth.can(...)`.
- [ ] Penghapusan data meminta konfirmasi dialog SweetAlert2.
- [ ] Query list di-invalidate setelah operasi mutasi (create/edit/delete) sukses dilakukan.
