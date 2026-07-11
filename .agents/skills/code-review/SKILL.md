---
name: code-review
description: Melakukan review kode (backend/frontend) untuk memeriksa bug, pelanggaran arsitektur layering, konvensi folder, dan best practice project ini. Gunakan saat user minta review PR/kode.
---

# Code Review

## Kapan skill ini dipakai

Saat user minta Agent me-review kode (PR, file tertentu, atau perubahan yang baru dibuat).

## Checklist Review

1. **Correctness**: Apakah kode melakukan apa yang seharusnya? Apakah ada edge case yang tidak ditangani (input kosong, null, unauthorized, dsb)?
2. **Arsitektur** (rule `architecture-layering`):
   - Apakah component Vue mengakses Orval/Axios langsung? -> pelanggaran.
   - Apakah business logic backend ditulis langsung di Controller? -> pelanggaran, harus di Service/Action.
3. **OpenAPI/Orval** (rule `openapi-first-flow`, `generated-files-protection`):
   - Apakah ada tipe manual di frontend yang seharusnya dari generated client?
   - Apakah ada file generated yang diedit manual?
4. **Konvensi folder** (rule `folder-convention`): apakah file baru diletakkan sesuai struktur module?
5. **UI/UX** (rule `ui-component-priority`): apakah masih ada `alert()`/`confirm()` native? Apakah komponen yang dipakai sesuai prioritas (shadcn-vue vs Element Plus)?
6. **Authorization** (rule `auth-authorization`): apakah endpoint baru sudah dilindungi Policy/Gate, bukan hanya cek di frontend?
7. **Style**: apakah lolos Pint (PHP) / ESLint (TypeScript)? Penamaan variable/function konsisten?
8. **Test**: apakah ada test yang seharusnya ditambahkan (ikuti rule `testing-strategy`) tapi belum ada?

## Format Feedback

- Jelaskan **apa** yang perlu diubah dan **kenapa** (bukan cuma "ini salah").
- Jika memungkinkan, beri **contoh perbaikan** singkat.
- Bedakan **blocking issue** (harus diperbaiki sebelum merge) dan **suggestion** (nice to have).
- Sampaikan hasil review dalam Bahasa Indonesia (rule `bahasa-indonesia`), istilah teknis tetap apa adanya.
