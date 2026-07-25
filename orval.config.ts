import { defineConfig } from 'orval';

export default defineConfig({
  api: {
    input: './docs/openapi.json',
    output: {
      mode: 'single',
      target: './resources/js/api/generated/users.ts',
      schemas: './resources/js/api/generated/models',
      client: 'axios-functions',
      clean: true,
      override: {
        mutator: {
          path: './resources/js/lib/axios.ts',
          name: 'customAxiosInstance',
        },
      },
    },
  },
});
