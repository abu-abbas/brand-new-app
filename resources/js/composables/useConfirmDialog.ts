import { reactive } from 'vue';
import type { ButtonVariants } from '@/components/ui/button';

type ConfirmVariant = NonNullable<ButtonVariants['variant']>;

export interface ConfirmDialogOptions {
  title: string;
  description?: string;
  confirmLabel?: string | false;
  cancelLabel?: string | false;
  loadingLabel?: string;
  confirmVariant?: ConfirmVariant;
  reverseActions?: boolean;
  onConfirm?: () => void | Promise<void>;
  successTitle?: string;
  successDescription?: string;
  successAutoCloseSeconds?: number | false;
  errorTitle?: string;
  errorDescription?: string | ((error: unknown) => string);
  resultCloseLabel?: string;
}

interface ResolvedConfirmDialogOptions {
  title: string;
  description?: string;
  confirmLabel: string | false;
  cancelLabel: string | false;
  loadingLabel: string;
  confirmVariant: ConfirmVariant;
  reverseActions: boolean;
  onConfirm?: () => void | Promise<void>;
  successTitle: string;
  successDescription: string;
  successAutoCloseSeconds: number | false;
  errorTitle: string;
  errorDescription?: string | ((error: unknown) => string);
  resultCloseLabel: string;
}

export type ConfirmDialogPhase = 'confirm' | 'success' | 'error';

export const confirmDialogState = reactive<{
  open: boolean;
  loading: boolean;
  phase: ConfirmDialogPhase;
  countdown: number;
  error?: unknown;
  options: ResolvedConfirmDialogOptions;
}>({
  open: false,
  loading: false,
  phase: 'confirm',
  countdown: 0,
  options: {
    title: '',
    confirmLabel: 'Lanjutkan',
    cancelLabel: 'Batal',
    loadingLabel: 'Memproses...',
    confirmVariant: 'default',
    reverseActions: false,
    successTitle: 'Berhasil',
    successDescription: 'Proses berhasil diselesaikan.',
    successAutoCloseSeconds: 3,
    errorTitle: 'Proses gagal',
    resultCloseLabel: 'Tutup',
  },
});

let resolveCurrent: ((confirmed: boolean) => void) | undefined;
let rejectCurrent: ((reason: unknown) => void) | undefined;
let countdownTimer: ReturnType<typeof globalThis.setInterval> | undefined;

export function useConfirmDialog() {
  return (options: ConfirmDialogOptions): Promise<boolean> => {
    if (confirmDialogState.open) {
      return Promise.reject(new Error('Confirm dialog lain masih terbuka.'));
    }

    confirmDialogState.options = {
      confirmLabel: 'Lanjutkan',
      cancelLabel: 'Batal',
      loadingLabel: 'Memproses...',
      confirmVariant: 'default',
      reverseActions: false,
      successTitle: 'Berhasil',
      successDescription: 'Proses berhasil diselesaikan.',
      successAutoCloseSeconds: 3,
      errorTitle: 'Proses gagal',
      resultCloseLabel: 'Tutup',
      ...options,
    };
    confirmDialogState.phase = 'confirm';
    confirmDialogState.countdown = 0;
    confirmDialogState.error = undefined;
    confirmDialogState.open = true;

    return new Promise<boolean>((resolve, reject) => {
      resolveCurrent = resolve;
      rejectCurrent = reject;
    });
  };
}

export function dismissConfirmDialog(): void {
  if (confirmDialogState.loading || !resolveCurrent) return;

  if (confirmDialogState.phase === 'success') {
    resolveDialog(true);
  } else if (confirmDialogState.phase === 'error') {
    rejectDialog(confirmDialogState.error);
  } else {
    resolveDialog(false);
  }
}

export async function acceptConfirmDialog(): Promise<void> {
  if (confirmDialogState.loading || !resolveCurrent) return;

  confirmDialogState.loading = true;

  try {
    if (!confirmDialogState.options.onConfirm) {
      resolveDialog(true);
      return;
    }

    await confirmDialogState.options.onConfirm();
    confirmDialogState.loading = false;
    confirmDialogState.phase = 'success';
    startSuccessCountdown();
  } catch (error) {
    confirmDialogState.loading = false;
    confirmDialogState.phase = 'error';
    confirmDialogState.error = error;
  }
}

export function updateConfirmDialogOpen(open: boolean): void {
  if (open) {
    confirmDialogState.open = true;
    return;
  }

  dismissConfirmDialog();
}

function clearCurrent(): void {
  if (countdownTimer) globalThis.clearInterval(countdownTimer);

  countdownTimer = undefined;
  resolveCurrent = undefined;
  rejectCurrent = undefined;
}

function startSuccessCountdown(): void {
  const seconds = confirmDialogState.options.successAutoCloseSeconds;
  if (seconds === false) return;

  confirmDialogState.countdown = Math.max(0, Math.ceil(seconds));
  if (confirmDialogState.countdown === 0) {
    resolveDialog(true);
    return;
  }

  countdownTimer = globalThis.setInterval(() => {
    confirmDialogState.countdown -= 1;
    if (confirmDialogState.countdown <= 0) resolveDialog(true);
  }, 1000);
}

function resolveDialog(confirmed: boolean): void {
  const resolve = resolveCurrent;
  confirmDialogState.open = false;
  confirmDialogState.loading = false;
  clearCurrent();
  resolve?.(confirmed);
}

function rejectDialog(error: unknown): void {
  const reject = rejectCurrent;
  confirmDialogState.open = false;
  confirmDialogState.loading = false;
  clearCurrent();
  reject?.(error);
}
