---
description: Alur terstruktur untuk mendiagnosis bug dari laporan user hingga perbaikan dan test regresi
---

# Debug Issue

Workflow untuk menelusuri dan memperbaiki bug yang dilaporkan user. Panggil dengan `/debug-issue`.

## Steps

1. Minta/konfirmasi informasi dari user jika belum lengkap: pesan error, langkah reproduksi, halaman/endpoint terkait, screenshot/response API jika ada.
2. Jalankan skill `debug-application` untuk menelusuri root cause mengikuti layer arsitektur (Component -> API Facade -> Orval -> Axios -> Controller -> Service -> Database).
3. Setelah root cause ditemukan, jelaskan ke user (Bahasa Indonesia) apa penyebabnya sebelum melakukan perbaikan, terutama jika perbaikan cukup signifikan.
4. Lakukan perbaikan di layer yang tepat:
   - Jika perlu ubah backend -> ikuti skill `build-api` untuk konsistensi (Controller/FormRequest/Resource/Policy).
   - Jika perubahan backend mengubah contract API -> lanjutkan skill `generate-openapi` dan `sync-orval`.
   - Jika perlu ubah frontend -> ikuti skill `build-frontend-ui`/`build-form`/`build-datatable` sesuai konteks.
5. Jalankan skill `write-tests` untuk menambahkan test yang mereproduksi bug ini (test harus gagal sebelum fix, dan lolos setelah fix).
6. Summary ke user: root cause, perbaikan yang dilakukan, file yang berubah, dan test yang ditambahkan untuk mencegah regresi.
