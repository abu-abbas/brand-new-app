# ID Obfuscation — Wajib untuk Semua API Resource

## Aturan Utama

- **DILARANG KERAS** mengekspos raw integer ID (primary key angka seperti `id`, `i_id`, dll.) secara langsung
  di API response mana pun. Raw integer ID wajib di-obfuscate terlebih dahulu menggunakan `Obfuscator`.
- Setiap Model yang primary key-nya integer **WAJIB** menggunakan trait `HasObfuscatedId`
  (`App\Traits\HasObfuscatedId`). Gunakan accessor `$model->hash_id` sebagai identifier publik.
- Di dalam API Resource (`JsonResource`), field yang merepresentasikan identifier record **WAJIB**
  menggunakan `$this->hash_id`, bukan `$this->id`.

## Contoh Benar — UserResource (Model dengan Integer Primary Key)

```php
// app/Http/Resources/UserResource.php
public function toArray(Request $request): array
{
    return [
        'id' => $this->hash_id,   // ✅ hash_id dari trait HasObfuscatedId
        'name' => $this->name,
        // ...
    ];
}
```

```php
// app/Models/User.php
use App\Traits\HasObfuscatedId;

class User extends Authenticatable
{
    use HasObfuscatedId; // ✅ wajib dipasang
    // ...
}
```

## Pengecualian yang Diizinkan

Model **TIDAK PERLU** memakai `HasObfuscatedId` jika identifiernya bukan integer, melainkan
slug/alias string yang sudah semantik dan aman diekspos. Contoh:

```php
// app/Models/Feature.php — route key adalah v_alias (string slug), bukan integer ID
public function getRouteKeyName(): string
{
    return 'v_alias'; // ✅ string alias, aman diekspos langsung
}
```

```php
// app/Http/Resources/FeatureResource.php — tidak expose integer ID sama sekali
public function toArray(Request $request): array
{
    return [
        'alias' => $this->v_alias, // ✅ alias string, bukan integer primary key
        // ...
        // 'id' TIDAK ADA di response ini — benar!
    ];
}
```

## Aturan Saat Membuat Modul Baru

1. **Cek primary key model**: apakah integer (auto-increment / bigint)? → pasang `HasObfuscatedId`.
2. **Cek API Resource**: apakah ada field `id` atau identifier yang merujuk ke integer primary key?
   → wajib gunakan `$this->hash_id`.
3. **Jangan membuat field alias seperti** `raw_id`, `internal_id`, `original_id`, atau sejenisnya
   yang kembali mengekspos integer primary key.
4. **Route Model Binding** sudah otomatis di-handle trait `HasObfuscatedId` — URL parameter berupa
   `hash_id` akan otomatis di-decode ke model yang sesuai.
5. **FormRequest** yang menerima ID dari client (misal untuk update/delete) harus validasi bahwa
   nilai yang masuk adalah hash_id yang valid, bukan integer mentah.

## File Referensi

- Trait: `app/Traits/HasObfuscatedId.php`
- Obfuscator: `app/Support/Obfuscator.php`
- Contoh model: `app/Models/User.php`
- Contoh resource: `app/Http/Resources/UserResource.php`
