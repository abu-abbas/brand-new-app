---
description: Digunakan ketika fitur backend & frontend sudah ada, tapi perlu ditambah/diubah aturan hak akses (role/permission baru)
---

## Steps

1. Klarifikasi ke user: permission apa yang ditambahkan (format `<module>.<action>`), role mana yang mendapat permission ini.
2. Jalankan skill `build-rbac`:
   - Tambahkan permission baru ke seeder/tabel permission.
   - Perbarui Policy terkait untuk mengenali permission baru.
   - Assign permission ke role yang sesuai (seeder atau lewat halaman admin RBAC jika sudah ada).
3. Pastikan endpoint terkait di backend memakai middleware `can:` / `$this->authorize()` dengan permission baru ini (cek ulang, jangan sampai permission dibuat tapi tidak dipakai di endpoint manapun).
4. Update endpoint `/me` atau `AuthResource` (jika perlu) supaya permission baru ikut terkirim ke frontend saat login.
5. Frontend: pastikan `auth.can('<module>.<action>')` dipasang di tombol/menu/route yang relevan.
6. Jalankan skill `write-tests` untuk test otorisasi (`403` untuk role tanpa permission, sukses untuk role dengan permission).
7. Summary ke user: permission baru apa saja, role mana yang punya akses, bagian UI mana yang sekarang dilindungi permission ini.
