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
    expect(store.config.references.permission_types).toEqual([]);
  });

  it('reads config from window.__APP_CONFIG__ if defined', () => {
    window.__APP_CONFIG__ = {
      name: 'E-Office App',
      env: 'local',
      url: 'http://localhost',
      timezone: 'Asia/Jakarta',
      locale: 'id',
      current_date: '2026-07-28',
      current_fulldate: '2026-07-28T00:00:00+07:00',
      references: {
        permission_types: [
          {
            value: 'menu',
            label: 'Menu Sidebar',
          },
        ],
      },
    };

    const store = useAppBootstrapStore();
    expect(store.config.name).toBe('E-Office App');
    expect(store.config.timezone).toBe('Asia/Jakarta');
    expect(store.config.locale).toBe('id');
    expect(store.config.current_date).toBe('2026-07-28');
    expect(store.config.references.permission_types).toEqual([
      {
        value: 'menu',
        label: 'Menu Sidebar',
      },
    ]);
  });
});
