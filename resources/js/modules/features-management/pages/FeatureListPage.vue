<script setup lang="ts">
import { computed, ref } from 'vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import { DataTable } from '@/components/custom-ui/data-table';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
} from '@/components/custom-ui/data-table/data-table.types';
import { LucideIcon } from '@/components/custom-ui/lucide-icon';
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
import { FeaturesFacade } from '../api/features.facade.ts';
import FeatureFormModal from '../components/FeatureFormModal.vue';

interface FeatureRow extends Record<string, unknown>, FeatureResource {
  level?: number;
}

const formOpen = ref(false);
const selectedFeature = ref<FeatureRow | null>(null);
const selectedType = ref<'all' | NonNullable<FeaturesIndexParams['type']>>('all');

const appBootstrap = useAppBootstrapStore();
const permissionTypeOptions = computed(() => appBootstrap.config.references.permission_types);

const fields: DataTableField<FeatureRow>[] = [
  {
    key: 'rownum',
    label: 'No',
    width: 50,
    sortable: false,
    filterColumn: false,
    align: 'center',
  },
  { key: 'name', label: 'Identitas Fitur', minWidth: 260 },
  { key: 'alias', label: 'Alias', minWidth: 180 },
  { key: 'type', label: 'Tipe', align: 'center', hidden: true },
  { key: 'parent', label: 'Parent', hidden: true },
  { key: 'description', label: 'Deskripsi', hidden: true },
  { key: 'route', label: 'Route', hidden: true },
  { key: 'icon', label: 'Icon', hidden: true },
  { key: 'order', label: 'Urutan', align: 'center', hidden: true },
  { key: 'show_on_sidebar', label: 'Sidebar', width: 70, align: 'center' },
  { key: 'deleted_at', label: 'Status', width: 80, align: 'center' },
  { key: 'updated_at', label: 'Diperbarui', minWidth: 110 },
];

const filters: DataTableFilter[] = [
  { key: 'include_deleted', label: 'Sertakan data terhapus', type: 'boolean' },
  { key: 'updated_at', label: 'Tanggal diperbarui', type: 'date-range' },
];

function buildFeatureTreeList(items: FeatureRow[]): FeatureRow[] {
  const childrenMap = new Map<string | null, FeatureRow[]>();

  items.forEach((item) => {
    const parentKey = item.parent || null;
    if (!childrenMap.has(parentKey)) {
      childrenMap.set(parentKey, []);
    }
    childrenMap.get(parentKey)!.push(item);
  });

  const result: FeatureRow[] = [];

  function traverse(parentKey: string | null, level: number) {
    const children = childrenMap.get(parentKey) || [];
    children.sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
    children.forEach((child) => {
      result.push({ ...child, level });
      traverse(child.alias, level + 1);
    });
  }

  traverse(null, 0);

  items.forEach((item) => {
    if (!result.some((r) => r.alias === item.alias)) {
      result.push({ ...item, level: 0 });
    }
  });

  return result;
}

const fetcher: DataTableFetcher<FeatureRow> = async ({ params, signal }) => {
  const activeFilters = params.filters as Record<string, unknown> | undefined;
  const updatedAt = Array.isArray(activeFilters?.updated_at) ? activeFilters.updated_at : [];
  const response = await FeaturesFacade.list(
    {
      page: 1,
      per_page: 100,
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

  const treeList = buildFeatureTreeList(response.data as FeatureRow[]);

  return {
    data: treeList,
    meta: {
      current_page: 1,
      from: 1,
      last_page: 1,
      per_page: response.data.length,
      to: response.data.length,
      total: response.data.length,
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
        <LucideIcon name="Plus" data-icon="inline-start" />
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
        @create="openCreate"
      >
        <template #toolbar>
          <div class="flex justify-end">
            <Select v-model="selectedType">
              <SelectTrigger class="w-40" aria-label="Filter tipe fitur">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Tipe fitur</SelectLabel>
                  <SelectItem value="all">Semua tipe</SelectItem>
                  <SelectItem
                    v-for="option in permissionTypeOptions"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </template>

        <template #cell(name)="{ row }">
          <div
            class="flex items-center gap-2"
            :style="{ paddingLeft: `${(row.level ?? 0) * 20}px` }"
          >
            <div
              v-if="(row.level ?? 0) > 0"
              class="flex items-center justify-center text-muted-foreground/45 shrink-0"
            >
              <LucideIcon name="CornerDownRight" class="size-3.5" />
            </div>

            <div class="flex gap-2" :class="{ 'items-center': !row.description }">
              <div
                v-if="row.type === 'menu' && !!row.icon"
                class="flex size-9 items-center justify-center rounded-lg bg-muted shrink-0"
              >
                <LucideIcon
                  :name="row.icon"
                  fallback="CircleDashed"
                  class="size-4"
                  fallback-class="text-muted-foreground/65"
                />
              </div>

              <div class="flex-1">
                <div class="flex flex-col gap-0.5">
                  <span class="leading-snug font-medium text-sm">
                    {{ row.name }}
                  </span>
                  <span v-if="row.description" class="text-2sm text-muted-foreground leading-tight">
                    {{ row.description }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <template #cell(alias)="{ value, row }">
          <div class="flex flex-col">
            <span class="text-2sm">{{ value }}</span>
            <div class="flex flex-wrap items-center gap-1.5">
              <div class="flex items-center text-xs gap-1.5">
                <span
                  class="size-1.5 rounded-full"
                  :class="{
                    'bg-blue-500': row.type === 'menu',
                    'bg-emerald-500': row.type === 'crud',
                    'bg-amber-500': row.type === 'filter',
                  }"
                />
                {{ row.type_label }}
              </div>

              <div
                v-if="!!row.route"
                class="flex-1 flex items-center gap-1 text-xs text-muted-foreground leading-tight"
              >
                <LucideIcon
                  name="Link2"
                  fallback="CircleDashed"
                  class="size-3"
                  fallback-class="text-muted-foreground/65"
                />

                <span class="font-mono text-2xs">{{ row.route }}</span>
              </div>
            </div>
          </div>
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
