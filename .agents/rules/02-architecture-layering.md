---
name: architecture-layering
trigger: always_on
description: Menjaga alur arsitektur berlapis Browser -> Blade -> Vue -> Module -> API Facade -> Orval -> Axios -> Laravel API tetap konsisten dan tidak dilanggar.
---

# Architecture Layering (Wajib)

## Alur Resmi

```
Browser
  -> Blade (SPA Shell)
  -> Vue
  -> Module
  -> API Facade
  -> Orval Generated Client
  -> Axios
  -> Laravel API
  -> Business Logic (Service/Action)
  -> Database
```

## Larangan Keras

- **Component Vue TIDAK BOLEH** mengimpor Orval generated client atau Axios instance secara langsung.
- Semua akses data dari component **HARUS** melalui **API Facade** milik module terkait (`modules/<nama-module>/api/*.facade.ts`), yang di dalamnya baru memanggil generated client (Orval) atau composable Query/Mutation (TanStack Query).
- Business logic backend **TIDAK BOLEH** ditulis langsung di Controller. Controller hanya orkestrasi: validasi (FormRequest) -> panggil Service/Action -> return API Resource.
- Setiap module frontend **HARUS mandiri** (self-contained): Page, Components, API Facade, Query, Mutation, Routes, Permission ada di dalam folder module itu sendiri.

## Kenapa aturan ini penting

Layering ini menjaga agar:

1. Perubahan API contract cukup di-generate ulang lewat Orval, tanpa Agent perlu menebak-nebak shape data di banyak component.
2. Agent AI bisa membangun fitur baru secara konsisten mengikuti pola yang sama setiap saat.
3. Refactor lebih aman karena boundary antar layer jelas.

Jika Agent menemukan pelanggaran pola ini di kode existing, **laporkan ke user** dan tawarkan perbaikan, jangan diam-diam mengikuti pola yang salah.
