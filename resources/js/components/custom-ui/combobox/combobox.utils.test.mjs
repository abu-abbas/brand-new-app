import assert from 'node:assert/strict';
import test from 'node:test';
import { defaultFilterOption, getOptionLabel, mergeOptions } from './combobox.utils.ts';

test('combobox memetakan label, memfilter, dan menjaga opsi terpilih tetap tersedia', () => {
  const selected = { id: 2, name: 'Budi' };
  const options = [
    { id: 1, name: 'Ani' },
    { id: 2, name: 'Budi terbaru' },
  ];
  const merged = mergeOptions([[selected], options], 'id');

  assert.equal(merged.length, 2);
  assert.equal(
    getOptionLabel(
      merged.find((option) => option.id === 2),
      'name',
    ),
    'Budi terbaru',
  );
  assert.equal(
    defaultFilterOption(
      merged.find((option) => option.id === 1),
      'AN',
      'name',
    ),
    true,
  );
});
