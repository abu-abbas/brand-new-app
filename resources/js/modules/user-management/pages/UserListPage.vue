<script setup lang="ts">
import { type UserResource, type UsersIndexParams } from '@/api/generated/api';
import AdminLayout from '@/components/AdminLayout.vue';
import { BadgeList } from '@/components/custom-ui/badge-list';
import { DataTable } from '@/components/custom-ui/data-table';
import type {
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
  DataTableParams,
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
import { usePermissionStore } from '@/stores/permission';
import { Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { userManagementFacade } from '../api/user-management.facade';
import UserFormModal from '../components/UserFormModal.vue';
import UserRoleModal from '../components/UserRoleModal.vue';
import { USER_PERMISSIONS } from '../permissions';

interface UserRow extends Record<string, unknown>, UserResource {}

const permission = usePermissionStore();

const dataTableRef = ref();
const formModalOpen = ref(false);
const roleModalOpen = ref(false);

const selectedUserId = ref<string | null>(null);
const selectedRoleUserId = ref<string | null>(null);

const activeStatus = ref<'all' | 'true' | 'false'>('all');

const canCreate = computed(() => permission.can(USER_PERMISSIONS.CREATE));
const canUpdate = computed(() => permission.can(USER_PERMISSIONS.UPDATE));

const fields: DataTableField<UserRow>[] = [
  {
    key: 'rownum',
    label: 'No',
    width: 50,
    sortable: false,
    filterColumn: false,
    align: 'center',
  },
  { key: 'userid', label: 'User ID', hidden: true },
  { key: 'username', label: 'Info Pengguna', minWidth: 200, sortable: true },
  { key: 'roles', label: 'Group Akses', minWidth: 180, sortable: false },
  { key: 'is_active', label: 'Status', minWidth: 100, align: 'center', sortable: true },
  { key: 'created_at', label: 'Tanggal Dibuat', minWidth: 140, sortable: true },
];

const filters: DataTableFilter[] = [
  { key: 'include_deleted', label: 'Sertakan data terhapus', type: 'boolean' },
];

const fetcher: DataTableFetcher<UserRow> = async ({
  params,
  signal,
}: {
  params: DataTableParams;
  signal?: unknown;
}) => {
  const query: UsersIndexParams = {
    page: params.page,
    per_page: params.per_page,
    search: params.search ? String(params.search) : undefined,
    sort_by: params.sort_by ? (String(params.sort_by) as UsersIndexParams['sort_by']) : undefined,
    sort_direction: params.sort_direction as 'asc' | 'desc' | undefined,
    active: activeStatus.value === 'all' ? undefined : activeStatus.value,
  };

  const res = await userManagementFacade.getUsers(query, signal as unknown as undefined);

  return {
    data: (res.data ?? []) as unknown as UserRow[],
    meta: {
      current_page: res.meta.current_page,
      from: res.meta.from,
      last_page: res.meta.last_page,
      per_page: res.meta.per_page,
      to: res.meta.to,
      total: res.meta.total,
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

const handleRefreshTable = () => {
  dataTableRef.value?.reload();
};

const handleOpenCreate = () => {
  selectedUserId.value = null;
  formModalOpen.value = true;
};

const handleOpenEdit = (user: UserRow) => {
  selectedUserId.value = user.id;
  formModalOpen.value = true;
};

const handleOpenRoleModal = (user: UserRow) => {
  selectedRoleUserId.value = user.id;
  roleModalOpen.value = true;
};
</script>

<template>
  <AdminLayout :icon="Users">
    <template #header-actions>
      <Button v-if="canCreate" class="ml-auto gap-2" @click="handleOpenCreate">
        <LucideIcon name="Plus" data-icon="inline-start" />
        Tambah Pengguna
      </Button>
    </template>

    <div class="rounded-2xl border bg-background p-4 shadow-2xs md:p-6">
      <DataTable
        ref="dataTableRef"
        query-key="users"
        title="Daftar Pengguna"
        :fetcher="fetcher as unknown as DataTableFetcher<Record<string, unknown>>"
        :fields="fields as unknown as DataTableField<Record<string, unknown>>[]"
        :filters="filters"
        row-key="id"
        remember="users"
        search-placeholder="Cari berdasarkan User ID, nama, atau email..."
        actions
        :actions-width="90"
        :can-edit="() => canUpdate"
        :can-delete="() => false"
        @edit="(row: unknown) => handleOpenEdit(row as UserRow)"
        @create="handleOpenCreate"
      >
        <template #action-extra="{ row }">
          <Button
            v-if="canUpdate"
            variant="ghost"
            size="icon"
            class="h-8 w-8 text-primary/80 hover:text-primary hover:bg-primary/10"
            title="Atur Penugasan Role & Scope"
            @click="handleOpenRoleModal(row as UserRow)"
          >
            <LucideIcon name="Shield" class="h-4 w-4" />
          </Button>
        </template>

        <template #toolbar>
          <div class="flex justify-end">
            <Select v-model="activeStatus" @update:model-value="handleRefreshTable">
              <SelectTrigger class="w-40" aria-label="Filter status pengguna">
                <SelectValue placeholder="Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Status akun</SelectLabel>
                  <SelectItem value="all">Semua Status</SelectItem>
                  <SelectItem value="true">Aktif</SelectItem>
                  <SelectItem value="false">Nonaktif</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </template>

        <template #cell(username)="{ value, row }">
          <div class="flex flex-col">
            <span class="font-medium text-sm">{{ value }}</span>
            <span v-if="row.email" class="text-xs text-muted-foreground">{{ row.email }}</span>
            <Badge
              v-if="row.is_external"
              class="text-xs py-0 px-1 bg-primary/5 border-primary/10 text-foreground"
            >
              Integrasi
            </Badge>
          </div>
        </template>

        <template #cell(roles)="{ row }">
          <div
            v-if="row.roles && (row.roles as string[]).length > 0"
            class="flex flex-wrap items-center gap-1.5"
          >
            <BadgeList
              :items="
                (row as UserRow).user_roles?.map((r) => ({
                  name: r.role_name ?? r.role_code,
                  code: r.role_code,
                })) ?? []
              "
              :max="3"
            />
            <Button
              v-if="canUpdate"
              variant="ghost"
              size="xs"
              class="h-6 px-1.5 text-muted-foreground hover:text-primary cursor-pointer"
              title="Ubah Penugasan Group"
              @click="handleOpenRoleModal(row as UserRow)"
            >
              <LucideIcon name="Edit3" class="size-3.5" />
            </Button>
          </div>
          <div v-else>
            <Button
              v-if="canUpdate"
              variant="outline"
              size="xs"
              class="cursor-pointer h-6 px-2 text-xs gap-1 border-dashed text-muted-foreground hover:text-primary hover:border-primary"
              @click="handleOpenRoleModal(row as UserRow)"
            >
              <LucideIcon name="Plus" class="size-3" />
              Tambah Group
            </Button>
            <span v-else class="text-xs text-muted-foreground">—</span>
          </div>
        </template>

        <template #cell(is_active)="{ value }">
          <Badge
            v-if="value"
            variant="outline"
            class="font-normal border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs"
          >
            Aktif
          </Badge>
          <Badge
            v-else
            variant="outline"
            class="font-normal border-destructive/30 text-destructive text-xs"
          >
            Nonaktif
          </Badge>
        </template>

        <template #cell(created_at)="{ value }">
          <span>{{ formatDate(value) }}</span>
        </template>
      </DataTable>
    </div>

    <!-- Modal Form Edit/Tambah Profil Pengguna -->
    <UserFormModal
      v-model:open="formModalOpen"
      :user-id="selectedUserId"
      @saved="handleRefreshTable"
    />

    <!-- Modal Khusus Penugasan Role & Scope -->
    <UserRoleModal
      v-model:open="roleModalOpen"
      :user-id="selectedRoleUserId"
      @saved="handleRefreshTable"
    />
  </AdminLayout>
</template>
