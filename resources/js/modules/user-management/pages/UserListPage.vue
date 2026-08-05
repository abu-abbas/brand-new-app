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
import { formatHumanDate } from '@/lib/utils';
import { usePermissionStore } from '@/stores/permission';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { userManagementFacade } from '../api/user-management.facade';
import UserFormModal from '../components/UserFormModal.vue';
import UserRoleModal from '../components/UserRoleModal.vue';
import { USER_PERMISSIONS } from '../permissions';

import { useAuthStore } from '@/stores/auth';
import SelectImpersonateGroupModal from '../components/SelectImpersonateGroupModal.vue';

interface UserRow extends Record<string, unknown>, UserResource {}

const permission = usePermissionStore();
const authStore = useAuthStore();
const confirmDialog = useConfirmDialog();

const dataTableRef = ref();
const formModalOpen = ref(false);
const roleModalOpen = ref(false);
const groupModalOpen = ref(false);

const selectedUserId = ref<string | null>(null);
const selectedRoleUserId = ref<string | null>(null);

const impersonateTarget = ref<UserRow | null>(null);
const impersonateLoading = ref(false);

const activeStatus = ref<'all' | 'true' | 'false'>('all');

const canCreate = computed(() => permission.can(USER_PERMISSIONS.CREATE));
const canUpdate = computed(() => permission.can(USER_PERMISSIONS.UPDATE));
const canResetPassword = computed(() => permission.can(USER_PERMISSIONS.RESET_PASSWORD));
const canImpersonate = computed(() => permission.can('impersonate-pengguna'));

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
  { key: 'is_active', label: 'Status', minWidth: 90, align: 'center', sortable: true },
  { key: 'created_at', label: 'Tanggal Dibuat', minWidth: 120, sortable: true },
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

const handleSendPasswordLink = (user: UserRow) => {
  const isVerified = user.is_verified;
  const title = isVerified ? 'Kirim Link Reset Password' : 'Kirim Ulang Verifikasi';

  void confirmDialog({
    title,
    description: `Apakah Anda yakin ingin mengirimkan tautan ${isVerified ? 'reset password' : 'verifikasi'} ke email ${user.email || user.username}?`,
    confirmLabel: isVerified ? 'Kirim Reset Password' : 'Kirim Verifikasi',
    successTitle: 'Berhasil',
    successDescription: `Tautan ${isVerified ? 'reset password' : 'verifikasi'} berhasil dikirim ke email.`,
    onConfirm: async () => {
      await userManagementFacade.sendPasswordLink(user.id);
    },
  });
};

const impersonateValidRoles = computed(() => {
  if (!impersonateTarget.value) return [];
  const user = impersonateTarget.value;
  if (Array.isArray(user.user_roles) && user.user_roles.length > 0) {
    return (
      user.user_roles as Array<{
        role_code: string;
        role_name?: string;
        unit_name?: string | null;
        wilayah_name?: string | null;
        is_expired?: boolean;
      }>
    )
      .filter((r) => !r.is_expired)
      .map((r) => ({
        code: r.role_code,
        name: r.role_name || r.role_code,
        subtitle: r.unit_name ?? r.wilayah_name ?? null,
      }));
  }
  if (Array.isArray(user.roles)) {
    return user.roles.map((r) => (typeof r === 'string' ? { code: r, name: r } : r));
  }
  return [];
});

const handleStartImpersonate = async (user: UserRow, targetGroupId?: string) => {
  impersonateLoading.value = true;
  try {
    sessionStorage.setItem(
      'impersonate_return_url',
      window.location.pathname + window.location.search,
    );
    await authStore.startImpersonate(user.id, targetGroupId);
    groupModalOpen.value = false;
    window.location.href = '/';
  } finally {
    impersonateLoading.value = false;
  }
};

