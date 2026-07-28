import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useServerClock } from './useServerClock';

describe('useServerClock', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('provides formattedTime, formattedDate, and formattedDateTime', () => {
    const { formattedTime, formattedDate, formattedDateTime, stopClock } = useServerClock();

    expect(formattedTime.value).toMatch(/^\d{2}:\d{2}:\d{2}$/);
    expect(formattedDate.value).toMatch(/^\d{4}-\d{2}-\d{2}$/);
    expect(formattedDateTime.value).toContain(formattedDate.value);
    expect(formattedDateTime.value).toContain(formattedTime.value);

    stopClock();
  });
});
