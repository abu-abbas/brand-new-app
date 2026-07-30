import { describe, expect, it } from 'vitest';
import { Obfuscator } from '../obfuscator';

describe('Obfuscator TypeScript', () => {
  it('should encode integer to obfuscated string of min length 5', () => {
    const salt = 'my-secret-salt-2026';
    const code1 = Obfuscator.encode(1, salt);
    const code2 = Obfuscator.encode(2, salt);

    expect(code1.length).toBeGreaterThanOrEqual(5);
    expect(code2.length).toBeGreaterThanOrEqual(5);
    expect(code1).not.toBe(code2);
  });

  it('should decode obfuscated string back to original integer', () => {
    const salt = 'my-secret-salt-2026';

    for (let id = 1; id <= 100; id++) {
      const encoded = Obfuscator.encode(id, salt);
      const decoded = Obfuscator.decode(encoded, salt);

      expect(decoded).toBe(id);
    }
  });

  it('should return null for invalid code', () => {
    expect(Obfuscator.decode('', 'salt')).toBeNull();
  });
});
