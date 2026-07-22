---
name: auth-authorization
trigger: always_on
description: Aturan implementasi authentication (Sanctum SPA), authorization (Policy/Gate backend & auth.can frontend), strategi automated testing, dan integrasi pipeline CI/CD.
---

# Authentication & Authorization (Wajib)

## 1. Authentication — Sanctum SPA

- Gunakan **Laravel Sanctum** mode **SPA (cookie & session)**, bukan token Bearer manual, karena frontend adalah SPA yang di-serve dari domain/subdomain yang sama atau sudah diatur `SANCTUM_STATEFUL_DOMAINS`.
- Axios **wajib** dikonfigurasi `withCredentials: true` dan memanggil endpoint `/sanctum/csrf-cookie` sebelum request pertama yang butuh autentikasi.
- Guard route Vue Router menggunakan **`beforeEach`** untuk mengecek status login (dari Pinia `auth` store) sebelum masuk ke halaman yang butuh autentikasi.
- Axios Interceptor menangani:
  - Response `401` -> redirect ke halaman login & bersihkan `auth` store.
  - Response `403` -> tampilkan blocking alert melalui `useConfirmDialog()` tanpa tombol cancel (bukan native alert).

## 2. Authorization

- **Backend (Source of Truth)**: **Policy** dan **Gate** adalah satu-satunya tempat keputusan otorisasi yang **sesungguhnya**. Setiap Controller/Action **WAJIB** memanggil `$this->authorize()` atau middleware `can:` sebelum eksekusi. **JANGAN PERNAH** mempercayai flag permission dari frontend untuk keputusan keamanan.
- **Frontend (Hanya untuk UX)**: Menggunakan `auth.can('permission-name')` dari Pinia `permission` store **hanya untuk keperluan UX** (seperti menyembunyikan/menonaktifkan tombol, menu, atau route yang tidak relevan bagi user).

---

# Testing Strategy & CI/CD Gating

## 1. Testing Strategy (Kapan Menulis Apa)

- **Perubahan backend (Controller, Service, Action, Policy)** -> wajib **Pest feature test** minimal untuk happy path + 1 edge case (unauthorized / validation error).
- **Perubahan logic murni (helper, formatter)** -> **Pest unit test**.
- **Perubahan composable / API Facade / store frontend** -> **Vitest unit test**.
- **Perubahan alur krusial** (login, submit form penting) -> pertimbangkan **Playwright E2E test**, tetapi tidak wajib untuk setiap perubahan kecil.

## 2. CI/CD Pipeline Gates (Verifikasi Alur Git)

Sebelum menyarankan PR/MR siap digabungkan (merge), verifikasi hal-hal berikut:

1. **Push stage**: Lulus Linting (`eslint` / `pint`), Type Check (`tsc --noEmit`), dan static analysis PHPStan.
2. **Merge Request stage**: Lolos test backend (`Pest`) dan test frontend (`Vitest`).
3. **Release stage**: Lolos E2E test (`Playwright`) sebelum deployment otomatis berjalan.
