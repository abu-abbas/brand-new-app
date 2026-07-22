---
name: ui-component-priority
trigger: always_on
description: Prioritas pemilihan komponen UI antara shadcn-vue, Element Plus, dan Tailwind, termasuk ConfirmDialog renderless untuk konfirmasi tanpa SweetAlert2.
---

# UI Component Priority (Wajib)

## Prioritas Pemilihan Komponen

1. **shadcn-vue** — pilihan **utama/first choice** untuk **layout & struktur halaman**: `Layout`, `Card`, `Button`, `Dialog`, `Dropdown`, `Tabs`, `Sheet`, `Badge`, `Breadcrumb`, `Tooltip`.
2. **Element Plus** — pilihan utama untuk **form & data kompleks** karena validasi bawaan lebih matang: `Form`, `Validation`, `Table` (jika bukan custom DataTable), `Tree`, `Upload`, `DatePicker`, `Select`, `Pagination`.
3. **Tailwind CSS** — dipakai sebagai styling utama untuk spacing, warna, layout responsif. **Hindari menulis CSS manual/custom** selama utility class Tailwind sudah bisa mengakomodasi.

## Skill Resmi shadcn-vue — WAJIB Terinstall

- Project ini **WAJIB** meng-install skill resmi shadcn-vue supaya Agent tahu API, CLI, dan konvensi component yang benar sesuai `components.json` project ini (bukan menebak dari ingatan/training data yang bisa saja sudah kadaluarsa versi):
  ```bash
  npx skills add unovue/shadcn-vue
  ```
- Sebelum menambahkan/menggunakan component shadcn-vue baru, Agent **WAJIB**:
  1. Cek dulu skill shadcn-vue (hasil `shadcn-vue info --json`) untuk tahu alias import, base library (`reka-ui`), icon library, dan component apa saja yang **sudah** ter-install di project ini — jangan asumsikan/hardcode path import.
  2. Gunakan `shadcn-vue add <component>` (CLI resmi) untuk menambah component baru, **bukan** copy-paste manual dari dokumentasi/ingatan, supaya file yang di-generate konsisten dengan versi & preset project ini.
  3. Gunakan `shadcn-vue docs`/`shadcn-vue search` (atau MCP server shadcn-vue jika tersedia) untuk memverifikasi prop/API component sebelum menulis kode, supaya tidak halusinasi prop yang tidak ada.
- Ikuti aturan komposisi resmi dari skill tersebut, contohnya: pakai `FieldGroup` untuk pengelompokan field form, `ToggleGroup` untuk kumpulan opsi toggle, gunakan **semantic color token** (misal `bg-primary`, `text-muted-foreground`) — **bukan** hex/Tailwind color mentah (`bg-blue-500`) — supaya tetap konsisten dengan theming (CSS variable/OKLCH) yang diatur project.
- Rule ini (`ui-component-priority`) mengatur **kapan pakai shadcn-vue vs Element Plus** (keputusan arsitektur); skill resmi shadcn-vue mengatur **detail teknis penggunaan library itu sendiri** (API, CLI, theming) — keduanya dipakai berdampingan, bukan saling menggantikan.

## Notifikasi & Konfirmasi

- **DILARANG KERAS** menggunakan `window.alert()`, `window.confirm()`, atau `window.prompt()` di mana pun dalam aplikasi.
- Konfirmasi dan blocking alert **WAJIB** memakai `useConfirmDialog()` dan satu host global `ConfirmDialog.vue` berbasis shadcn-vue `AlertDialog`.
- Untuk aksi async, berikan `onConfirm`; host mempertahankan dialog dan menampilkan `Spinner` sampai Promise selesai.
- Setelah `onConfirm`, host menampilkan dialog sukses atau error. Success auto-close memakai `successAutoCloseSeconds` (default `3`, gunakan `false` untuk menonaktifkan) dan menampilkan countdown.
- Gunakan `confirmVariant: 'destructive'` untuk aksi berbahaya, `reverseActions` untuk menukar urutan, serta label `false` untuk menyembunyikan tombol.
- Notifikasi non-blocking memakai `vue-sonner` bila fitur memang membutuhkan toast. Validation `422` tetap inline pada form.
- Pesan error harus ramah pengguna dan tidak menampilkan raw stack trace/JSON.

## Prinsip Memilih Kapan Pakai Apa

- Butuh modal/dialog sederhana, tab, breadcrumb, badge status -> **shadcn-vue**.
- Butuh form dengan validasi banyak field, tree select, upload file, date range -> **Element Plus**.
- Butuh konfirmasi/blocking alert -> **ConfirmDialog** berbasis shadcn-vue.
- Butuh toast non-blocking -> **vue-sonner**.

## Aturan Icon

- **DILARANG** menggunakan unicode/emoji icon mentah (seperti 👋, 📂, ❌, ✔) sebagai representasi icon UI di dalam component.
- **WAJIB** menggunakan component icon resmi dari library yang telah diatur (seperti `@lucide/vue` untuk Lucide, atau sesuai setelan `iconLibrary` pada `components.json`).
