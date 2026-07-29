<script setup lang="ts">
import { ref } from 'vue';
import {
  ChevronsUpDown,
  Network,
  Server,
  User,
  AlertCircle,
  RefreshCw,
  WifiOff,
  ShieldAlert,
  Sparkles,
  BookOpen,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { MarkdownText } from '@/components/custom-ui/markdown-text';
import DataTable from './DataTable.vue';
import DataTableDocViewer from './DataTableDocViewer.vue';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableInstance,
  DataTableParams,
} from './data-table.types';
import { UserManagementFacade } from '@/modules/user-management/api/user-management.facade';
import type { UsersIndexParams } from '@/api/generated/api';
import { axiosInstance } from '@/lib/axios';

import supportIdBlade from '../../../../views/support-id.blade.php?raw';
import dataTableReadme from './README.md?raw';

const activeTab = ref<'showcase' | 'documentation'>('showcase');

// ==========================================
// 1. DATA & TYPES: USER MANAGEMENT (LOCAL & SERVER)
// ==========================================
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

const userFields: DataTableField<UserRow>[] = [
  { key: 'rownum', label: 'No.', width: 70, sortable: false, align: 'center' },
  { key: 'name', label: 'Nama', minWidth: 180 },
  { key: 'username', label: 'Username', minWidth: 140 },
  { key: 'unit.name', label: 'Unit Kerja', minWidth: 160 },
  { key: 'roles', label: 'Peran', minWidth: 120 },
  { key: 'email', label: 'Email', hidden: true },
  { key: 'active', label: 'Status', width: 110, align: 'center' },
];

const userFilters = [
  {
    key: 'active',
    label: 'Status',
    type: 'boolean' as const,
  },
];

const serverFilters = [
  {
    key: 'active',
    label: 'Status',
    type: 'custom' as const,
  },
];

const selectedUsers = ref<UserRow[]>([]);
const serverExtraParams = ref<Record<string, unknown>>({});

/**
 * Server fetcher terintegrasi penuh ke Laravel 13 API (/api/users)
 * Menggunakan UserManagementFacade & Orval generated client.
 * Jika parameter extra simulate_error diaktifkan, API akan memicu Exception EDF backend atau simulasi firewall block.
 */
const serverFetcher: DataTableFetcher<UserRow> = async ({ params, signal }) => {
  if (params.simulate_error === '409') {
    await UserManagementFacade.triggerTestError(signal);
  }

  if (params.simulate_error === '422') {
    await UserManagementFacade.getUsers({ per_page: 999 }, signal);
  }

  if (params.simulate_error === 'network') {
    // Error jaringan murni (tanpa response) — normalizeAppError() menandainya
    // retryable secara default, jadi DataTableErrorAlert menampilkan tombol "Coba lagi".
    throw new Error('Network Error');
  }

  if (params.simulate_error === 'firewall') {
    // Membaca langsung isi file support-id.blade.php via Vite ?raw import
    const mockFirewallHtml = supportIdBlade;
    const mockResponse = {
      data: mockFirewallHtml,
      status: 200,
      statusText: 'OK',
      headers: {},
      config: { headers: {} } as unknown,
    };
    // Jalankan melalui interceptor response axiosInstance agar terdeteksi secara otomatis
    const handler = (
      axiosInstance.interceptors.response as unknown as {
        handlers: Array<{ fulfilled: (res: unknown) => unknown }>;
      }
    ).handlers?.[0];
    if (handler) {
      await handler.fulfilled(mockResponse);
    }
  }

  const activeFilter =
    params.filters?.active !== undefined
      ? (String(params.filters.active) as 'true' | 'false')
      : undefined;

  const response = await UserManagementFacade.getUsers(
    {
      page: params.page,
      per_page: params.per_page,
      search: params.search ? String(params.search) : undefined,
      sort_by: params.sort_by ? (String(params.sort_by) as UsersIndexParams['sort_by']) : undefined,
      sort_direction: params.sort_direction as 'asc' | 'desc' | undefined,
      active: activeFilter,
    },
    signal,
  );

  return {
    data: response.data as unknown as UserRow[],
    meta: {
      current_page: response.meta.current_page,
      from: response.meta.from,
      last_page: response.meta.last_page,
      per_page: response.meta.per_page,
      to: response.meta.to,
      total: response.meta.total,
    },
    message: 'Data pengguna berhasil dimuat dari server.',
  };
};

