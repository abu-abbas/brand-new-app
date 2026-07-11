---
name: cicd-pipeline
trigger: model_decision
description: Referensi pipeline CI/CD (push, merge request, release) agar Agent tahu gate apa yang harus lolos di setiap tahap sebelum menyarankan merge/deploy.
---

# CI/CD Pipeline Reference

## Push (setiap commit ke branch)

- Lint (ESLint/Pint)
- Type Check (`tsc --noEmit`)
- PHPStan / Larastan

## Merge Request

- PHPUnit / Pest (backend)
- Vitest (frontend)

## Release (merge ke branch utama)

- Playwright (E2E)
- Deploy

## Panduan untuk Agent

- Sebelum menyarankan sebuah PR/MR siap di-merge, pastikan checklist **Push** dan **Merge Request** di atas sudah disebutkan ke user sebagai langkah verifikasi, meski Agent tidak selalu bisa menjalankan pipeline CI secara langsung.
- Jika Agent membuat perubahan yang berpotensi memengaruhi E2E flow penting (login, checkout, form kritikal), ingatkan user bahwa Playwright test terkait perlu di-review sebelum release.
