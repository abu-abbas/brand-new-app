---
name: build-error-definitions
description: Menetapkan dan mengintegrasikan error terstruktur per module Laravel berdasarkan Error Definition Framework. Gunakan setiap kali membuat atau mengubah module, endpoint, FormRequest, Service/Action, domain exception, response error API, logging error, atau generated error code TypeScript/JSON.
---

# Build Error Definitions

Jadikan PHP/Laravel source of truth. Definisikan setiap kondisi gagal sekali pada backed enum milik konteks bisnis; frontend hanya memakai contract atau artifact hasil generate.

## Alur wajib

1. Periksa error enum, exception, renderer, FormRequest, dan pola logging yang sudah ada. Reuse implementasi framework jika tersedia; repository acuan saat ini adalah spesifikasi, bukan package Composer siap instal.
2. Inventarisasi kondisi gagal nyata pada module: validation, authentication, authorization, not found, business rule, workflow, integration, dan system. Jangan membuat kode untuk kegagalan spekulatif.
3. Tempatkan satu enum pada konteks yang memiliki aturan tersebut, bukan berdasarkan controller atau halaman. Gunakan enum relasi tersendiri hanya bila relasi itu mempunyai aturan bisnis mandiri.
4. Gunakan string backed enum yang mengimplementasikan `ErrorCode`. Beri setiap case satu `#[ErrorDefinition(...)]` dengan message, category, HTTP status, severity, dan retryable.
5. Bentuk kode stabil `<PREFIX>-<KONTEKS>-<NNN>` dan cocokkan regex `^[A-Z0-9]+(?:-[A-Z0-9]+)+-\d{3,}$`. Jangan memakai ulang kode lama untuk arti baru.
6. Untuk kegagalan bisnis, resolve enum melalui `ErrorDefinitionReader`, lalu lempar `ApplicationException` dari Service/Action. Controller tidak membentuk response error manual.
7. Untuk validation, gunakan `HasErrorDefinitions` pada FormRequest dan petakan setiap rule melalui `errorCodes()` dengan key `attribute.rule`. Jangan override `messages()` pada request yang opt-in.
8. Simpan hanya identifier minimum pada runtime context. Jangan masukkan credential, payload, Request, session, atau model penuh. Pastikan sanitizer meredaksi sensitive key.
9. Pertahankan response publik persis sesuai [kontrak](references/contracts.md). Client melakukan branching berdasarkan `code`, bukan message.
10. Jalankan linter dan generator bila command tersedia. Jangan edit generated TypeScript atau JSON catalog secara manual.
11. Tambahkan test terkecil yang membuktikan mapping error, HTTP status, response body, dan tidak bocornya context internal.

## Integrasi module

- Module baru wajib mempunyai error enum bila mempunyai validation atau kondisi gagal khusus.
- CRUD tanpa business failure tetap memetakan validation; gunakan exception Laravel biasa untuk kasus yang belum memiliki Error Definition hanya jika contract global memang menanganinya.
- Tambahkan error baru pada enum pemilik yang sudah ada sebelum membuat enum baru.
- Sertakan generated error code dalam sinkronisasi frontend/OpenAPI ketika bentuk response terdampak.

## Reference

Baca [contracts.md](references/contracts.md) sebelum menulis atau mereview implementasi. Dokumen itu memuat bentuk response, metadata, kategori, validation, logging, linting, dan generation yang wajib dipertahankan.
