---
trigger: always_on
---

# Respect Bahasa Indonesia, kecuali untuk type scope

## Aturan Utama

1. Cek semua perubahan dengan:
   - `git status --short`
   - `git diff --name-only`
   - `git diff`
2. Kelompokkan perubahan ke dalam commit yang relevan berdasarkan domain/fitur/file yang saling terkait.
3. Jangan campur perubahan yang tidak relevan dalam satu commit.
4. Jika ada lebih dari satu kelompok perubahan, buat beberapa commit terpisah sampai working tree bersih.
5. Untuk tiap commit:
   - stage hanya file yang relevan
   - gunakan Conventional Commit dengan scope
   - format: `<type>(<scope>): <judul>`
6. Scope harus spesifik terhadap domain perubahan, misalnya:
   - `monitoring-nd`
   - `monitoring-nd-ui`
   - `datatable`
   - `dashboard-filter`
   - `perbal-search`
7. Subject:
   - deskriptif
   - lowercase style
   - maksimal 100 karakter
8. Body wajib berbentuk bullet points dan berisi:
   - perubahan utama
   - file/area yang terdampak
   - dampak perilaku jika ada
9. Format body:
   - ada 1 baris kosong setelah subject
   - tiap baris maksimal 120 karakter
10. Jika commit hook gagal, perbaiki message lalu ulangi sampai lolos.
11. Setelah semua commit selesai, laporkan:

- daftar hash commit
- subject masing-masing commit
- ringkasan body masing-masing commit

12. Selesaikan sampai `git status --short` kosong.
13. Respect bahasa indonesia kecuali untuk type dan scope.
14. Jika ada perubahan yang jelas tidak relevan dengan kelompok lain, buat commit terpisah, jangan diabaikan.
15. Jika ada file yang meragukan relevansinya, jelaskan dulu pengelompokannya secara singkat lalu commit.
16. Pastikan tidak ada console.log agar tidak membuat error saat proses CI/CD
17. PHP wajib mengikuti Laravel Pint dengan indentasi 4 spasi. Jalankan `composer format:php:check` sebelum commit.
