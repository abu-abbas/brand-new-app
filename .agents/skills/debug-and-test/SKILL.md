---
name: debug-and-test
description: Panduan penelusuran kesalahan (debugging) lintas-layer, prinsip peninjauan kode (code review) sesuai standar arsitektur project, dan strategi penulisan tes otomatis (backend Pest, frontend Vitest, dan E2E Playwright).
---

# Debug & Test

## Kapan skill ini dipakai

Dipakai saat menelusuri bug/eror yang dilaporkan, melakukan verifikasi/review mandiri kode program sebelum digabungkan (merge), atau menulis tes otomatis untuk fitur baru/perbaikan bug.

## Langkah Kerja

### 1. Diagnosis & Debugging Lintas-Layer
1. **Pemanfaatan Trace ID**: Jika laporan bug menyertakan `trace_id` / `request_id`, lakukan pencarian (grep) langsung pada log backend:
   ```bash
   grep 'request_id":"<id>"' storage/logs/laravel.log
   ```
2. **Investigasi Lapisan**: Telusuri alur data sesuai urutan arsitektur (`Component Vue -> API Facade -> Generated Client -> Axios -> Controller -> Service/Action -> Database`) untuk mendeteksi di mana data mulai menyimpang.
3. **Penyelesaian Masalah**: Selesaikan eror di sumber masalahnya. Hindari penulisan kode "tambalan" sementara di frontend untuk menutupi bug logika di backend.

### 2. Code Review (Peninjauan Kode)
1. **Pemeriksaan Arsitektur**:
   - Pastikan komponen Vue **tidak** memanggil instance Axios / Orval client secara langsung (wajib lewat API Facade).
   - Pastikan Controller backend **hanya** bertindak sebagai orkestrator tipis (logika bisnis didelegasikan ke class Service/Action).
   - Pastikan tidak ada file generated (Orval client, Scramble openapi.json) yang diedit secara manual.
2. **Kepatuhan Linter**: Kode harus mematuhi aturan format otomatis (Pint untuk PHP, ESLint/Prettier untuk TypeScript).

### 3. Penulisan Tes Otomatis (Automated Testing)
1. **Backend Feature Test (Pest)**: Buat tes untuk API endpoint baru/modifikasi di `tests/Feature/`.
   - Uji skenario sukses (*happy path* dengan data valid).
   - Uji kegagalan validasi (skema input invalid menghasilkan respon `422`).
   - Uji batasan otorisasi (respon `403` jika tidak diizinkan, `401` jika unauthenticated).
   - Gunakan factory data model untuk menyajikan model data dummy (hindari hardcode data).
2. **Frontend Unit Test (Vitest)**: Fokuskan pengujian pada custom composables (Query/Mutation) dan validasi alur input form penting menggunakan `@vue/test-utils` / mock API.
3. **End-to-End Test (Playwright)**: Batasi pengujian E2E hanya untuk fungsionalitas alur bisnis kritis/krusial (seperti login, form pembayaran/registrasi).

## Checklist

- [ ] Log ditelusuri menggunakan `request_id` unik untuk menganalisis root cause.
- [ ] Logika perbaikan diimplementasikan pada layer arsitektur yang tepat.
- [ ] Review memverifikasi kepatuhan standardisasi folder dan file generated.
- [ ] Tes backend mencakup skenario happy path, validasi gagal, dan otorisasi.
- [ ] Kode tes memanfaatkan model factories untuk menyajikan data dummy.
- [ ] Hasil penulisan kode bersih dari sisa instruksi `console.log` / `dd()`.