function serverParams(params: DataTableParams): void {
  void params;
}

function setSimulateError(mode?: '409' | '422' | 'network' | 'firewall') {
  if (!mode) {
    serverExtraParams.value = {};
  } else {
    serverExtraParams.value = { simulate_error: mode };
  }
}

// ==========================================
// 2. DATA & TYPES: TAXONOMY ANIMAL TREE
// ==========================================
const treeTable = ref<DataTableInstance>();
const isTreeExpanded = ref(true);

function toggleTreeExpansion() {
  if (isTreeExpanded.value) {
    treeTable.value?.collapseAll();
    isTreeExpanded.value = false;
  } else {
    treeTable.value?.expandAll();
    isTreeExpanded.value = true;
  }
}
interface AnimalTaxonomyRow extends Record<string, unknown> {
  id: string;
  name: string;
  latinName: string;
  rank: 'Kingdom' | 'Phylum' | 'Class' | 'Order' | 'Family' | 'Genus' | 'Species';
  status?:
    | 'Critically Endangered'
    | 'Endangered'
    | 'Vulnerable'
    | 'Near Threatened'
    | 'Least Concern'
    | 'Domesticated';
  children?: AnimalTaxonomyRow[];
}

const taxonomyTreeData: AnimalTaxonomyRow[] = [
  {
    id: 'kingdom-animalia',
    name: 'Animalia (Hewan)',
    latinName: 'Animalia',
    rank: 'Kingdom',
    children: [
      {
        id: 'phylum-chordata',
        name: 'Chordata (Vertebrata & Kerabat)',
        latinName: 'Chordata',
        rank: 'Phylum',
        children: [
          {
            id: 'class-mammalia',
            name: 'Mammalia (Mamalia / Menyusui)',
            latinName: 'Mammalia',
            rank: 'Class',
            children: [
              {
                id: 'order-carnivora',
                name: 'Carnivora (Pemakan Daging)',
                latinName: 'Carnivora',
                rank: 'Order',
                children: [
                  {
                    id: 'family-felidae',
                    name: 'Felidae (Keluarga Kucing)',
                    latinName: 'Felidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-panthera-tigris-sumatrae',
                        name: 'Harimau Sumatra',
                        latinName: 'Panthera tigris sumatrae',
                        rank: 'Species',
                        status: 'Critically Endangered',
                      },
                      {
                        id: 'species-panthera-leo',
                        name: 'Singa Afrika',
                        latinName: 'Panthera leo',
                        rank: 'Species',
                        status: 'Vulnerable',
                      },
                      {
                        id: 'species-felis-catus',
                        name: 'Kucing Domestik',
                        latinName: 'Felis catus',
                        rank: 'Species',
                        status: 'Domesticated',
                      },
                    ],
                  },
                ],
              },
            ],
          },
          {
            id: 'class-aves',
            name: 'Aves (Burung)',
            latinName: 'Aves',
            rank: 'Class',
            children: [
              {
                id: 'order-bucerotiformes',
                name: 'Bucerotiformes (Rangkong)',
                latinName: 'Bucerotiformes',
                rank: 'Order',
                children: [
                  {
                    id: 'family-bucerotidae',
                    name: 'Burung Enggang',
                    latinName: 'Bucerotidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-rhinoceros-vigil',
                        name: 'Rangkong Gading',
                        latinName: 'Rhinoplax vigil',
                        rank: 'Species',
                        status: 'Critically Endangered',
                      },
                    ],
                  },
                ],
              },
            ],
          },
        ],
      },
      {
        id: 'phylum-arthropoda',
        name: 'Arthropoda (Hewan Berbuku-buku)',
        latinName: 'Arthropoda',
        rank: 'Phylum',
        children: [
          {
            id: 'class-insecta',
            name: 'Insecta (Serangga)',
            latinName: 'Insecta',
            rank: 'Class',
            children: [
              {
                id: 'order-lepidoptera',
                name: 'Lepidoptera (Kupu-kupu & Ngengat)',
                latinName: 'Lepidoptera',
                rank: 'Order',
                children: [
                  {
                    id: 'species-troides-helena',
                    name: 'Kupu-kupu Raja (Troides helena)',
                    latinName: 'Troides helena',
                    rank: 'Species',
                    status: 'Least Concern',
                  },
                ],
              },
            ],
          },
        ],
      },
    ],
  },
];

