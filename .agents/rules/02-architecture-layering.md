---
name: architecture-layering
trigger: always_on
description: Menjaga alur arsitektur berlapis Browser -> Blade -> Vue -> Module -> API Facade -> Orval -> Axios -> Laravel API tetap konsisten dan tidak dilanggar.
---

# Architecture Layering (Wajib)

## Alur Resmi

```
Browser
  -> Blade (SPA Shell)
  -> Vue
  -> Module
  -> API Facade
  -> Orval Generated Client
  -> Axios
  -> Laravel API
  -> Business Logic (Service/Action)
  -> Database
```

## Larangan Keras

- **Component Vue TIDAK BOLEH** mengimpor Orval generated client atau Axios instance secara langsung.
- Semua akses data dari component **HARUS** melalui **API Facade** milik module terkait (`modules/<nama-module>/api/*.facade.ts`), yang di dalamnya baru memanggil generated client (Orval) atau composable Query/Mutation (TanStack Query).
- Business logic backend **TIDAK BOLEH** ditulis langsung di Controller. Controller hanya orkestrasi: validasi (FormRequest) -> panggil Service/Action -> return API Resource.
- Setiap module frontend **HARUS mandiri** (self-contained): Page, Components, API Facade, Query, Mutation, Routes, Permission ada di dalam folder module itu sendiri.

---

# Folder Convention — `resources/js/`

```
resources/js/
├── app/          # Bootstrap aplikasi: main.ts, router index, pinia init, plugin registration
├── api/          # Generated client (Orval) — JANGAN diedit manual
├── modules/      # Setiap fitur/domain bisnis punya foldernya sendiri
│   └── <nama-module>/
│       ├── pages/          # Halaman/route-level component
│       ├── components/     # Component spesifik module ini
│       ├── api/            # API Facade module ini (pembungkus generated client)
│       ├── queries/        # useQuery hooks (TanStack Query)
│       ├── mutations/      # useMutation hooks (TanStack Query)
│       ├── routes.ts       # Route definition module ini
│       └── permissions.ts  # Daftar permission key yang dipakai module ini
├── shared/       # Component, composable, util yang dipakai lintas module
└── stores/       # Pinia store: auth, permission, theme, sidebar, app-bootstrap
```

### Aturan Folder:

- Module baru **WAJIB** mengikuti struktur di atas secara konsisten — jangan membuat struktur ad-hoc.
- Component yang dipakai lebih dari 1 module **WAJIB** ditaruh di `shared/components/`.
- Nama folder module menggunakan `kebab-case` (contoh: `user-management`, `product-catalog`).

---

# Batasan Pinia vs TanStack Query

### Pinia — untuk Client State / App State

Gunakan Pinia **hanya** untuk state yang sifatnya client-side dan tidak berasal dari server secara langsung sebagai data list/detail:

- `auth` — user login, token, profile ringkas.
- `permission` — daftar permission/role hasil login.
- `theme` — dark/light mode, preferensi tampilan.
- `sidebar` — collapsed/expanded, active menu.
- `app-bootstrap` — konfigurasi awal aplikasi.

### TanStack Query — untuk Server State

Gunakan TanStack Query untuk **semua** data yang berasal dari server API (CRUD, Search & filter, Pagination, Detail record, Caching, dll).
Jangan menyimpan hasil fetch list/detail ke dalam Pinia store — itu akan menduplikasi cache dan menyebabkan data stale tidak sinkron.
