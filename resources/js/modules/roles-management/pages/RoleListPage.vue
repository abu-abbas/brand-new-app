<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { BadgeList } from '@/components/custom-ui/badge-list';
import { DataTable } from '@/components/custom-ui/data-table';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
  DataTableParams,
} from '@/components/custom-ui/data-table/data-table.types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { LucideIcon } from '@/components/custom-ui/lucide-icon';
import { CircleCheck, CircleX } from '@lucide/vue';
import RoleFormModal from '../components/RoleFormModal.vue';
import { RolesFacade, type RoleRow } from '../api/roles.facade';

const dataTableRef = ref();

const modalOpen = ref(false);
const selectedRole = ref<RoleRow | null>(null);

const fields: DataTableField<RoleRow>[] = [
  {
    key: 'rownum',
    label: 'No',
    width: 50,
    sortable: false,
    filterColumn: false,
    align: 'center',
  },
  { key: 'name', label: 'Group', minWidth: 200, sortable: true },
  { key: 'code', label: 'Kode Group', hidden: true },
  { key: 'features', label: 'Hak Akses', minWidth: 150 },
  {
    key: 'limitasi',
    label: 'Limitasi',
    headerAlign: 'center',
    sortable: false,
    filterColumn: false,
    children: [
      {
        key: 'need_region',
        label: 'Wilayah',
        minWidth: 40,
        align: 'center',
        sortable: false,
        filterColumn: false,
      },
      {
        key: 'need_unit',
        label: 'OPD',
        minWidth: 40,
        align: 'center',
        sortable: false,
        filterColumn: false,
      },
      {
        key: 'active_periode',
        label: 'Periode',
        minWidth: 40,
        align: 'center',
        sortable: false,
        filterColumn: false,
      },
    ],
  },
  {
    key: 'deleted_at',
    label: 'Status',
    minWidth: 40,
    align: 'center',
    sortable: true,
    filterColumn: true,
  },
  { key: 'updated_at', label: 'Diperbarui', minWidth: 80, sortable: true },
];

const filters: DataTableFilter[] = [
  { key: 'include_deleted', label: 'Sertakan data terhapus', type: 'boolean' },
  { key: 'updated_at', label: 'Tanggal diperbarui', type: 'date-range' },
];

const fetcher: DataTableFetcher<RoleRow> = async ({ params }: { params: DataTableParams }) => {
  const activeFilters = params.filters as Record<string, unknown> | undefined;

  let updated_at_from: string | undefined;
  let updated_at_to: string | undefined;

  const rawDateRange = activeFilters?.updated_at as { start?: string; end?: string } | undefined;
  if (rawDateRange) {
    updated_at_from = rawDateRange.start;
    updated_at_to = rawDateRange.end;
  }

  const result = await RolesFacade.list({
    page: params.page,
    per_page: params.per_page,
    search: params.search,
    sort_by: params.sort_by,
    sort_direction: params.sort_direction,
    include_deleted: activeFilters?.include_deleted === true,
    updated_at_from,
    updated_at_to,
  });

  return {
    data: result.data,
    meta: {
      current_page: result.current_page,
      from: (result.current_page - 1) * result.per_page + 1,
      last_page: result.last_page,
      per_page: result.per_page,
      to: Math.min(result.current_page * result.per_page, result.total),
      total: result.total,
    },
  };
};

function formatDate(value: unknown): string {
  return value
    ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(
        new Date(String(value)),
      )
    : '-';
}

function openCreate(): void {
  selectedRole.value = null;
  modalOpen.value = true;
}

function openEdit(role: RoleRow): void {
  selectedRole.value = role;
  modalOpen.value = true;
}

function onSubmitted(): void {
  dataTableRef.value?.reload();
}
</script>

<template>
  <AdminLayout parent-title="Manajemen Pengguna & Hak Akses" title="Manajemen Group">
    <template #header-actions>
      <Button class="ml-auto gap-2" @click="openCreate">
        <LucideIcon name="Plus" data-icon="inline-start" />
        Tambah Group
      </Button>
    </template>

    <div class="rounded-2xl border bg-background p-4 shadow-2xs md:p-6">
      <DataTable
        ref="dataTableRef"
        query-key="roles"
        title="Daftar Group"
        :fetcher="fetcher as unknown as DataTableFetcher<Record<string, unknown>>"
        :fields="fields as unknown as DataTableField<Record<string, unknown>>[]"
        :filters="filters"
        row-key="id"
        remember="roles"
        search-placeholder="Cari berdasarkan nama atau kode group..."
        actions
        :actions-width="60"
        :can-delete="() => false"
        @edit="(row: unknown) => openEdit(row as RoleRow)"
        @create="openCreate"
      >
        <template #cell(name)="{ row, value }">
          <div class="flex flex-col gap-0">
            <p>{{ value }}</p>
            <p class="text-sm text-muted-foreground">{{ row.code }}</p>
          </div>
        </template>

        <template #cell(need_region)="{ value }">
          <CircleCheck v-if="value" class="mx-auto size-4 text-emerald-500" />
          <CircleX v-else class="mx-auto size-4 text-muted-foreground/40" />
        </template>

        <template #cell(need_unit)="{ value }">
          <CircleCheck v-if="value" class="mx-auto size-4 text-emerald-500" />
          <CircleX v-else class="mx-auto size-4 text-muted-foreground/40" />
        </template>

        <template #cell(active_periode)="{ value }">
          <CircleCheck
            v-if="value && (value.start || value.end)"
            class="mx-auto size-4 text-emerald-500"
          />
          <CircleX v-else class="mx-auto size-4 text-muted-foreground/40" />
        </template>

        <template #cell(features)="{ value, search }">
          <BadgeList :items="value" :search="search" :max="10" />
        </template>

        <template #cell(deleted_at)="{ value }">
          <Badge
            v-if="!value"
            variant="outline"
            class="font-normal border-emerald-500/30 text-emerald-600 dark:text-emerald-400"
          >
            Aktif
          </Badge>
          <Badge
            v-else
            variant="outline"
            class="font-normal border-destructive/30 text-destructive"
          >
            Tidak Aktif
          </Badge>
        </template>

        <template #cell(updated_at)="{ value }">
          {{ formatDate(value) }}
        </template>
      </DataTable>

      <!-- Modal Form Tambah / Edit Group -->
      <RoleFormModal v-model:open="modalOpen" :role="selectedRole" @submitted="onSubmitted" />
    </div>
  </AdminLayout>
</template>
