import assert from 'node:assert/strict';
import test from 'node:test';
import { toKebabCase } from './feature-form.utils.ts';

test('nama fitur menjadi alias kebab-case yang stabil', () => {
  assert.equal(toKebabCase('  Manajemen Ákses & Pengguna  '), 'manajemen-akses-pengguna');
});
