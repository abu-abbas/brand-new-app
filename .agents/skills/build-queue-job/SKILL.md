---
name: build-queue-job
description: Membangun Queue Job baru di Laravel (dengan Horizon) untuk proses async seperti kirim email, export/import data besar, notifikasi. Gunakan saat user minta proses yang berjalan di background.
---

# Build Queue Job

## Kapan skill ini dipakai

Saat user minta sebuah proses dijalankan secara async/background (kirim email massal, generate report besar, import data besar, dsb).

## Langkah Kerja

1. Buat Job class (`php artisan make:job <NamaJob>`), implementasikan `ShouldQueue`.
2. Tentukan **queue name** yang sesuai (misal `emails`, `exports`, `default`) supaya bisa diatur concurrency-nya lewat **Horizon** config (`config/horizon.php`).
3. Business logic job **delegasikan ke Service/Action class**, jangan taruh semua logic di method `handle()` Job supaya bisa di-unit-test terpisah.
4. Tangani **failure**: implementasikan method `failed()` untuk logging/notifikasi jika job gagal setelah retry habis, set `$tries` dan `$backoff` sesuai kebutuhan.
5. Jika job perlu memberi tahu progress ke user (misal export besar), pertimbangkan simpan status di tabel/`cache` yang di-poll oleh frontend lewat TanStack Query (polling interval) atau broadcast event (jika sudah ada infrastruktur broadcasting).
6. Dispatch job dari Controller/Service, **jangan** dispatch job langsung dari route/closure tanpa lewat layer Service.
7. Tambahkan endpoint API (jika perlu) untuk cek status job, ikuti skill `build-api` untuk konsistensi (Controller -> FormRequest -> Resource).

## Checklist

- [ ] Job memakai `ShouldQueue` dan queue name sudah didaftarkan di Horizon config.
- [ ] Logic berat didelegasikan ke Service/Action, bukan langsung di `handle()`.
- [ ] Ada penanganan failure (`failed()`, retry, backoff).
- [ ] Jika ada status yang perlu ditampilkan ke user, sudah ada mekanisme polling/notifikasi di frontend (bukan blocking request).
