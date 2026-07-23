import assert from 'node:assert/strict';
import test from 'node:test';
import { buildParams, getPath, normalizeError, searchRows, sortRows } from './data-table.utils.ts';

const rows = [
  { id: 1, name: 'Budi', unit: { name: 'Keuangan' }, roles: [{ name: 'Admin' }] },
  { id: 2, name: 'Ani', unit: { name: 'Teknologi' }, roles: [{ name: 'Staff' }] },
];
const fields = [
  { key: 'name', label: 'Nama' },
  { key: 'unit.name', label: 'Unit' },
  { key: 'roles', label: 'Peran' },
];

test('membaca nested path dan mencari nested object/array dengan satu frasa', () => {
  assert.equal(getPath(rows[0], 'unit.name'), 'Keuangan');
  assert.deepEqual(searchRows(rows, 'staff', fields), [rows[1]]);
  assert.deepEqual(searchRows(rows, 'keu ang', fields), []);
});

test('sorting stabil tanpa mengubah input', () => {
  const sorted = sortRows(rows, [{ key: 'name', direction: 'asc' }]);
  assert.deepEqual(
    sorted.map((row) => row.name),
    ['Ani', 'Budi'],
  );
  assert.equal(rows[0].name, 'Budi');
});

test('params melindungi state internal dan memilih kontrak multi-sort', () => {
  const params = buildParams(
    {
      page: 2,
      perPage: 10,
      paginated: true,
      search: 'ani',
      searchFields: ['name'],
      sorts: [
        { key: 'name', direction: 'asc' },
        { key: 'id', direction: 'desc' },
      ],
      filters: { active: true },
    },
    { page: 99, tenant: 7 },
  );
  assert.equal(params.page, 2);
  assert.equal(params.tenant, 7);
  assert.equal(params.sort_by, undefined);
  assert.equal(params.sort?.length, 2);
});

test('normalisasi error memakai contract publik', () => {
  assert.deepEqual(
    normalizeError({
      response: {
        status: 422,
        data: {
          message: 'Validasi gagal.',
          code: 'USR-VAL-001',
          retryable: false,
          errors: { name: [] },
        },
        headers: { 'x-request-id': 'request-1' },
      },
    }),
    {
      message: 'Validasi gagal.',
      code: 'USR-VAL-001',
      retryable: false,
      validationErrors: { name: [] },
      requestId: 'request-1',
    },
  );
});