const taxonomyFields: DataTableField<AnimalTaxonomyRow>[] = [
  { key: 'name', label: 'Nama / Takson', minWidth: 260 },
  { key: 'latinName', label: 'Nama Latin', minWidth: 200 },
  { key: 'rank', label: 'Tingkat Takson', width: 140, align: 'center' },
  { key: 'status', label: 'Status Konservasi', minWidth: 180, align: 'center' },
];

function getStatusBadgeVariant(
  status?: string,
): 'destructive' | 'default' | 'secondary' | 'outline' {
  switch (status) {
    case 'Critically Endangered':
    case 'Endangered':
      return 'destructive';
    case 'Vulnerable':
    case 'Near Threatened':
      return 'default';
    case 'Domesticated':
      return 'outline';
    case 'Least Concern':
      return 'secondary';
    default:
      return 'secondary';
  }
}

function getRankBadgeVariant(rank: string): 'default' | 'secondary' | 'outline' {
  switch (rank) {
    case 'Kingdom':
    case 'Phylum':
      return 'default';
    case 'Class':
    case 'Order':
      return 'secondary';
    default:
      return 'outline';
  }
}
</script>

<template>
  <Tabs v-model="activeTab" class="w-full space-y-6">
    <div class="flex items-center justify-between border-b border-border pb-4">
      <TabsList class="h-10 p-1">
        <TabsTrigger value="showcase" class="gap-2 px-4 py-1.5 text-sm font-medium">
          <Sparkles class="size-4" />
          Showcase & Demo
        </TabsTrigger>
        <TabsTrigger value="documentation" class="gap-2 px-4 py-1.5 text-sm font-medium">
          <BookOpen class="size-4" />
          Dokumentasi & Spesifikasi
        </TabsTrigger>
      </TabsList>
    </div>

    <!-- TAB 1: SHOWCASE -->
    <TabsContent value="showcase" class="space-y-8 mt-0 focus-visible:outline-none">
      <!-- Card 1: Mode Lokal -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <User class="size-5 text-primary" />
            1. DataTable Mode Lokal (User Management)
          </CardTitle>
          <CardDescription>
            <MarkdownText
              content="Data diproses penuh di sisi browser (*search*, *filter*, *sort*, dan *pagination* lokal dari fixture array)."
            />
          </CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            v-model:selected="selectedUsers"
            title="Pengguna Lokal"
            :items="users"
            :fields="userFields"
            :filters="userFilters"
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
              <span class="text-xs uppercase tracking-wider text-muted-foreground">
                {{ (column as { label?: string }).label }}
              </span>
            </template>
            <template #cell(active)="{ value }">
              <Badge :variant="value ? 'default' : 'secondary'">
                {{ value ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </template>
          </DataTable>
        </CardContent>
      </Card>

      <!-- Card 2: Mode Server Real-Time + EDF Integration -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Server class="size-5 text-primary" />
            2. DataTable Mode Server (Real Laravel 13 API + EDF & TanStack Query)
          </CardTitle>
          <CardDescription>
            <MarkdownText
              content="Data diambil secara *real-time* dari server Laravel 13 (`/api/users`) via **Orval client**, **TanStack Query**, dan **EDF (Error Definition Framework)** langsung dari dalam komponen DataTable."
            />
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <!-- Panel Kontrol Pengujian EDF di luar DataTable -->
          <div class="flex flex-col gap-2.5 p-3.5 rounded-lg bg-muted/40 border border-border">
            <span class="text-xs font-semibold text-foreground flex items-center gap-1.5 mr-1">
              <AlertCircle class="size-4 text-amber-500" />
              Mode API Server:
            </span>
            <div class="flex-1 gap-2.5 flex flex-wrap">
              <Button
                :variant="!serverExtraParams.simulate_error ? 'default' : 'outline'"
                size="sm"
                class="gap-1.5 text-xs"
                @click="setSimulateError()"
              >
                <RefreshCw class="size-3.5" />
                Normal Data API
              </Button>
              <Button
                :variant="serverExtraParams.simulate_error === '409' ? 'destructive' : 'outline'"
                size="sm"
                class="gap-1.5 text-xs"
                @click="setSimulateError('409')"
              >
                <AlertCircle class="size-3.5" />
                Uji EDF 409 (App Error)
              </Button>
              <Button
                :variant="serverExtraParams.simulate_error === '422' ? 'destructive' : 'outline'"
                size="sm"
                class="gap-1.5 text-xs"
                @click="setSimulateError('422')"
              >
                <AlertCircle class="size-3.5" />
                Uji EDF 422 (Validation Error)
              </Button>
              <Button
                :variant="
                  serverExtraParams.simulate_error === 'network' ? 'destructive' : 'outline'
                "
                size="sm"
                class="gap-1.5 text-xs"
                @click="setSimulateError('network')"
              >
                <WifiOff class="size-3.5" />
                Uji Error Jaringan (Retryable)
              </Button>
              <Button
                :variant="
                  serverExtraParams.simulate_error === 'firewall' ? 'destructive' : 'outline'
                "
                size="sm"
                class="gap-1.5 text-xs"
                @click="setSimulateError('firewall')"
              >
                <ShieldAlert class="size-3.5" />
                Uji Firewall Block (200 HTML)
              </Button>
            </div>
          </div>

          <!-- Component DataTable Server dengan error handling internal -->
          <DataTable
            title="Daftar Pengguna Server"
            query-key="users-server"
            :fetcher="serverFetcher"
            :fields="userFields"
            :filters="serverFilters"
            :extra-params="serverExtraParams"
            remember="users-server-demo"
            row-key="id"
            @params-change="serverParams"
          >
            <template #filter(active)="{ value, setValue, disabled }">
              <Select
                :model-value="value == null ? '__all__' : String(value)"
                :disabled="Boolean(disabled)"
                @update:model-value="
                  (val) =>
                    (setValue as (v: unknown) => void)(
                      val === '__all__' ? undefined : val === 'true',
                    )
                "
              >
                <SelectTrigger class="w-full">
                  <SelectValue placeholder="Pilih status (Custom Dropdown)..." />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="__all__">Semua Status</SelectItem>
                    <SelectItem value="true">User Aktif</SelectItem>
                    <SelectItem value="false">User Nonaktif</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </template>
            <template #cell(active)="{ value }">
              <Badge :variant="value ? 'default' : 'secondary'">
                {{ value ? 'Aktif' : 'Nonaktif' }}
              </Badge>
            </template>
          </DataTable>
        </CardContent>
      </Card>

      <!-- Card 3: Mode Tree (Taksonomi Hewan) -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Network class="size-5 text-primary" />
            3. DataTable Mode Tree (Taksonomi Hewan)
          </CardTitle>
          <CardDescription>
            <MarkdownText
              content="Menampilkan struktur hierarki data taksonomi biologis (*Kingdom* &rarr; *Phylum* &rarr; *Class* &rarr; *Order* &rarr; *Family* &rarr; *Species*). Mendukung *expand/collapse*, pencarian hierarkis (mempertahankan *ancestor*), dan *highlighting*."
            />
          </CardDescription>
        </CardHeader>
        <CardContent>
          <DataTable
            ref="treeTable"
            title="Taksonomi Biologis"
            :items="taxonomyTreeData"
            :fields="taxonomyFields"
            :tree="{ children: 'children', defaultExpandAll: true }"
            :paginated="false"
            row-key="id"
            striped
          >
            <template #toolbar>
              <Button
                variant="outline"
                size="sm"
                class="gap-1.5 font-medium"
                @click="toggleTreeExpansion"
              >
                <ChevronsUpDown class="size-4" />
                {{ isTreeExpanded ? 'Tutup Semua (Collapse All)' : 'Buka Semua (Expand All)' }}
              </Button>
            </template>
            <template #cell(latinName)="{ value }">
              <span class="italic font-semibold">
                {{ value || '-' }}
              </span>
            </template>
            <template #cell(rank)="{ value }">
              <Badge :variant="getRankBadgeVariant(String(value))">
                {{ value }}
              </Badge>
            </template>
            <template #cell(status)="{ value }">
              <Badge v-if="value" :variant="getStatusBadgeVariant(String(value))">
                {{ value }}
              </Badge>
              <span v-else class="text-muted-foreground font-mono text-xs">-</span>
            </template>
          </DataTable>
        </CardContent>
      </Card>
    </TabsContent>

    <!-- TAB 2: DOKUMENTASI -->
    <TabsContent value="documentation" class="mt-0 focus-visible:outline-none">
      <DataTableDocViewer :content="dataTableReadme" />
    </TabsContent>
  </Tabs>
</template>
