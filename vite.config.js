import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.ts'],
      refresh: true,
      fonts: [
        bunny('Figtree', {
          weights: [400, 500, 600, 700],
        }),
        bunny('JetBrains Mono', {
          weights: [400],
        }),
      ],
    }),
    tailwindcss(),
    vue(),
  ],
  build: {
    chunkSizeWarningLimit: 1000,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (id.includes('element-plus')) {
              return 'vendor-element-plus';
            }
            if (id.includes('@lucide/vue')) {
              return 'vendor-lucide';
            }
            if (id.includes('reka-ui')) {
              return 'vendor-reka';
            }
            if (id.includes('@tanstack/vue-query')) {
              return 'vendor-tanstack-query';
            }
            if (
              id.includes('vue') ||
              id.includes('vue-router') ||
              id.includes('pinia') ||
              id.includes('axios')
            ) {
              return 'vendor-core';
            }
          }
        },
      },
    },
  },
  server: {
    hmr: {
      overlay: false,
      reloadForStaticChanges: false,
    },
    watch: {
      ignored: [
        '**/app/**',
        '**/lang/**',
        '**/tests/**',
        '**/storage/**',
        '**/database/**',
        '**/bootstrap/**',

        // Kecuali routes yang tetap di-watch
        '!**/routes/**',
      ],
    },
  },
});
