# Brand New App - Enterprise Dashboard

Aplikasi kerangka kerja (dashboard admin) modern yang dibangun menggunakan perpaduan teknologi terkini: **Laravel 13**, **Vue 3 (TypeScript)**, **Tailwind CSS v4**, dan **shadcn-vue**.

## Fitur Utama

- **Layout Modular & Dekomposisi Komponen**:
  - `AdminLayout.vue` — Orkestrator global yang menyatukan tata letak sidebar dan header.
  - `AdminSidebar.vue` — Sidebar collapsible dengan menu bertingkat (_collapsible tree items_) bergaya _sidebar-07_.
  - `AdminHeader.vue` — Top clean navigation dengan integrasi breadcrumb dinamis.
- **Preferences Panel (Dropdown Profil)**:
  - **Multi-Theme Selector**: Mendukung 18 warna tema visual terintegrasi secara dinamis (mengubah variabel CSS `--primary` root secara instan).
  - **Dynamic Dark/Light Mode**: Pengatur mode gelap/terang bawaan yang di-render langsung di dalam menu dropdown profil secara responsif.
  - Ikon preferensi warna (`Palette` icon) yang warnanya dinamis mengikuti warna tema yang sedang aktif.
- **Sistem Font Figtree Terpusat**: Seluruh font di dalam aplikasi diselaraskan secara konsisten ke Google Font **Figtree** melalui konfigurasi `@theme` Tailwind CSS dan bundler Vite.

## Stack Teknologi

- **Backend**: Laravel 13
- **Frontend**: Vue 3 (Composition API) + TypeScript
- **Styling**: Tailwind CSS v4 & Vanilla CSS
- **UI Components**:
  - **shadcn-vue** (Layout, Sidebar, Dialog, Tooltip, DropdownMenu)
  - **Lucide Vue** (Ikon UI)
- **State & Core Utilities**: `@vueuse/core`

## Cara Menjalankan Project

1. Pastikan seluruh dependensi frontend terinstall:
   ```bash
   npm install
   ```
2. Jalankan server pengembangan Vite secara lokal:
   ```bash
   npm run dev
   ```
3. Di terminal lain, jalankan server pengembangan Laravel:
   ```bash
   php artisan serve
   ```
4. Buka `http://localhost:8000` di peramban web Anda.
