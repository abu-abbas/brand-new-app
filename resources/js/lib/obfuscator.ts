const PRIME = 16807n;
const INVERSE = 1407677000n;
const MODULO = 2147483647n; // 2^31 - 1
const MIN_LENGTH = 5;
const BASE_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

export class Obfuscator {
  /**
   * Mengambil Salt default dari VITE env atau fallback.
   */
  public static getSalt(): string {
    if (typeof import.meta !== 'undefined' && import.meta.env?.VITE_OBFUSCATOR_SALT) {
      return import.meta.env.VITE_OBFUSCATOR_SALT;
    }

    if (typeof document !== 'undefined') {
      const meta = document.querySelector('meta[name="app-hash-salt"]');
      if (meta && meta.getAttribute('content')) {
        return meta.getAttribute('content')!;
      }
    }

    return 'default-brand-new-app-salt-2026';
  }

  private static getSaltKey(salt: string): bigint {
    let hash = 0n;
    const modulo = 2147483647n;
    for (let i = 0; i < salt.length; i++) {
      const charCode = BigInt(salt.charCodeAt(i));
      hash = (hash * 31n + charCode) % modulo;
    }
    return hash === 0n ? 0x5a3f9c12n : hash;
  }

  private static getAlphabet(salt: string): string {
    const chars = BASE_ALPHABET.split('');
    const saltKey = Number(this.getSaltKey(salt));
    const count = chars.length;

    for (let i = count - 1; i > 0; i--) {
      const j = Math.abs((saltKey + i) % (i + 1));
      const temp = chars[i];
      chars[i] = chars[j];
      chars[j] = temp;
    }

    return chars.join('');
  }

  /**
   * Mengubah ID integer menjadi Obfuscated String (min 5 digit).
   */
  public static encode(id: number, salt?: string): string {
    if (id <= 0) {
      return '';
    }

    const currentSalt = salt ?? this.getSalt();
    const saltKey = this.getSaltKey(currentSalt);
    const alphabet = this.getAlphabet(currentSalt);

    // 1. XOR dengan salt key
    let xPrime = (BigInt(id) ^ saltKey) % MODULO;
    if (xPrime <= 0n) {
      xPrime += MODULO;
    }

    // 2. Multiplicative Obfuscation
    const y = (xPrime * PRIME) % MODULO;

    // 3. Base62 Encode
    const code = this.toBase62(y, alphabet);

    // 4. Pad ke minimal MIN_LENGTH
    return this.padLeft(code, alphabet);
  }

  /**
   * Mengubah Obfuscated String kembali menjadi Integer ID asli.
   */
  public static decode(code: string, salt?: string): number | null {
    if (!code) {
      return null;
    }

    const currentSalt = salt ?? this.getSalt();
    const saltKey = this.getSaltKey(currentSalt);
    const alphabet = this.getAlphabet(currentSalt);

    if (code.length < MIN_LENGTH) {
      return null;
    }

    // 1. Hapus padding dari kiri
    const rawCode = this.unpadLeft(code, alphabet);
    if (!rawCode) {
      return null;
    }

    // 2. Base62 Decode
    const y = this.fromBase62(rawCode, alphabet);
    if (y <= 0n) {
      return null;
    }

    // 3. Multiplicative Deobfuscation
    const xPrime = (y * INVERSE) % MODULO;
    const idNum = Number(xPrime ^ saltKey);

    if (idNum <= 0) {
      return null;
    }

    // 4. Integrity check
    if (this.encode(idNum, currentSalt) !== code) {
      return null;
    }

    return idNum;
  }

  private static toBase62(num: bigint, alphabet: string): string {
    const base = BigInt(alphabet.length);
    let res = '';
    let current = num;

    while (current > 0n) {
      const rem = Number(current % base);
      res = alphabet[rem] + res;
      current = current / base;
    }

    return res || alphabet[0];
  }

  private static fromBase62(str: string, alphabet: string): bigint {
    const base = BigInt(alphabet.length);
    let num = 0n;

    for (let i = 0; i < str.length; i++) {
      const pos = alphabet.indexOf(str[i]);
      if (pos === -1) {
        return 0n;
      }
      num = num * base + BigInt(pos);
    }

    return num;
  }

  /**
   * Pad string ke kiri hingga MIN_LENGTH menggunakan alphabet[0].
   * rawCode dari y > 0 TIDAK PERNAH dimulai alphabet[0] (leading zero),
   * sehingga padding bisa di-strip dengan aman.
   */
  private static padLeft(code: string, alphabet: string): string {
    if (code.length >= MIN_LENGTH) {
      return code;
    }

    const padChar = alphabet[0];
    const padCount = MIN_LENGTH - code.length;

    return padChar.repeat(padCount) + code;
  }

  /**
   * Hapus padding karakter dari kiri (leading alphabet[0]).
   */
  private static unpadLeft(code: string, alphabet: string): string {
    const padChar = alphabet[0];
    let start = 0;

    while (start < code.length && code[start] === padChar) {
      start++;
    }

    return code.substring(start);
  }
}
