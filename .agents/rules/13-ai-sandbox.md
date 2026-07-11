# Penanganan Batasan Sandbox AI

## Aturan Utama

1. **Meminta Izin Secara Jelas**:
   - Untuk setiap perintah atau operasi file yang membutuhkan permission (baik yang gagal karena permission error maupun yang terdeteksi di awal membutuhkan persetujuan/privilese lebih tinggi), Agent harus langsung menanyakannya kepada user.

2. **Batasan Berulang (Anti-Looping)**:
   - Jika suatu perintah/operasi sudah disetujui (di-approve/ACC) oleh user namun eksekusi masih gagal atau mentok karena batasan sistem sandbox (misalnya: gagal membaca path luar, isu sandboxing terminal, port tertutup, atau keterbatasan environment), **JANGAN mencobanya berulang-ulang**. Hal ini penting untuk menghindari pemborosan token dan resource.

3. **Fallback ke Instruksi Manual**:
   - Jika mentok karena keterbatasan sandbox, hentikan percobaan otomatis dan sampaikan secara jujur kepada user mengenai keterbatasan tersebut.
   - Berikan panduan yang jelas, lengkap, dan siap pakai berisi daftar perintah (script) atau modifikasi file yang harus dijalankan/dilakukan oleh user secara manual di terminal lokal mereka.
