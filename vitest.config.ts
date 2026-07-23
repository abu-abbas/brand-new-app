import path from 'node:path';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, 'resources/js'),
    },
  },
  test: {
    environment: 'jsdom',
    include: ['resources/js/**/*.test.ts'],
  },
});
