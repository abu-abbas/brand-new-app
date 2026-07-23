import assert from 'node:assert/strict';
import test from 'node:test';
import {
  buildParams,
  filterTreeRows,
  getPath,
  highlightText,
  normalizeError,
  searchRows,
  sortRows,
  sortTreeRows,
} from './data-table.utils.ts';

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

test('tree mempertahankan ancestor, mengurutkan per level, dan menandai frasa', () => {
  const tree = [
    {
      id: 1,
      name: 'Induk',
      children: [
        { id: 3, name: 'Zulu' },
        { id: 2, name: 'Anak cocok' },
      ],
    },
  ];
  const filtered = filterTreeRows(tree, 'cocok', [{ key: 'name', label: 'Nama' }]);
  assert.deepEqual(
    filtered.rows[0].children.map((row) => row.id),
    [2],
  );
  assert.deepEqual(filtered.expandedKeys, [1]);
  assert.deepEqual(
    sortTreeRows(tree, [{ key: 'name', direction: 'asc' }])[0].children.map((row) => row.id),
    [2, 3],
  );
  assert.deepEqual(highlightText('Anak cocok', 'cocok'), [
    { text: 'Anak ', match: false },
    { text: 'cocok', match: true },
  ]);
});

test('tree filter mendukung custom rowKey (string dan callback)', () => {
  const customTree = [
    {
      uuid: 'parent-101',
      name: 'Induk Utama',
      subordinates: [{ uuid: 'child-202', name: 'Child Match' }],
    },
  ];
  const stringKeyResult = filterTreeRows(
    customTree,
    'Match',
    [{ key: 'name', label: 'Nama' }],
    'subordinates',
    undefined,
    'uuid',
  );
  assert.deepEqual(stringKeyResult.expandedKeys, ['parent-101']);

  const fnKeyResult = filterTreeRows(
    customTree,
    'Match',
    [{ key: 'name', label: 'Nama' }],
    'subordinates',
    undefined,
    (row) => `custom-${row.uuid}`,
  );
  assert.deepEqual(fnKeyResult.expandedKeys, ['custom-parent-101']);
});

