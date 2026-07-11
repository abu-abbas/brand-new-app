---
name: build-rbac
description: Membangun/mengubah struktur Role-Based Access Control (role, permission, assignment) baik di backend (Policy/Gate) maupun frontend (permission store, auth.can). Gunakan saat user minta atur hak akses/role/permission.
---

# Build RBAC

## Kapan skill ini dipakai

Saat user minta menambah role baru, permission baru, atau mengatur hak akses suatu fitur.

## Langkah Kerja

### Backend

1. Definisikan permission sebagai string konsisten, format `<module>.<action>` (contoh: `user.view`, `user.create`, `user.update`, `user.delete`).
2. Simpan role & permission di database (tabel `roles`, `permissions`, pivot) — gunakan package RBAC yang sudah dipakai project (jika ada), atau struktur sederhana sesuai konvensi Policy Laravel.
3. Registrasikan pengecekan permission di **Policy** class per resource, method Policy memanggil helper cek permission user (bukan hardcode role name di banyak tempat).
4. Endpoint yang butuh proteksi permission pakai middleware `can:<permission>` atau `$this->authorize('<permission>')` di Controller.
5. Saat login, sertakan daftar permission user di response (lewat API Resource khusus `AuthResource`/`MeResource`) supaya frontend bisa membangun `permission` store.

### Frontend

1. Simpan daftar permission hasil login di Pinia store `permission` (ikuti rule `state-management-boundary`).
2. Sediakan helper `auth.can('user.create')` yang dipakai di component untuk conditional render (tombol, menu, route meta).
3. Route yang butuh permission tertentu diberi `meta: { permission: 'user.view' }`, dicek di navigation guard (`router.beforeEach`).
4. **Ingat**: pengecekan frontend hanya UX, backend tetap wajib re-check (rule `auth-authorization`).

## Checklist

- [ ] Format permission konsisten `<module>.<action>`.
- [ ] Policy backend jadi satu-satunya sumber keputusan otorisasi.
- [ ] Permission ikut dikirim saat login/`/me` endpoint.
- [ ] Frontend guard route & UI sudah pakai `auth.can(...)`.
