---
name: build-datatable
description: Membangun tabel data (DataTable) dengan pagination, sorting, filter/search, terhubung ke TanStack Query. Gunakan saat user minta tampilkan data dalam bentuk tabel dengan fitur pencarian/pagination.
---

# Build DataTable

## Kapan skill ini dipakai

Saat user butuh menampilkan data list dalam bentuk tabel dengan pagination, sorting, dan/atau search.

## Langkah Kerja

1. Gunakan **Element Plus `Table`** + `Pagination` sebagai basis komponen tabel (bukan reinvent tabel custom kecuali requirement sangat spesifik).
2. Buat composable query khusus di `modules/<module>/queries/use<Resource>ListQuery.ts` yang menerima parameter reaktif: `page`, `perPage`, `search`, `sortBy`, `filters`.
3. State parameter tabel (page, search, filter) disimpan sebagai **local reactive state di component** (bukan Pinia — ikuti rule `state-management-boundary`), lalu diteruskan ke query composable.
4. Debounce input search (misal 300-500ms) sebelum trigger query, supaya tidak spam request ke API.
5. Tampilkan **loading skeleton/spinner** saat `isLoading`, dan **empty state** yang jelas saat data kosong (bukan tabel kosong tanpa pesan).
6. Kolom aksi (edit/delete/detail) mengikuti permission check dari store `permission`.
7. Untuk export data (jika diminta), panggil endpoint backend khusus export, jangan proses export besar di frontend.

## Checklist

- [ ] Pagination, search, sorting terhubung ke API lewat query params, bukan filter di frontend saja.
- [ ] Search sudah di-debounce.
- [ ] Loading & empty state sudah ada.
- [ ] Tidak menyimpan state tabel di Pinia.
