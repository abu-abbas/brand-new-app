import type { ClassValue } from 'clsx';
import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

/**
 * Mengonversi format markdown sederhana (inline code, bold, italic) menjadi string HTML.
 */
export function renderMarkdown(text: string): string {
  if (!text) return '';
  return text
    .replace(
      /`([^`]+)`/g,
      '<code class="relative rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-foreground">$1</code>',
    )
    .replace(/\*\*([^*]+)\*\*/g, '<strong class="font-semibold text-foreground">$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em class="italic">$1</em>');
}

/**
 * Mengonversi string tanggal menjadi format human readable (d MMM yyyy, contoh: 28 Jul 2026).
 */
export function formatHumanDate(dateStr?: string): string {
  if (!dateStr) return 'Hari ini';

  try {
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return dateStr;

    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(date);
  } catch {
    return dateStr;
  }
}
