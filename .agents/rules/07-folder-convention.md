---
name: folder-convention
trigger: glob
globs: 'resources/js/**/*'
description: Konvensi struktur folder resources/js yang wajib diikuti untuk setiap module frontend baru.
---

# Folder Convention — `resources/js/`

```
resources/js/
├── app/          # Bootstrap aplikasi: main.ts, router index, pinia init, plugin registration
├── api/          # Generated client (Orval) — JANGAN diedit manual, lihat rule generated-files-protection
├── modules/      # Setiap fitur/domain bisnis punya foldernya sendiri
│   └── <nama-module>/
│       ├── pages/          # Halaman/route-level component
│       ├── components/     # Component spesifik module ini
│       ├── api/             # API Facade module ini (pembungkus generated client)
│       ├── queries/          # useQuery hooks (TanStack Query)
│       ├── mutations/        # useMutation hooks (TanStack Query)
│       ├── routes.ts         # Route definition module ini
│       └── permissions.ts    # Daftar permission key yang dipakai module ini
├── shared/       # Component, composable, util yang dipakai lintas module (bukan spesifik 1 module)
└── stores/       # Pinia store: auth, permission, theme, sidebar, app-bootstrap
```

## Aturan

- Module baru **WAJIB** mengikuti struktur di atas secara konsisten — jangan membuat struktur ad-hoc per module.
- Component yang dipakai lebih dari 1 module **WAJIB** dipindah ke `shared/components/`, bukan diduplikasi.
- Nama folder module menggunakan `kebab-case` dan mencerminkan domain bisnis (contoh: `user-management`, `product-catalog`), bukan nama teknis generik.
- Routes tiap module didaftarkan terpusat lewat `app/router` yang meng-import `routes.ts` dari setiap module (lazy-loaded / code-splitting per module).
