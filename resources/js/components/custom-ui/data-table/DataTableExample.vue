<script setup lang="ts">
import { ref } from 'vue';
import { User } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DataTable from './DataTable.vue';
import type { DataTableFetcher, DataTableField, DataTableParams } from './data-table.types';
import { searchRows, sortRows } from './data-table.utils';

interface UserRow extends Record<string, unknown> {
  id: number;
  name: string;
  username: string;
  email: string;
  unit: { name: string };
  roles: string[];
  active: boolean;
}

const users: UserRow[] = Array.from({ length: 36 }, (_, index) => ({
  id: index + 1,
  name: ['Afria', 'Budi Santoso', 'Citra Lestari', 'Dimas Putra'][index % 4],
  username: `user${index + 1}`,
  email: `user${index + 1}@example.test`,
  unit: { name: ['Keuangan', 'Teknologi', 'SDM'][index % 3] },
  roles: [index % 3 === 0 ? 'Admin' : 'Staff'],
  active: index % 4 !== 0,
}));

const fields: DataTableField<UserRow>[] = [
  { key: 'rownum', label: 'No.', width: 70, sortable: false, align: 'center' },
  { key: 'name', label: 'Nama', minWidth: 180 },
  { key: 'username', label: 'Username', minWidth: 140 },
  { key: 'unit.name', label: 'Unit Kerja', minWidth: 160 },
  { key: 'roles', label: 'Peran', minWidth: 120 },
  { key: 'email', label: 'Email', hidden: true },
  { key: 'active', label: 'Status', width: 110, align: 'center' },
];
const filters = [
  {
    key: 'active',
    label: 'Status',
    type: 'boolean' as const,
  },
];
const selected = ref<UserRow[]>([]);

const serverFetcher: DataTableFetcher<UserRow> = async ({ params, signal }) => {
  await new Promise<void>((resolve, reject) => {
    const timeout = globalThis.setTimeout(resolve, 1200);
    signal.addEventListener('abort', () => {
      globalThis.clearTimeout(timeout);
      reject(new Error('Request dibatalkan'));
    });
  });
  const filtered = searchRows(
    users,
    String(params.search ?? ''),
    fields,
    params.search_fields as string[],
  );
  const sorted = sortRows(
    filtered,
    params.sort ??
      (params.sort_by ? [{ key: params.sort_by, direction: params.sort_direction! }] : []),
  );
  const page = Number(params.page ?? 1);
  const perPage = Number(params.per_page ?? 10);
  const pageRows = sorted.slice((page - 1) * perPage, page * perPage);
  return {
    data: pageRows,
    meta: {
      current_page: page,
      from: pageRows.length ? (page - 1) * perPage + 1 : null,
      last_page: Math.max(1, Math.ceil(sorted.length / perPage)),
      links: [],
      path: '/mock/users',
      per_page: perPage,
      to: pageRows.length ? (page - 1) * perPage + pageRows.length : null,
      total: sorted.length,
    },
    message: 'Data pengguna berhasil dimuat.',
  };
};

function serverParams(params: DataTableParams): void {
  void params;
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>DataTable reusable</CardTitle>
      <CardDescription>
        Mode lokal dan mock API Facade/server dari kontrak yang sama.
      </CardDescription>
    </CardHeader>
    <CardContent class="flex flex-col gap-8">
      <DataTable
        v-model:selected="selected"
        title="Pengguna lokal"
        :items="users"
        :fields="fields"
        :filters="filters"
        selection="multiple"
        actions
        row-key="id"
      >
        <template #header(name)="{ column }">
          <span class="inline-flex items-center gap-1 font-bold text-primary">
            <User class="size-4" />
            {{ (column as { label?: string }).label }}
          </span>
        </template>
        <template #header(active)="{ column }">
          <span class="text-xs tracking-wider uppercase text-muted-foreground">
            {{ (column as { label?: string }).label }}
          </span>
        </template>
        <template #cell(active)="{ value }">
          <Badge :variant="value ? 'default' : 'secondary'">
            {{ value ? 'Aktif' : 'Nonaktif' }}
          </Badge>
        </template>
      </DataTable>

      <DataTable
        title="Pengguna server"
        query-key="users-demo"
        :fetcher="serverFetcher"
        :fields="fields"
        :filters="filters"
        remember="users-demo"
        row-key="id"
        @params-change="serverParams"
      >
        <template #cell(active)="{ value }">
          <Badge :variant="value ? 'default' : 'secondary'">
            {{ value ? 'Aktif' : 'Nonaktif' }}
          </Badge>
        </template>
      </DataTable>
    </CardContent>
  </Card>
</template>
