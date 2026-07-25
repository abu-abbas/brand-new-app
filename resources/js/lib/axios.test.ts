import { describe, expect, it } from 'vitest';
import { extractSupportId, extractPhoneNumber, buildWhatsappUrl, isFirewallBlocked } from './axios';

const mockFirewallHtml = `
<!DOCTYPE html>
<html>
<head><title>Firewall Blocked</title></head>
<body>
    <div class="content">
        <h2><span>&#9888;</span> URL YANG DIMINTA DI TOLAK <span>&#9888;</span></h2>
        <p><b>Silahkan Konsultasikan dengan Call Center UP Layanan Teknologi Informasi dan Komunikasi</b></p>
        <div class="red-box">
            <p>Support ID Anda : <span id="sp-id">
                4499979717396997446
            </span></p>
        </div>
        <h3><a id="wa_ccltik" href="#">&#128222;+6281313588684</a></h3>
    </div>
</body>
</html>
`;

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
