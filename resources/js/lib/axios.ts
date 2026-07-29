import axios, { AxiosError, type AxiosRequestConfig } from 'axios';
import { clearDataTableMemory } from '../components/custom-ui/data-table/data-table.utils';

type RetriableAxiosRequestConfig = AxiosRequestConfig & {
  csrfRetried?: boolean;
};

export interface AppError {
  message: string;
  code?: string;
  status?: number;
  retryable: boolean;
  validationErrors?: Record<string, unknown>;
  requestId?: string;
  supportId?: string;
  whatsappUrl?: string;
  retryAfterMs?: number;
  cause?: unknown;
}

export const axiosInstance = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
  withCredentials: true,
  withXSRFToken: true,
});

axiosInstance.interceptors.request.use((config) => {
  if (!config.headers['X-Request-Id']) {
    const uuid = crypto.randomUUID();
    config.headers['X-Request-Id'] = uuid;
  }
  return config;
});

export function extractSupportId(html: string): string | null {
  const spanMatch = html.match(/<span[^>]*id=["']sp-id["'][^>]*>([\s\S]*?)<\/span>/i);
  if (spanMatch && spanMatch[1]) {
    const extracted = spanMatch[1].trim();
    if (extracted) return extracted;
  }

  const textMatch = html.match(/Support ID Anda\s*:\s*([\d\w-]+)/i);
  if (textMatch && textMatch[1]) {
    return textMatch[1].trim();
  }

  return null;
}

export function extractPhoneNumber(html: string): string | null {
  const waMatch = html.match(/phone=([0-9]+)/i);
  if (waMatch && waMatch[1]) {
    return waMatch[1].trim();
  }

  const textMatch = html.match(/(\+?62\d{9,13})/i);
  if (textMatch && textMatch[1]) {
    return textMatch[1].replace(/^\+/, '').trim();
  }

  return null;
}

export function buildWhatsappUrl(
  phoneNumber: string | null,
  supportId: string | null,
): string | null {
  if (!phoneNumber) return null;
  const cleanPhone = phoneNumber.replace(/^\+/, '');
  const messageText = supportId
    ? `Halo Admin Saya terkena Support id ${supportId}`
    : 'Halo Admin Saya terkena penolakan firewall';
  return `https://api.whatsapp.com/send/?phone=${cleanPhone}&type=phone_number&app_absent=0&text=${encodeURIComponent(messageText)}`;
}

export function isFirewallBlocked(data: string): boolean {
  if (typeof data !== 'string') return false;
  return (
    data.includes('URL YANG DIMINTA DI TOLAK') ||
    data.includes('URL YANG DIMINTA DITOLAK') ||
    data.includes('id="sp-id"') ||
    data.includes('Support ID Anda')
  );
}

function parseRetryAfter(value: unknown): number | undefined {
  if (typeof value !== 'string' && typeof value !== 'number') return undefined;
  const seconds = Number(value);
  if (Number.isFinite(seconds) && seconds >= 0) return seconds * 1000;
  const date = Date.parse(String(value));
  return Number.isNaN(date) ? undefined : Math.max(0, date - Date.now());
}

export function normalizeAppError(error: unknown): AppError {
  const candidate = error as {
    message?: string;
    code?: string;
    status?: number;
    retryable?: boolean;
    errors?: Record<string, unknown>;
    validationErrors?: Record<string, unknown>;
    requestId?: string;
    supportId?: string;
    whatsappUrl?: string;
    support_id?: string;
    whatsapp_url?: string;
    retryAfterMs?: number;
    cause?: unknown;
    response?: {
      status?: number;
      data?: {
        message?: string;
        code?: string;
        retryable?: boolean;
        errors?: Record<string, unknown>;
        support_id?: string;
        whatsapp_url?: string;
      };
      headers?: Record<string, unknown>;
    };
  };
  const data = candidate?.response?.data ?? candidate;
  const status = candidate?.response?.status ?? candidate?.status;
  const rawMessage = typeof data?.message === 'string' ? data.message.trim() : '';
  const technical =
    !rawMessage ||
    /^(network error|failed to fetch|axioserror|request failed|500 internal|fetch failed)/i.test(
      rawMessage,
    );
  const headers = candidate?.response?.headers;

  return {
    message: technical ? 'Terjadi kesalahan saat memproses permintaan.' : rawMessage,
    code: data?.code,
    status,
    retryable: data?.retryable ?? (!status || status === 429 || status >= 500),
    validationErrors: candidate?.validationErrors ?? data?.errors,
    requestId: candidate?.requestId ?? (headers?.['x-request-id'] as string | undefined),
    supportId: candidate?.supportId ?? data?.support_id,
    whatsappUrl: candidate?.whatsappUrl ?? data?.whatsapp_url,
    retryAfterMs: candidate?.retryAfterMs ?? parseRetryAfter(headers?.['retry-after']),
    cause: candidate?.cause ?? error,
  };
}

axiosInstance.interceptors.response.use(
  (response) => {
    // Deteksi jika response status 2xx tetapi berisi HTML block dari firewall
    if (typeof response.data === 'string' && isFirewallBlocked(response.data)) {
      const supportId = extractSupportId(response.data);
      const phoneNumber = extractPhoneNumber(response.data) || '6281313588684';
      const whatsappUrl = buildWhatsappUrl(phoneNumber, supportId);

      const message = 'Permintaan ditolak oleh firewall. Silakan hubungi Call Center.';

      const firewallError = new AxiosError(
        message,
        'ERR_FIREWALL_BLOCKED',
        response.config,
        response.request,
        {
          ...response,
          status: 403,
          statusText: 'Forbidden (Firewall Blocked)',
          data: {
            message,
            code: 'SYS-FW-001',
            retryable: false,
            support_id: supportId,
            phone_number: phoneNumber,
            whatsapp_url: whatsappUrl,
          },
        },
      );

      return Promise.reject(normalizeAppError(firewallError));
    }

    return response;
  },
  async (error: AxiosError) => {
    const config = error.config as RetriableAxiosRequestConfig | undefined;
    const status = error.response?.status;

    if (status === 419 && config && !config.csrfRetried && config.url !== '/sanctum/csrf-cookie') {
      config.csrfRetried = true;
      await axiosInstance.get('/sanctum/csrf-cookie', { baseURL: '/' });

      return axiosInstance(config);
    }

    if (
      (status === 401 || status === 419) &&
      typeof window !== 'undefined' &&
      window.location.pathname !== '/login'
    ) {
      clearDataTableMemory();
      const intendedUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
      window.location.assign(`/login?redirect=${encodeURIComponent(intendedUrl)}`);
    }

    return Promise.reject(normalizeAppError(error));
  },
);

export const customAxiosInstance = <T>(
  config: AxiosRequestConfig,
  options?: AxiosRequestConfig,
): Promise<T> => {
  return axiosInstance({ ...config, ...options }).then(({ data }) => data);
};
