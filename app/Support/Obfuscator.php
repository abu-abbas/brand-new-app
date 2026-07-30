<?php

namespace App\Support;

class Obfuscator
{
    private const PRIME = 16807;

    private const INVERSE = 1407677000;

    private const MODULO = 2147483647; // 2^31 - 1 (Mersenne Prime)

    private const MIN_LENGTH = 5;

    private const BASE_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Mengambil salt default dari env / app.key.
     */
    public static function getSalt(): string
    {
        $salt = config('app.obfuscator_salt') ?? env('VITE_OBFUSCATOR_SALT');

        if (empty($salt)) {
            $salt = config('app.key', 'default-brand-new-app-salt-2026');
        }

        return (string) $salt;
    }

    /**
     * Menghasilkan integer 32-bit hash dari Salt string.
     */
    private static function getSaltKey(string $salt): int
    {
        $hash = 0;
        $len = strlen($salt);
        for ($i = 0; $i < $len; $i++) {
            $hash = (($hash * 31) + ord($salt[$i])) % self::MODULO;
        }

        return $hash === 0 ? 0x5A3F9C12 : $hash;
    }

    /**
     * Men-shuffle alphabet 62 karakter secara deterministik berdasarkan salt key.
     */
    private static function getAlphabet(string $salt): string
    {
        $chars = str_split(self::BASE_ALPHABET);
        $saltKey = self::getSaltKey($salt);
        $count = count($chars);

        for ($i = $count - 1; $i > 0; $i--) {
            $j = ($saltKey + $i) % ($i + 1);
            $temp = $chars[$i];
            $chars[$i] = $chars[$j];
            $chars[$j] = $temp;
        }

        return implode('', $chars);
    }

    /**
     * Mengubah ID integer menjadi Obfuscated String (min 5 digit).
     */
    public static function encode(int $id, ?string $salt = null): string
    {
        if ($id <= 0) {
            return '';
        }

        $salt = $salt ?? self::getSalt();
        $saltKey = self::getSaltKey($salt);
        $alphabet = self::getAlphabet($salt);

        // 1. XOR dengan salt key
        $xPrime = ($id ^ $saltKey) % self::MODULO;
        if ($xPrime <= 0) {
            $xPrime += self::MODULO;
        }

        // 2. Multiplicative Obfuscation
        $y = (int) (($xPrime * self::PRIME) % self::MODULO);

        // 3. Base62 Encode
        $code = self::toBase62($y, $alphabet);

        // 4. Pad ke minimal MIN_LENGTH dengan karakter deterministik
        return self::padLeft($code, $y, $alphabet);
    }

    /**
     * Mengubah Obfuscated String kembali menjadi Integer ID asli.
     */
    public static function decode(string $code, ?string $salt = null): ?int
    {
        if (empty($code)) {
            return null;
        }

        $salt = $salt ?? self::getSalt();
        $saltKey = self::getSaltKey($salt);
        $alphabet = self::getAlphabet($salt);

        $len = strlen($code);

        if ($len < self::MIN_LENGTH) {
            return null;
        }

        // 1. Tentukan rawCode: potong prefix padding
        $rawCode = self::unpadLeft($code, $alphabet);
        if ($rawCode === '') {
            return null;
        }

        // 2. Base62 Decode
        $y = self::fromBase62($rawCode, $alphabet);
        if ($y <= 0) {
            return null;
        }

        // 3. Multiplicative Deobfuscation
        $xPrime = (int) (($y * self::INVERSE) % self::MODULO);
        $id = $xPrime ^ $saltKey;

        if ($id <= 0) {
            return null;
        }

        // 4. Integrity check: pastikan jika di-encode ulang hasilnya identik
        if (self::encode($id, $salt) !== $code) {
            return null;
        }

        return $id;
    }

    private static function toBase62(int $num, string $alphabet): string
    {
        $base = strlen($alphabet);
        $res = '';
        while ($num > 0) {
            $rem = $num % $base;
            $res = $alphabet[$rem].$res;
            $num = (int) ($num / $base);
        }

        return $res ?: $alphabet[0];
    }

    private static function fromBase62(string $str, string $alphabet): int
    {
        $base = strlen($alphabet);
        $num = 0;
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos($alphabet, $str[$i]);
            if ($pos === false) {
                return 0;
            }
            $num = ($num * $base) + $pos;
        }

        return $num;
    }

    /**
     * Pad string ke kiri hingga MIN_LENGTH.
     * Padding chars deterministik dari $y agar decode bisa memvalidasi.
     * Format: [padChars...][rawCode]
     * Karena rawCode terakhir hanya berisi karakter alphabet base62,
     * kita tahu panjang rawCode asli dari base62($y): cukup strip
     * padding dari kiri sampai tersisa rawCode yang valid.
     *
     * Strategi: gunakan alphabet[0] sebagai padding char HANYA jika
     * leading digit rawCode bukan alphabet[0]. Tapi ini rumit.
     *
     * Strategi lebih simpel: rawCode dari y (di mana y > 0) TIDAK PERNAH
     * dimulai oleh alphabet[0] (karena itu berarti leading zero).
     * Jadi padding pakai alphabet[0] sebagai filler.
     */
    private static function padLeft(string $code, int $y, string $alphabet): string
    {
        $len = strlen($code);
        if ($len >= self::MIN_LENGTH) {
            return $code;
        }

        $padChar = $alphabet[0];
        $padCount = self::MIN_LENGTH - $len;

        return str_repeat($padChar, $padCount).$code;
    }

    /**
     * Hapus padding karakter dari kiri.
     * Padding char = alphabet[0] (leading zeros).
     */
    private static function unpadLeft(string $code, string $alphabet): string
    {
        $padChar = $alphabet[0];

        return ltrim($code, $padChar);
    }
}
