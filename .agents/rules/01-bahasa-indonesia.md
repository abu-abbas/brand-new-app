---
name: bahasa-indonesia
trigger: always_on
description: Wajib gunakan Bahasa Indonesia untuk setiap penjelasan, ringkasan, dan komentar percakapan dari AI Agent.
---

# Respect Bahasa Indonesia

## Aturan Utama

- Setiap kali Agent **menjelaskan sesuatu ke user** (analisis, rencana kerja, ringkasan hasil, alasan keputusan teknis, error yang ditemukan, dsb) — **WAJIB menggunakan Bahasa Indonesia**.
- **Istilah teknis TETAP menggunakan istilah aslinya** (bahasa Inggris), JANGAN dipaksakan diterjemahkan. Contoh istilah yang tidak diterjemahkan: `controller`, `middleware`, `endpoint`, `request`, `response`, `query`, `mutation`, `cache`, `store`, `composable`, `resource`, `policy`, `queue`, `job`, `migration`, `seeder`.
- **Kode program tetap ditulis dalam konvensi internasional standar** — nama variable, function, class, method, props, emits, dsb tetap Bahasa Inggris sesuai konvensi Laravel/Vue. Jangan menerjemahkan nama variable ke Bahasa Indonesia kecuali memang istilah bisnis lokal (misal nama kolom `nomor_induk_pegawai` jika memang istilah domain).
- **Commit message**: gunakan format `type: deskripsi singkat dalam Bahasa Indonesia`, misalnya `feat: tambah modul manajemen user`, `fix: perbaiki validasi form registrasi`.
- **Nama file, path, command line, dan output tool** tidak diterjemahkan — tampilkan apa adanya.
- Dokumentasi kode (docblock/PHPDoc/JSDoc) boleh Bahasa Indonesia untuk deskripsi bisnis, tapi anotasi tipe (`@param`, `@return`, tipe TypeScript) tetap standar teknis.

## Contoh

✅ Benar:

> "Saya akan membuat `UserController` dengan method `index()` untuk menampilkan daftar user, menggunakan `UserResource` supaya response API konsisten dengan skema OpenAPI."

❌ Salah:

> "I will create UserController with an index method to display the user list."

❌ Salah (memaksa terjemahkan istilah teknis):

> "Saya akan membuat Pengontrol Pengguna dengan metode indeks."
