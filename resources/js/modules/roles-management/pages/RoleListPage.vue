<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { DataTable } from '@/components/custom-ui/data-table';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
} from '@/components/custom-ui/data-table/data-table.types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { LucideIcon } from '@/components/custom-ui/lucide-icon';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import RoleFormModal from '../components/RoleFormModal.vue';
import { RolesFacade, type RoleRow } from '../api/roles.facade';

const confirmDialog = useConfirmDialog();
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
  { key: 'code', label: 'Kode Group', minWidth: 160, sortable: true },
  { key: 'name', label: 'Nama Group', minWidth: 200, sortable: true },
  { key: 'region', label: 'Wilayah', minWidth: 130, align: 'center' },
  { key: 'regional_device', label: 'Perangkat Daerah', minWidth: 160, align: 'center' },
  { key: 'user_count', label: 'Jumlah User', width: 110, align: 'center' },
  { key: 'permissions', label: 'Akses', minWidth: 120, align: 'center' },
  { key: 'updated_at', label: 'Diperbarui', minWidth: 150 },
];

const filters: DataTableFilter[] = [
  { key: 'updated_at', label: 'Tanggal diperbarui', type: 'date-range' },
];

const fetcher: DataTableFetcher<RoleRow> = async () => {
  const result = await RolesFacade.list();
  return {
    data: result.data,
    meta: {
      current_page: 1,
      from: 1,
      last_page: 1,
      per_page: result.total,
      to: result.total,
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

function handleDelete(role: RoleRow): void {
  confirmDialog({
    title: 'Hapus Group',
    description: `Apakah Anda yakin ingin menghapus group "${role.name}" (${role.code})?`,
    confirmLabel: 'Ya, Hapus',
    confirmVariant: 'destructive',
    onConfirm: async () => {
      await RolesFacade.delete(role.code);
      dataTableRef.value?.reload();
    },
  });
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
        row-key="code"
        remember="roles"
        search-placeholder="Cari berdasarkan nama atau kode group..."
        action-column-title="Aksi"
        action-column-width="110"
      >
        <template #cell(code)="{ value }">
          <span class="font-mono text-xs font-semibold">{{ value }}</span>
        </template>

        <template #cell(region)="{ value }">
          <Badge v-if="value" variant="outline" class="font-normal">
            {{ value }}
          </Badge>
          <span v-else class="text-xs text-muted-foreground">-</span>
        </template>

        <template #cell(regional_device)="{ value }">
          <Badge v-if="value" variant="secondary" class="font-normal">
            {{ value }}
          </Badge>
          <span v-else class="text-xs text-muted-foreground">-</span>
        </template>

        <template #cell(user_count)="{ value }">
          <span class="font-medium text-xs">{{ value }} pengguna</span>
        </template>

        <template #cell(permissions)="{ value }">
          <Badge variant="default" class="bg-primary/90">
            {{ Array.isArray(value) ? value.length : 0 }} Fitur
          </Badge>
        </template>

        <template #cell(updated_at)="{ value }">
          {{ formatDate(value) }}
        </template>

        <template #actions="{ row }">
          <div class="flex items-center justify-center gap-1">
            <Button
              variant="ghost"
              size="icon-xs"
              title="Edit Group"
              @click="openEdit(row as RoleRow)"
            >
              <LucideIcon
                name="Pencil"
                class="size-3.5 text-muted-foreground hover:text-foreground"
              />
            </Button>
            <Button
              variant="ghost"
              size="icon-xs"
              title="Hapus Group"
              @click="handleDelete(row as RoleRow)"
            >
              <LucideIcon name="Trash2" class="size-3.5 text-destructive" />
            </Button>
          </div>
        </template>
      </DataTable>

      <!-- Modal Form Tambah / Edit Group -->
      <RoleFormModal v-model:open="modalOpen" :role="selectedRole" @submitted="onSubmitted" />
    </div>
  </AdminLayout>
</template>
