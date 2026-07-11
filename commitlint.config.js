export default {
  extends: ['@commitlint/config-conventional'],
  rules: {
    'type-enum': [
      2,
      'always',
      ['feat', 'fix', 'docs', 'style', 'refactor', 'test', 'chore', 'publish'],
    ],
    'scope-case': [2, 'always', 'kebab-case'],
    'subject-case': [0],
    'subject-max-length': [2, 'always', 100],
    'body-case': [0],
    'body-max-line-length': [2, 'always', 120],
    'footer-max-line-length': [2, 'always', 120],
  },
};
