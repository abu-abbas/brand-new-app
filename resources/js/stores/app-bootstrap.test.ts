/// <reference types="../env.d.ts" />
import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAppBootstrapStore } from './app-bootstrap';

describe('AppBootstrapStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('initializes with default value if window.__APP_CONFIG__ is undefined', () => {
    delete window.__APP_CONFIG__;
    const store = useAppBootstrapStore();
    expect(store.config.name).toBe('Laravel');
    expect(store.config.locale).toBe('en');
  });

  it('reads config from window.__APP_CONFIG__ if defined', () => {
    window.__APP_CONFIG__ = {
      name: 'E-Office App',
      env: 'local',
      url: 'http://localhost',
      timezone: 'Asia/Jakarta',
      locale: 'id',
      current_date: '2026-07-28',
    };

    const store = useAppBootstrapStore();
    expect(store.config.name).toBe('E-Office App');
    expect(store.config.timezone).toBe('Asia/Jakarta');
    expect(store.config.locale).toBe('id');
    expect(store.config.current_date).toBe('2026-07-28');
  });
});
