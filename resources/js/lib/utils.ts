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
