---
description: Menjalankan review terstruktur terhadap perubahan kode (backend dan/atau frontend) sebelum di-merge
---

## Steps

1. Identifikasi file-file yang berubah (dari diff/PR yang diberikan user).
2. Jalankan skill `code-review` terhadap masing-masing file/perubahan, mencakup: correctness, arsitektur layering, konvensi folder, OpenAPI/Orval, UI/UX (ConfirmDialog, shadcn-vue/Element Plus), authorization, style, dan kecukupan test.
3. Kelompokkan temuan menjadi:
   - **Blocking** — harus diperbaiki sebelum merge (bug, pelanggaran arsitektur, celah keamanan/otorisasi).
   - **Suggestion** — perbaikan opsional (style, refactor kecil, penamaan).
4. Cek apakah checklist CI/CD (rule `cicd-pipeline`) relevan sudah disebutkan: lint, type check, PHPStan untuk push; PHPUnit/Pest & Vitest untuk merge request.
5. Sampaikan hasil review dalam Bahasa Indonesia (rule `bahasa-indonesia`), terstruktur per file/per area, dengan contoh perbaikan singkat jika ada.
6. Jika user setuju dengan temuan, tawarkan untuk langsung memperbaiki (lanjut ke skill terkait: `build-api`, `build-frontend-ui`, dsb) atau biarkan user yang memperbaiki sendiri.
