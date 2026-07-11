---
name: auth-authorization
trigger: always_on
description: Aturan implementasi authentication (Sanctum SPA) dan authorization (Policy/Gate di backend, auth.can di frontend hanya untuk UX).
---

# Authentication & Authorization (Wajib)

## Authentication — Sanctum SPA

- Gunakan **Laravel Sanctum** mode **SPA (cookie & session)**, bukan token Bearer manual, karena frontend adalah SPA yang di-serve dari domain/subdomain yang sama atau sudah diatur `SANCTUM_STATEFUL_DOMAINS`.
- Axios **wajib** dikonfigurasi `withCredentials: true` dan memanggil endpoint `/sanctum/csrf-cookie` sebelum request pertama yang butuh autentikasi.
- Guard route Vue Router menggunakan **`beforeEach`** untuk mengecek status login (dari Pinia `auth` store) sebelum masuk ke halaman yang butuh autentikasi.
- Axios Interceptor menangani:
  - Response `401` -> redirect ke halaman login & bersihkan `auth` store.
  - Response `403` -> tampilkan SweetAlert2 "Anda tidak memiliki akses" (bukan native alert).

## Authorization

### Backend (Source of Truth)

- **Policy** dan **Gate** adalah satu-satunya tempat keputusan otorisasi yang **sesungguhnya**. Setiap Controller/Action **WAJIB** memanggil `$this->authorize()` atau middleware `can:` sebelum eksekusi.
- **JANGAN PERNAH** mempercayai flag permission dari frontend untuk keputusan keamanan — backend selalu re-check.

### Frontend (Hanya untuk UX)

- Frontend menggunakan `auth.can('permission-name')` dari Pinia `permission` store **hanya untuk keperluan UX**: menyembunyikan/menonaktifkan tombol, menu, atau route yang tidak relevan bagi user.
- **JANGAN** menjadikan pengecekan frontend sebagai satu-satunya lapisan keamanan. Selalu asumsikan backend adalah garda terakhir.
