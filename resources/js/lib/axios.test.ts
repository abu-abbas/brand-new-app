import { describe, expect, it } from 'vitest';
import {
  extractSupportId,
  extractPhoneNumber,
  buildWhatsappUrl,
  isFirewallBlocked,
  normalizeAppError,
} from './axios';
import mockFirewallHtml from '../../views/support-id.blade.php?raw';

describe('Axios Firewall Interceptor', () => {
  it('dapat mendeteksi respons HTML firewall block', () => {
    expect(isFirewallBlocked(mockFirewallHtml)).toBe(true);
    expect(isFirewallBlocked('{"message": "success"}')).toBe(false);
    expect(isFirewallBlocked('<html><body>Error 500</body></html>')).toBe(false);
  });

  it('dapat mengekstrak Support ID dari HTML firewall', () => {
    const supportId = extractSupportId(mockFirewallHtml);
    expect(supportId).toBe('4499979717396997446');
  });

  it('mengembalikan null jika Support ID tidak ditemukan', () => {
    const supportId = extractSupportId('<html><body>URL YANG DIMINTA DI TOLAK</body></html>');
    expect(supportId).toBeNull();
  });

  it('dapat mengekstrak nomor WhatsApp dari HTML firewall', () => {
    const phone = extractPhoneNumber(mockFirewallHtml);
    expect(phone).toBe('6281313588684');
  });

  it('dapat membuat WhatsApp URL dengan prefilled text support ID', () => {
    const url = buildWhatsappUrl('6281313588684', '4499979717396997446');
    expect(url).toContain('https://api.whatsapp.com/send/?phone=6281313588684');
    expect(url).toContain('Halo%20Admin%20Saya%20terkena%20Support%20id%204499979717396997446');
  });
});

describe('AppError normalizer', () => {
  it('menyatukan validation error dan mempertahankan kontrak eksplisit backend', () => {
    const error = normalizeAppError({
      response: {
        status: 422,
        data: {
          message: 'Validasi gagal.',
          retryable: false,
          errors: { email: [{ code: 'USR-VAL-001', message: 'Email wajib diisi.' }] },
        },
        headers: { 'x-request-id': 'request-1' },
      },
    });

    expect(error).toMatchObject({
      message: 'Validasi gagal.',
      status: 422,
      retryable: false,
      validationErrors: { email: [{ code: 'USR-VAL-001', message: 'Email wajib diisi.' }] },
      requestId: 'request-1',
    });

    expect(normalizeAppError(error).validationErrors).toEqual(error.validationErrors);
  });

  it('menandai 429 retryable dan menghormati Retry-After', () => {
    expect(
      normalizeAppError({
        response: {
          status: 429,
          data: { message: 'Terlalu banyak permintaan.' },
          headers: { 'retry-after': '3' },
        },
      }),
    ).toMatchObject({
      status: 429,
      retryable: true,
      retryAfterMs: 3000,
    });
  });
});
