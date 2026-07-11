---
name: openapi-first-flow
trigger: always_on
description: Laravel + Scramble adalah single source of truth untuk API contract, seluruh tipe frontend wajib mengikuti hasil generate dari OpenAPI, bukan sebaliknya.
---

# OpenAPI First (Wajib)

## Alur Resmi

```
Laravel (Controller + FormRequest + API Resource)
  -> Scramble (auto generate dari kode)
  -> docs/openapi.json
  -> Orval
  -> Generated TypeScript Client (resources/js/api/generated/)
```

## Aturan

- **Laravel adalah single source of truth**. Tipe data, request/response shape frontend **HARUS** mengikuti hasil generate dari backend, **BUKAN** sebaliknya (jangan bikin tipe manual di frontend lalu paksakan backend menyesuaikan).
- Setiap kali Agent membuat/mengubah endpoint baru di backend (Controller, FormRequest, API Resource, route), Agent **WAJIB**:
  1. Pastikan docblock/anotasi yang dibaca Scramble sudah benar (response schema, request schema, tag, summary).
  2. Generate ulang `docs/openapi.json`.
  3. Jalankan Orval untuk regenerate TypeScript client.
- Jangan pernah menulis interface/type TypeScript untuk response API secara manual jika seharusnya berasal dari generated client — itu akan membuat drift antara backend dan frontend.
- Jika Scramble tidak bisa mendeteksi schema dengan benar (misal response custom/dynamic), tambahkan anotasi eksplisit (`@response`, `@bodyParam`, atau helper Scramble) daripada membiarkan schema kosong/`any`.
