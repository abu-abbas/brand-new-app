<script setup lang="ts">
import { ref } from 'vue';
import { ChevronsUpDown, Network, Server, User } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { MarkdownText } from '@/components/custom-ui/markdown-text';
import DataTable from './DataTable.vue';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableInstance,
  DataTableParams,
} from './data-table.types';
import { searchRows, sortRows } from './data-table.utils';

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

const selectedUsers = ref<UserRow[]>([]);

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
    userFields,
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
                  {
                    id: 'family-canidae',
                    name: 'Canidae (Keluarga Anjing & Serigala)',
                    latinName: 'Canidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-canis-lupus',
                        name: 'Serigala Abu-abu',
                        latinName: 'Canis lupus',
                        rank: 'Species',
                        status: 'Least Concern',
                      },
                      {
                        id: 'species-vulpes-vulpes',
                        name: 'Rubah Merah',
                        latinName: 'Vulpes vulpes',
                        rank: 'Species',
                        status: 'Least Concern',
                      },
                    ],
                  },
                  {
                    id: 'family-ursidae',
                    name: 'Ursidae (Keluarga Beruang)',
                    latinName: 'Ursidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-helarctos-malayanus',
                        name: 'Beruang Madu',
                        latinName: 'Helarctos malayanus',
                        rank: 'Species',
                        status: 'Vulnerable',
                      },
                      {
                        id: 'species-ailuropoda-melanoleuca',
                        name: 'Panda Raksasa',
                        latinName: 'Ailuropoda melanoleuca',
                        rank: 'Species',
                        status: 'Vulnerable',
                      },
                    ],
                  },
                ],
              },
              {
                id: 'order-proboscidea',
                name: 'Proboscidea (Bebelalai)',
                latinName: 'Proboscidea',
                rank: 'Order',
                children: [
                  {
                    id: 'family-elephantidae',
                    name: 'Elephantidae (Gajah)',
                    latinName: 'Elephantidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-elephas-maximus-sumatranus',
                        name: 'Gajah Sumatra',
                        latinName: 'Elephas maximus sumatranus',
                        rank: 'Species',
                        status: 'Critically Endangered',
                      },
                    ],
                  },
                ],
              },
              {
                id: 'order-primates',
                name: 'Primates (Primata)',
                latinName: 'Primates',
                rank: 'Order',
                children: [
                  {
                    id: 'family-hominidae',
                    name: 'Hominidae (Kera Besar)',
                    latinName: 'Hominidae',
                    rank: 'Family',
                    children: [
                      {
                        id: 'species-pongo-pygmaeus',
                        name: 'Orangutan Kalimantan',
                        latinName: 'Pongo pygmaeus',
                        rank: 'Species',
                        status: 'Critically Endangered',
                      },
                      {
                        id: 'species-pan-troglodytes',
                        name: 'Simpanse',
                        latinName: 'Pan troglodytes',
                        rank: 'Species',
                        status: 'Endangered',
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
                    name: 'Bucerotidae (Burung Enggang)',
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
  <div class="flex flex-col gap-8">
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

    <!-- Card 2: Mode Server -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Server class="size-5 text-primary" />
          2. DataTable Mode Server (Mock API Fetcher)
        </CardTitle>
        <CardDescription>
          <MarkdownText
            content="Data diambil secara *async* melalui `fetcher` prop dengan format *response* standar Laravel (`data`, `meta`, `message`)."
          />
        </CardDescription>
      </CardHeader>
      <CardContent>
        <DataTable
          title="Pengguna Server"
          query-key="users-demo"
          :fetcher="serverFetcher"
          :fields="userFields"
          :filters="userFilters"
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
            <span class="italic text-muted-foreground font-mono text-xs">
              {{ value || '-' }}
            </span>
          </template>
          <template #cell(rank)="{ value }">
            <Badge :variant="getRankBadgeVariant(String(value))" class="text-[10px]">
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
  </div>
</template>
