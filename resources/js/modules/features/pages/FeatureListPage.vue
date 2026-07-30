<script setup lang="ts">
import { ref } from 'vue';
import { Plus } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { DataTable } from '@/components/custom-ui/data-table';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
} from '@/components/custom-ui/data-table/data-table.types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { FeatureResource, FeaturesIndexParams } from '@/api/generated/api';
import { FeaturesFacade } from '../api/features.facade';
import FeatureFormModal from '../components/FeatureFormModal.vue';

interface FeatureRow extends Record<string, unknown>, FeatureResource {}

const formOpen = ref(false);
const selectedFeature = ref<FeatureRow | null>(null);
const selectedType = ref<'all' | NonNullable<FeaturesIndexParams['type']>>('all');

const fields: DataTableField<FeatureRow>[] = [
  {
    key: 'rownum',
    label: 'No.',
    width: 70,
    sortable: false,
    filterColumn: false,
    align: 'center',
  },
  { key: 'name', label: 'Nama', minWidth: 180 },
  { key: 'alias', label: 'Alias', minWidth: 180 },
  { key: 'type', label: 'Tipe', width: 100, align: 'center' },
  { key: 'parent', label: 'Parent', minWidth: 160 },
  { key: 'description', label: 'Deskripsi', minWidth: 240 },
  { key: 'route', label: 'Route', minWidth: 180 },
  { key: 'icon', label: 'Icon', width: 120 },
  { key: 'order', label: 'Urutan', width: 100, align: 'center' },
  { key: 'show_on_sidebar', label: 'Sidebar', width: 110, align: 'center' },
  { key: 'updated_at', label: 'Diperbarui', minWidth: 180 },
  { key: 'deleted_at', label: 'Status', width: 120, align: 'center' },
];

const filters: DataTableFilter[] = [
  { key: 'include_deleted', label: 'Sertakan data terhapus', type: 'boolean' },
  { key: 'updated_at', label: 'Tanggal diperbarui', type: 'date-range' },
];

const fetcher: DataTableFetcher<FeatureRow> = async ({ params, signal }) => {
  const activeFilters = params.filters as Record<string, unknown> | undefined;
  const updatedAt = Array.isArray(activeFilters?.updated_at) ? activeFilters.updated_at : [];
  const response = await FeaturesFacade.list(
    {
      page: params.page,
      per_page: params.per_page,
      search: params.search ? String(params.search) : undefined,
      'search_fields[]': params.search_fields as FeaturesIndexParams['search_fields[]'],
      sort_by: params.sort_by as FeaturesIndexParams['sort_by'],
      sort_direction: params.sort_direction,
      type: params.type as FeaturesIndexParams['type'],
      include_deleted: activeFilters?.include_deleted === true ? 'true' : 'false',
      updated_at_from: updatedAt[0] ? String(updatedAt[0]) : undefined,
      updated_at_to: updatedAt[1] ? String(updatedAt[1]) : undefined,
    },
    signal,
  );

  return {
    data: response.data as FeatureRow[],
    meta: {
      current_page: response.meta.current_page,
      from: response.meta.from,
      last_page: response.meta.last_page,
      per_page: response.meta.per_page,
      to: response.meta.to,
      total: response.meta.total,
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
  selectedFeature.value = null;
  formOpen.value = true;
}

function openEdit(feature: FeatureRow): void {
  selectedFeature.value = feature;
  formOpen.value = true;
}
</script>

<template>
  <AdminLayout>
    <template #header-actions>
      <Button class="ml-auto gap-2" @click="openCreate">
        <Plus data-icon="inline-start" />
        Tambah Fitur
      </Button>
    </template>
    <div class="rounded-2xl border bg-background p-4 shadow-2xs md:p-6">
      <DataTable
        query-key="features"
        title="Daftar Fitur"
        :fetcher="fetcher"
        :fields="fields"
        :filters="filters"
        :extra-params="{ type: selectedType === 'all' ? undefined : selectedType }"
        row-key="alias"
        remember="features"
        actions
        :actions-width="60"
        :can-delete="() => false"
        @edit="openEdit"
      >
        <template #toolbar>
          <div class="flex justify-end">
            <Select v-model="selectedType">
              <SelectTrigger class="w-36" aria-label="Filter tipe fitur">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Tipe fitur</SelectLabel>
                  <SelectItem value="all">Semua tipe</SelectItem>
                  <SelectItem value="menu">Menu</SelectItem>
                  <SelectItem value="crud">CRUD</SelectItem>
                  <SelectItem value="filter">Filter</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </template>

        <template #cell(type)="{ value }">
          <Badge variant="outline">{{ value }}</Badge>
        </template>
        <template #cell(show_on_sidebar)="{ value }">
          <Badge :variant="value ? 'default' : 'secondary'">{{ value ? 'Ya' : 'Tidak' }}</Badge>
        </template>
        <template #cell(updated_at)="{ value }">
          {{ formatDate(value) }}
        </template>
        <template #cell(deleted_at)="{ value }">
          <Badge :variant="value ? 'destructive' : 'default'">
            {{ value ? 'Terhapus' : 'Aktif' }}
          </Badge>
        </template>
      </DataTable>
    </div>

    <FeatureFormModal v-model:open="formOpen" :feature="selectedFeature" />
  </AdminLayout>
</template>
