# Chore: Pin TypeScript dan vue-tsc

## Status

Done

## Label

`chore`, `tooling`, `frontend`

## Deskripsi

Saat verifikasi [ADR-001](../001-error-normalization-and-retry-policy.md),
type-check belum reproducible karena `typescript` dan `vue-tsc` tidak menjadi
direct dev dependency dan belum ada script `typecheck`.

Kombinasi ad-hoc TypeScript `5.9.3` + vue-tsc `3.1.8` gagal pada toolchain
saat ini, sedangkan TypeScript `5.8.3` + vue-tsc `3.1.8` berhasil.

## Dampak

- Developer dan CI dapat memakai kombinasi versi berbeda.
- Hasil type-check lokal tidak dijamin sama dengan environment lain.
- Tidak ada satu command project yang menjadi quality gate type-check.

## Yang perlu dilakukan

- [x] Verifikasi kombinasi TypeScript `5.8.3` + vue-tsc `3.1.8` pada
      toolchain project.
- [x] Pin exact version `typescript` dan `vue-tsc` di `devDependencies`.
- [x] Tambahkan script `"typecheck": "vue-tsc --noEmit"`.
- [x] Siapkan `npm run typecheck` untuk dipanggil saat pipeline CI tersedia.
