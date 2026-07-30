import type { Component } from 'vue';
import * as LucideIcons from '@lucide/vue';

/**
 * Resolves a Lucide icon component dynamically from a string name (PascalCase or kebab-case) or Component object.
 */
export function getLucideIcon(
  name?: string | Component | null,
  fallback?: string | Component | null,
): Component | null {
  if (!name) {
    if (!fallback) return null;
    return typeof fallback === 'string' ? getLucideIcon(fallback, null) : fallback;
  }

  if (typeof name !== 'string') {
    return name;
  }

  const icons = LucideIcons as unknown as Record<string, Component>;
  const pascalName = name.replace(/(^\w|-\w)/g, (clear) => clear.replace('-', '').toUpperCase());
  const found = icons[pascalName] || icons[name];
  if (found) return found;

  if (!fallback) return null;
  return typeof fallback === 'string' ? getLucideIcon(fallback, null) : fallback;
}
