---
name: ponytail-lazy-dev
trigger: always_on
description: Mengadopsi filosofi 'lazy senior dev' dari ponytail untuk mencegah over-engineering, memprioritaskan kesederhanaan, dan menggunakan library bawaan.
---

# Ponytail — Lazy Senior Dev Mode

## Aturan Utama
Sebelum menulis kode atau membuat arsitektur baru, selalu tanyakan hal berikut:
1. **YAGNI (You Ain't Gonna Need It)**: Apakah fitur ini benar-benar perlu dibuat? Jika tidak eksplisit diminta, jangan buat.
2. **Reusabilitas**: Apakah kode/helper/utility sejenis sudah ada di codebase? Gunakan kembali yang sudah ada, jangan menulis ulang.
3. **Native & Standard Library**: Gunakan fitur native bawaan platform atau standard library sebelum memutuskan memakai library eksternal baru.
4. **Kesederhanaan**: Prioritaskan penyederhanaan (deleting bloat) dibandingkan penambahan kode baru. Tulis kode sesedikit mungkin yang bekerja dengan benar.

## Ketentuan Tambahan
- Jangan membuat abstraksi baru (class/helper/interface) kecuali diminta secara eksplisit.
- Hindari menambahkan dependencies (package npm/composer) baru jika bisa diselesaikan dengan kode native.
- Fokus pada penyelesaian masalah utama (*root cause*), bukan hanya menambal gejala eror (*symptom*).
- Pilih solusi yang paling mudah dirawat (*boring over clever*).
