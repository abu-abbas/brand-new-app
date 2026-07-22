import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginVue from 'eslint-plugin-vue';
import eslintConfigPrettier from '@vue/eslint-config-prettier';

export default tseslint.config(
  eslint.configs.recommended,
  tseslint.configs.recommended,
  pluginVue.configs['flat/recommended'],
  eslintConfigPrettier,
  {
    ignores: [
      'node_modules/**',
      'vendor/**',
      'public/**',
      'storage/**',
      'bootstrap/**',
      'config/**',
      'database/**',
      'tests/**',
      'dist/**',
      '.vscode/**',
      '**/*.php',
    ],
  },
  {
    files: ['*.vue', '**/*.vue'],
    languageOptions: {
      parser: pluginVue.parser,
      parserOptions: {
        parser: tseslint.parser,
        sourceType: 'module',
      },
    },
  },
  {
    languageOptions: {
      globals: {
        process: 'readonly',
        window: 'readonly',
        document: 'readonly',
        HTMLElement: 'readonly',
        MouseEvent: 'readonly',
        Node: 'readonly',
      },
    },
  },
  {
    files: ['resources/js/components/ui/**/*.vue'],
    rules: {
      'vue/require-default-prop': 'off',
    },
  },
  {
    rules: {
      'vue/multi-word-component-names': 'off', // Dimatikan agar nama component seperti App.vue atau Button.vue diperbolehkan
      '@typescript-eslint/no-explicit-any': 'warn',
      'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn', // Error saat build production, warn saat development
    },
  },
);
