import { defineConfig } from 'orval';

export default defineConfig({
  api: {
    input: './docs/openapi.json',
    output: {
      mode: 'single',
      target: './resources/js/api/generated/api.ts',
      client: 'axios-functions',
      clean: true,
      override: {
        mutator: {
          path: './resources/js/lib/axios.ts',
          name: 'customAxiosInstance',
        },
      },
    },
    hooks: {},
  },
});
