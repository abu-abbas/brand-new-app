---
name: debug-application
description: Menelusuri dan memperbaiki bug di backend Laravel atau frontend Vue, mengikuti alur layering project untuk mempersempit lokasi masalah. Gunakan saat user melaporkan error/bug.
---

# Debug Application

## Kapan skill ini dipakai

Saat user melaporkan error, behavior tidak sesuai ekspektasi, atau bug lainnya.

## Langkah Kerja

1. **Kumpulkan informasi**: pesan error lengkap, langkah reproduksi, endpoint/halaman terkait, response API (status code + body) jika ada. Jika user melaporkan `trace_id`/`X-Request-Id` dari response error (lihat rule `error-handling-and-request-tracing`), **gunakan itu untuk langsung grep log backend** (`grep 'request_id":"<id>"' storage/logs/laravel.log`) — ini jauh lebih cepat daripada menelusuri berdasarkan waktu perkiraan.
2. **Telusuri sesuai layer** (rule `architecture-layering`), dari luar ke dalam:
   - Component Vue -> API Facade -> Generated Client (Orval) -> Axios -> Laravel Controller -> Service/Action -> Database.
   - Cek di layer mana data pertama kali menjadi tidak sesuai ekspektasi.
3. **Bug di frontend**:
   - Cek Network tab: request/response payload sudah sesuai contract OpenAPI?
   - Cek state TanStack Query (`isLoading`, `isError`, `error`) — apakah error di-handle dengan benar dan ditampilkan lewat SweetAlert2?
   - Cek Pinia store terkait (auth/permission) jika bug berkaitan dengan akses/otorisasi UI.
4. **Bug di backend**:
   - Cek log Laravel (`storage/logs/laravel.log`).
   - Cek apakah FormRequest memvalidasi dengan benar, Policy mengizinkan/menolak sesuai ekspektasi.
   - Cek query database (N+1 problem, eager loading kurang, dsb) — pertimbangkan `DB::enableQueryLog()` sementara untuk debug.
   - Jika terkait Queue Job, cek Horizon dashboard untuk job yang gagal/`failed_jobs` table.
5. **Setelah root cause ditemukan**, perbaiki di **sumber masalah** (bukan menambal di layer yang salah, misal jangan menambal bug backend dengan workaround di frontend).
6. Tambahkan/ubah test (ikuti rule `testing-strategy`) untuk mencegah regresi di masa depan.

## Checklist

- [ ] Root cause sudah teridentifikasi dengan jelas (bukan sekadar workaround).
- [ ] Perbaikan dilakukan di layer yang tepat sesuai arsitektur.
- [ ] Test ditambahkan/diperbarui untuk kasus bug ini.