const handleImpersonateClick = (user: UserRow) => {
  impersonateTarget.value = user;
  const validRoles = impersonateValidRoles.value;

  if (validRoles.length > 1) {
    groupModalOpen.value = true;
  } else {
    const singleRoleCode = validRoles.length === 1 ? validRoles[0].code : undefined;
    void confirmDialog({
      title: 'Mulai Impersonate Pengguna',
      description: `Apakah Anda yakin ingin bertindak sebagai ${user.name || user.username} (${user.userid})?`,
      confirmLabel: 'Mulai Impersonate',
      cancelLabel: 'Batal',
      onConfirm: async () => {
        await handleStartImpersonate(user, singleRoleCode);
      },
    });
  }
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
        :actions-width="50"
        :can-edit="() => canUpdate"
        :can-delete="() => false"
        @edit="(row: unknown) => handleOpenEdit(row as UserRow)"
        @create="handleOpenCreate"
      >
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
          <div class="flex flex-col gap-0.5">
            <span class="font-medium text-sm">{{ value }}</span>
            <span v-if="row.email" class="text-xs text-muted-foreground">{{ row.email }}</span>
            <div class="flex flex-wrap items-center gap-1 mt-0.5">
              <Badge
                v-if="row.is_external"
                class="text-2xs py-0 px-1 bg-blue-500/20 border-blue-500/30 text-foreground"
              >
                Integrasi
              </Badge>
              <Badge
                v-else-if="!row.is_verified"
                variant="outline"
                class="text-2xs py-0 px-1 border-amber-500/30 text-amber-600 dark:text-amber-400 bg-amber-500/5 font-normal"
              >
                Menunggu Verifikasi
              </Badge>

              <Button
                v-if="canResetPassword && !(row as UserRow).is_external"
                variant="outline"
                size="xs"
                class="cursor-pointer text-muted-foreground hover:text-primary"
                :title="(row as UserRow).is_verified ? 'Reset Password' : 'Kirim Ulang Verifikasi'"
                @click="handleSendPasswordLink(row as UserRow)"
              >
                <LucideIcon v-if="(row as UserRow).is_verified" name="LucideKey" class="size-4" />
                <LucideIcon v-else name="Mail" class="size-4" />

                <span class="text-xs">
                  {{ (row as UserRow).is_verified ? 'Reset Password' : 'Kirim Ulang Verifikasi' }}
                </span>
              </Button>

              <Button
                v-if="
                  canImpersonate &&
                  (row as UserRow).userid !== authStore.user?.userid &&
                  (row as UserRow).is_active
                "
                variant="secondary"
                size="xs"
                class="cursor-pointer gap-1 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/30"
                title="Bertindak sebagai pengguna ini"
                @click="handleImpersonateClick(row as UserRow)"
              >
                <LucideIcon name="UserCheck" class="size-3.5 text-amber-600 dark:text-amber-400" />
                <span class="text-xs font-medium">Impersonate</span>
              </Button>
            </div>
          </div>
        </template>

        <template #cell(roles)="{ row }">
          <div
            v-if="
              ((row as UserRow).user_roles && ((row as UserRow).user_roles?.length ?? 0) > 0) ||
              (row.roles && (row.roles as string[]).length > 0)
            "
            class="flex flex-wrap items-center gap-1.5"
          >
            <BadgeList
              :items="
                (row as UserRow).user_roles && (row as UserRow).user_roles!.length > 0
                  ? (row as UserRow).user_roles!.map((r) => {
                      const isExpired = Boolean(r.is_expired);
                      const roleName = r.role_name ?? r.role_code;
                      const formattedDate = r.valid_until ? formatHumanDate(r.valid_until) : '';
                      return {
                        name: isExpired ? `${roleName}` : roleName,
                        code: r.role_code,
                        expired: isExpired,
                        title: isExpired
                          ? formattedDate
                            ? `Kedaluwarsa pada ${formattedDate}`
                            : 'Kedaluwarsa (Periode master group telah berakhir)'
                          : r.valid_until
                            ? `Berlaku hingga ${formattedDate}`
                            : undefined,
                      };
                    })
                  : ((row.roles as string[])?.map((roleName) => ({
                      name: roleName,
                    })) ?? [])
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

    <!-- Modal Pemilihan Group Target Impersonate -->
    <SelectImpersonateGroupModal
      v-if="impersonateTarget"
      v-model:open="groupModalOpen"
      :target-name="impersonateTarget.name || impersonateTarget.username"
      :roles="impersonateValidRoles"
      :loading="impersonateLoading"
      @submit="(groupId) => handleStartImpersonate(impersonateTarget!, groupId)"
    />
  </AdminLayout>
</template>
