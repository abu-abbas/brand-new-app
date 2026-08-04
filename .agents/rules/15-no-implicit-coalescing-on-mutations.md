# Strict Payload Mutation & Anti-Implicit Coalescing (Wajib)

## Aturan Utama

1. **DILARANG HARAM** menggunakan operator fallback/coalescing implisit (`??`, `||`, `?:`) saat memproses operasi pembuatan data (insert/create), perubahan data (update/edit), maupun penghapusan data (delete), KECUALI jika ada instruksi bisnis yang secara eksplisit meminta fallback default tersebut.

2. **DILARANG MENYAMAKAN KEY TIDAK DIKIRIM DENGAN NILAI NULL**:
   - Pada request update/partial update (`PUT` / `PATCH`), key yang tidak ada di dalam payload request **TIDAK BOLEH** di-overwrite menjadi `null` atau diisi nilai fallback tebakan.
   - Pengecekan ketersediaan key **WAJIB** menggunakan `array_key_exists('field', $data)` atau `$this->has('field')` secara eksplisit.
   - Hanya ubah field di database jika key tersebut memang dikirimkan secara eksplisit dalam request payload.

3. **GAGALKAN EKSPLISIT LEWAT VALIDASI**:
   - Apabila ada data yang dibutuhkan (required) namun tidak dikirim dalam payload, **DILARANG** menutup-nutupi masalah dengan membuat tebakan nilai bawaan (fallback).
   - Biarkan validasi backend (`FormRequest`) menolak request tersebut secara eksplisit melalui error validasi `422`.

4. **DILARANG MEMBUAT FALLBACK BERLAPIS**:
   - Contoh larangan: `$unitCode = $data['unit'] ?? $roleUnit ?? $user->v_kolok ?? Auth::user()->v_kolok;`
   - Setiap assignment ke model/database harus murni mencerminkan payload aktual atau state terverifikasi dari entitas target.
