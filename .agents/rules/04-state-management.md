---
name: state-management-boundary
trigger: model_decision
description: Digunakan ketika Agent perlu memutuskan apakah suatu state sebaiknya disimpan di Pinia atau di-manage lewat TanStack Query.
---

# Batasan Pinia vs TanStack Query

Gunakan rule ini ketika Agent ragu menaruh sebuah state di Pinia store atau membiarkannya di-manage oleh TanStack Query.

## Pinia — untuk Client State / App State

Gunakan Pinia **hanya** untuk state yang sifatnya client-side dan tidak berasal dari server secara langsung sebagai data list/detail:

- `auth` — user login, token, profile ringkas.
- `permission` — daftar permission/role hasil login, dipakai `auth.can(permission)`.
- `theme` — dark/light mode, preferensi tampilan.
- `sidebar` — collapsed/expanded, active menu.
- `app-bootstrap` — konfigurasi awal aplikasi (app config, feature flags).

## TanStack Query — untuk Server State

Gunakan TanStack Query untuk **semua** data yang berasal dari server API:

- CRUD (create, read, update, delete).
- Search & filter.
- Pagination / infinite scroll.
- Detail record.
- Caching, invalidation, refetching, optimistic update.

## Aturan Keputusan

- Jika data **bisa basi/berubah dari server** dan perlu caching/refetch -> **TanStack Query**, bukan Pinia.
- Jika data **murni preferensi UI di client** atau **session/identitas user** -> **Pinia**.
- Jangan menyimpan hasil fetch list/detail ke dalam Pinia store hanya demi "kemudahan akses global" — itu akan menduplikasi cache dan menyebabkan data stale tidak sinkron dengan TanStack Query.
