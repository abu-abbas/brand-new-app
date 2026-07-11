---
name: testing-strategy
trigger: model_decision
description: Digunakan ketika Agent perlu menentukan jenis test apa yang harus ditulis untuk sebuah perubahan kode (PHPStan, Pest/PHPUnit, Vitest, Playwright).
---

# Testing Strategy

Urutan pengecekan yang harus dipertimbangkan Agent untuk setiap perubahan kode:

```
PHPStan (static analysis)
  -> PHPUnit / Pest (backend unit & feature test)
  -> Vitest (frontend unit test)
  -> Playwright (E2E test)
```

## Panduan Kapan Menulis Apa

- **Perubahan backend (Controller, Service, Action, Policy)** -> wajib **Pest/PHPUnit feature test** minimal untuk happy path + 1 edge case (misal unauthorized, validation error).
- **Perubahan business logic murni (helper, calculation, formatter)** -> **Pest/PHPUnit unit test**.
- **Perubahan composable / API Facade / store frontend** -> **Vitest unit test**.
- **Perubahan alur multi-halaman yang krusial** (login, checkout, submit form penting) -> pertimbangkan **Playwright E2E test**, tapi tidak wajib untuk setiap perubahan kecil.
- Sebelum submit test apapun, jalankan **PHPStan/Larastan** dulu untuk backend untuk menangkap error tipe lebih awal.

## Prinsip

Agent tidak perlu menulis ke-4 jenis test untuk setiap perubahan kecil — sesuaikan dengan risiko dan besarnya perubahan. Prioritaskan test yang paling dekat dengan lapisan yang diubah.
