---
name: build-form
description: Membangun form input (create/edit) dengan validasi menggunakan Element Plus Form, terhubung ke mutation TanStack Query dan menampilkan error validasi dari backend lewat SweetAlert2/inline error.
---

# Build Form

## Kapan skill ini dipakai

Saat user minta membuat form input untuk create/edit data.

## Langkah Kerja

1. Gunakan **Element Plus `Form` + `FormItem`** dengan `rules` validasi client-side (required, format, min/max) untuk quick feedback ke user.
2. Definisikan `reactive` form model dengan tipe TypeScript yang **berasal dari generated Orval type** (request body type), bukan interface manual (ikuti rule `openapi-first-flow`).
3. Submit form memanggil **mutation** dari `modules/<module>/mutations/`, yang di dalamnya memanggil **API Facade**.
4. **Validasi server-side (FormRequest Laravel)** adalah validasi final — tangani response error `422` dari backend:
   - Map error per-field ke Element Plus form (`el-form-item` error prop) jika field cocok.
   - Tampilkan pesan error umum lewat **SweetAlert2**, bukan native alert.
5. Setelah submit sukses: SweetAlert2 toast sukses, invalidate query terkait, dan redirect/close dialog sesuai konteks (halaman terpisah vs dialog).
6. Field yang sensitif terhadap permission (misal hanya admin yang boleh ubah field tertentu) dicek lewat `auth.can(...)`.

## Checklist

- [ ] Tipe form model berasal dari generated client Orval.
- [ ] Validasi client-side (Element Plus rules) + validasi server-side (Laravel FormRequest) keduanya jalan.
- [ ] Error dari backend ditampilkan lewat SweetAlert2/inline, tidak ada native alert.
- [ ] Query terkait di-invalidate setelah submit sukses.
