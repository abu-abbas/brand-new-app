<script setup lang="ts">
import { rolesOptions, type RoleOptionResource, type UserRoleResource } from '@/api/generated/api';
import Combobox from '@/components/custom-ui/combobox/Combobox.vue';
import { DatePicker, type DateRangeValue } from '@/components/custom-ui/date-picker';
import Modal from '@/components/custom-ui/modal/Modal.vue';

import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import {
  useUserScope,
  type PerangkatDaerahOption,
  type WilayahOption,
} from '@/composables/useUserScope';
import { normalizeAppError } from '@/lib/axios';
import { Plus, ShieldCheck, Trash } from '@lucide/vue';
import { ElForm, ElFormItem, type FormInstance } from 'element-plus';
import { computed, nextTick, ref, watch } from 'vue';
import { useUpdateUserMutation } from '../mutations/useUpdateUserMutation';
import { usePerangkatDaerahListQuery } from '../queries/usePerangkatDaerahListQuery';
import { useUserDetailQuery } from '../queries/useUserDetailQuery';
import { useWilayahListQuery } from '../queries/useWilayahListQuery';

const props = defineProps<{
  open: boolean;
  userId?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'saved'): void;
}>();

const confirmDialog = useConfirmDialog();
const { filterPdList, filterWilayahList } = useUserScope();
const targetId = computed(() => props.userId ?? null);

const { data: userDetailRes, isLoading: isFetchingDetail } = useUserDetailQuery(targetId);
const { data: wilayahRes } = useWilayahListQuery();
const { data: pdRes } = usePerangkatDaerahListQuery();

const formattedWilayahOptions = computed(() =>
  filterWilayahList(
    ((wilayahRes.value?.data ?? []) as WilayahOption[]).map((w) => ({
      code: w.code || '',
      name: w.name || '',
    })),
  ).map((w) => ({
    label: w.name,
    value: w.code,
  })),
);
const pdOptions = computed(() =>
  filterPdList(
    ((pdRes.value?.data ?? []) as PerangkatDaerahOption[]).map((pd) => ({
      code: pd.code || '',
      name: pd.name || '',
      spmu: pd.spmu,
      sipkd_code: pd.sipkd_code,
    })),
  ),
);

const availableRoles = ref<
  { code: string; name: string; need_region: boolean; need_unit: boolean }[]
>([]);
const isFetchingRoles = ref(false);

const userName = ref('');
const userIdCode = ref('');

const fetchRoles = async () => {
  isFetchingRoles.value = true;
  try {
    const res = await rolesOptions();
    const data = res.data ?? [];
    availableRoles.value = data.map((r: RoleOptionResource) => ({
      code: r.code || '',
      name: r.name || r.code || '',
      need_region: Boolean(r.need_region),
      need_unit: Boolean(r.need_unit),
    }));
  } catch {
    availableRoles.value = [];
  } finally {
    isFetchingRoles.value = false;
  }
};

interface UserRoleFormItem {
  role_code: string;
  wilayah?: string[];
  unit?: string;
  pelaksana?: string;
  _hasPelaksana?: boolean;
  valid_from?: string;
  valid_until?: string;
  _hasPeriod?: boolean;
}

const roles = ref<Array<UserRoleFormItem>>([]);
const itemRefs = ref<Array<HTMLElement>>([]);

const getRoleConfig = (roleCode: string) => {
  return availableRoles.value.find((r) => r.code === roleCode);
};

const isAddGroupDisabled = computed(() => {
  if (roles.value.length === 0) return false;
  return roles.value.some((item) => {
    if (!item.role_code) return true;
    const config = getRoleConfig(item.role_code);
    if (!config) return false;
    if (config.need_region && (!item.wilayah || item.wilayah.length === 0)) return true;
    if (config.need_unit && !item.unit) return true;
    return false;
  });
});

const syncFormDataFromDetail = () => {
  const u = userDetailRes.value?.data;
  if (!u || !props.userId) return;

  userName.value = u.username || u.name || '';
  userIdCode.value = u.userid || '';

  if (u.user_roles && Array.isArray(u.user_roles)) {
    roles.value = (u.user_roles as unknown as UserRoleResource[]).map((r: UserRoleResource) => {
      let wilayahArr: string[] = [];
      if (r.wilayah) {
        if (Array.isArray(r.wilayah)) {
          wilayahArr = r.wilayah;
        } else if (typeof r.wilayah === 'string') {
          wilayahArr = r.wilayah
            .split(',')
            .map((w) => w.trim())
            .filter(Boolean);
        }
      }
      return {
        role_code: r.role_code || '',
        wilayah: wilayahArr,
        unit: r.unit || undefined,
        pelaksana: r.pelaksana || undefined,
        _hasPelaksana: !!r.pelaksana,
        valid_from: r.valid_from || undefined,
        valid_until: r.valid_until || undefined,
        _hasPeriod: !!(r.valid_from || r.valid_until),
      };
    });
  } else {
    roles.value = [];
  }
};

watch(
  () => props.open,
  (val) => {
    if (val) {
      submitError.value = '';
      formRef.value?.clearValidate();
      fetchRoles();
      if (props.userId && userDetailRes.value) {
        syncFormDataFromDetail();
      }
    }
  },
  { immediate: true },
);

watch(
  () => userDetailRes.value,
  () => {
    if (props.open && props.userId) {
      syncFormDataFromDetail();
    }
  },
  { immediate: true },
);

const addRoleItem = async () => {
  if (isAddGroupDisabled.value) return;
  roles.value.push({
    role_code: '',
    wilayah: undefined,
    unit: undefined,
    pelaksana: undefined,
    _hasPelaksana: false,
    valid_from: undefined,
    valid_until: undefined,
    _hasPeriod: false,
  });

  await nextTick();
  const lastIndex = roles.value.length - 1;
  const lastElem = itemRefs.value[lastIndex];
  if (lastElem) {
    lastElem.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }
};

const removeRoleItem = (index: number) => {
  roles.value.splice(index, 1);
  itemRefs.value.splice(index, 1);
};

function getRolePeriod(roleItem: UserRoleFormItem): DateRangeValue {
  return {
    start: roleItem.valid_from,
    end: roleItem.valid_until,
  };
}

function setRolePeriod(roleItem: UserRoleFormItem, val: unknown) {
  const rangeVal = val as DateRangeValue | null;
  roleItem.valid_from = rangeVal?.start || undefined;
  roleItem.valid_until = rangeVal?.end || undefined;
}

const updateUserMutation = useUpdateUserMutation();

const isSubmitting = computed(() => updateUserMutation.isPending.value);

const formRef = ref<FormInstance>();
const submitError = ref<string>('');

const formModel = computed(() => ({
  roles: roles.value,
}));

const handleSubmit = async () => {
  if (!props.userId || !userDetailRes.value?.data) return;

  submitError.value = '';

  if (roles.value.length === 0) {
    submitError.value = 'Minimal 1 group penugasan harus ditambahkan.';
    return;
  }

  if (formRef.value) {
    const isValid = await formRef.value.validate().catch(() => false);
    if (!isValid) return;
  }

  const currentDetail = userDetailRes.value.data;

  // Strip internal flags sebelum kirim ke server
  const cleanRoles = roles.value
    .filter((r) => r.role_code)
    .map((r) => ({
      role_code: r.role_code,
      wilayah:
        Array.isArray(r.wilayah) && r.wilayah.length > 0
          ? r.wilayah.join(',')
          : typeof r.wilayah === 'string'
            ? r.wilayah
            : undefined,
      unit: r.unit,
      pelaksana: r.pelaksana,
      valid_from: r.valid_from,
      valid_until: r.valid_until,
    }));

  try {
    await confirmDialog({
      title: 'Simpan penugasan group?',
      description: `Penugasan group dan batasan scope untuk pengguna "${userName.value || currentDetail.username}" akan disimpan.`,
      confirmLabel: 'Simpan Penugasan',
      loadingLabel: 'Menyimpan...',
      onConfirm: async () => {
        await updateUserMutation.mutateAsync({
          id: props.userId!,
          data: {
            username: currentDetail.username || currentDetail.name || '',
            email: currentDetail.email || undefined,
            is_active: currentDetail.is_active ?? true,
            is_external: currentDetail.is_external ?? false,
            roles: cleanRoles,
          },
        });

        emit('update:open', false);
        emit('saved');
      },
      successTitle: 'Penugasan group berhasil disimpan',
      successDescription: `Hak akses group untuk pengguna "${userName.value || currentDetail.username}" telah diperbarui.`,
      errorTitle: 'Penugasan group gagal disimpan',
      errorDescription: (error: unknown) => normalizeAppError(error).message,
    });
  } catch {
    // Error ditangani oleh ConfirmDialog.
  }
};
</script>

<template>
  <Modal
    :open="open"
    size="lg"
    as-form
    :close-on-interact-outside="false"
    :loading="isSubmitting || isFetchingDetail || isFetchingRoles"
    body-class="custom-form space-y-0"
    confirm-text="Simpan Penugasan"
    @update:open="(val) => emit('update:open', val)"
    @confirm="handleSubmit"
  >
    <template #header>
      <div class="flex items-start gap-3">
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
        >
          <ShieldCheck class="size-5" />
        </div>
        <div class="flex min-w-0 flex-col gap-0.5">
          <DialogTitle class="font-semibold leading-snug">
            Penugasan Group & Scope: {{ userName }}
          </DialogTitle>
          <DialogDescription class="text-2sm pr-4">
            Atur hak akses group dan batasan wilayah/perangkat daerah untuk User ID {{ userIdCode }}
          </DialogDescription>
        </div>
      </div>
    </template>

    <div class="space-y-4">
      <div class="flex items-end justify-between">
        <p class="text-2sm text-muted-foreground pr-5">
          Daftar group dan batasan scope wilayah/unit kerja yang ditugaskan kepada pengguna ini.
        </p>
        <Button size="sm" type="button" :disabled="isAddGroupDisabled" @click="addRoleItem">
          <Plus class="w-4 h-4" />
          Tambah Group
        </Button>
      </div>

      <div
        v-if="roles.length === 0"
        class="flex flex-col items-center gap-3 p-8 border rounded-lg"
        :class="submitError ? 'border-destructive bg-destructive/5' : 'border-dashed'"
      >
        <p class="text-2sm text-center text-muted-foreground">
          Belum ada group yang ditugaskan. Klik
          <span class="font-medium text-foreground">"+ Tambah Group"</span>
          untuk menambahkan hak akses.
        </p>
        <p v-if="submitError" class="text-xs font-medium text-destructive">
          {{ submitError }}
        </p>
      </div>

      <ElForm
        v-else
        ref="formRef"
        :model="formModel"
        label-position="top"
        require-asterisk-position="right"
        status-icon
        class="space-y-4"
      >
        <div
          v-for="(roleItem, index) in roles"
          :key="index"
          :ref="
            (el) => {
              if (el) itemRefs[index] = el as HTMLElement;
            }
          "
          class="p-4 rounded-lg border bg-card space-y-4 relative group"
        >
          <div class="flex items-center justify-between border-b pb-2">
            <span class="text-2sm font-semibold text-primary"> Group #{{ index + 1 }} </span>
            <Button
              size="icon-xs"
              variant="ghost"
              class="text-destructive hover:bg-destructive/10"
              type="button"
              @click="removeRoleItem(index)"
            >
              <Trash class="size-3.5" />
            </Button>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <ElFormItem
              label="Group"
              :prop="`roles.${index}.role_code`"
              :rules="[
                { required: true, message: 'Group wajib dipilih.', trigger: ['change', 'blur'] },
              ]"
            >
              <Select v-model="roleItem.role_code">
                <SelectTrigger class="h-9 text-2sm w-full">
                  <SelectValue placeholder="Pilih Group" class="truncate" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="r in availableRoles" :key="r.code" :value="r.code">
                    {{ r.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </ElFormItem>

            <ElFormItem
              v-if="getRoleConfig(roleItem.role_code)?.need_region"
              label="Wilayah"
              :prop="`roles.${index}.wilayah`"
              :rules="[
                {
                  required: true,
                  validator: (_rule, val, callback) => {
                    if (!val || (Array.isArray(val) && val.length === 0)) {
                      callback(new Error('Wilayah wajib dipilih minimal 1.'));
                    } else {
                      callback();
                    }
                  },
                  trigger: ['change', 'blur'],
                },
              ]"
            >
              <Combobox
                v-model="roleItem.wilayah"
                :options="formattedWilayahOptions"
                multiple
                clear-all
                placeholder="Pilih Wilayah"
                search-placeholder="Cari wilayah..."
              />
            </ElFormItem>

            <ElFormItem
              v-if="getRoleConfig(roleItem.role_code)?.need_unit"
              label="Perangkat Daerah"
              :prop="`roles.${index}.unit`"
              :rules="[
                {
                  required: true,
                  message: 'Perangkat Daerah wajib dipilih.',
                  trigger: ['change', 'blur'],
                },
              ]"
            >
              <Select v-model="roleItem.unit">
                <SelectTrigger class="w-full h-9 text-xs">
                  <SelectValue placeholder="Semua Unit" class="truncate" />
                </SelectTrigger>
                <SelectContent class="max-w-xl max-h-60 overflow-y-auto">
                  <SelectItem
                    v-for="pd in pdOptions"
                    :key="pd.code"
                    :value="pd.code"
                    class="text-xs py-2 whitespace-normal wrap-break-word"
                  >
                    {{ pd.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </ElFormItem>

            <!-- Pelaksana toggle card -->
            <div class="rounded-lg border bg-muted/30 p-3 space-y-2">
              <div class="flex items-center justify-between">
                <div class="min-w-0">
                  <p class="text-xs font-semibold">Pelaksana</p>
                  <p class="text-[11px] text-muted-foreground leading-snug">
                    Aktifkan untuk menugaskan sebagai pelaksana.
                  </p>
                </div>
                <Switch
                  :model-value="!!roleItem.pelaksana || !!roleItem._hasPelaksana"
                  @update:model-value="
                    (val) => {
                      roleItem._hasPelaksana = val;
                      if (val) {
                        if (!roleItem.pelaksana) roleItem.pelaksana = 'PLT';
                      } else {
                        roleItem.pelaksana = undefined;
                      }
                    }
                  "
                />
              </div>
              <ElFormItem
                v-if="!!roleItem.pelaksana || roleItem._hasPelaksana"
                :prop="`roles.${index}.pelaksana`"
                :rules="[
                  {
                    required: true,
                    message: 'Tipe pelaksana wajib dipilih.',
                    trigger: ['change', 'blur'],
                  },
                ]"
              >
                <Select v-model="roleItem.pelaksana">
                  <SelectTrigger class="h-9 text-xs w-full">
                    <SelectValue placeholder="Pilih Tipe Pelaksana" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="PLT" class="text-xs">PLT - Pelaksana Tugas</SelectItem>
                    <SelectItem value="PLH" class="text-xs">PLH - Pelaksana Harian</SelectItem>
                    <SelectItem value="PJ" class="text-xs">PJ - Penjabat</SelectItem>
                  </SelectContent>
                </Select>
              </ElFormItem>
            </div>

            <!-- Periode Aktif toggle card -->
            <div class="rounded-lg border bg-muted/30 p-3 space-y-2">
              <div class="flex items-center justify-between">
                <div class="min-w-0">
                  <p class="text-xs font-semibold">Periode Aktif</p>
                  <p class="text-[11px] text-muted-foreground leading-snug">
                    Aktifkan untuk membatasi dengan periode.
                  </p>
                </div>
                <Switch
                  :model-value="!!roleItem._hasPeriod"
                  @update:model-value="
                    (val) => {
                      roleItem._hasPeriod = val;
                      if (!val) {
                        roleItem.valid_from = undefined;
                        roleItem.valid_until = undefined;
                      }
                    }
                  "
                />
              </div>
              <ElFormItem
                v-if="roleItem._hasPeriod"
                :prop="`roles.${index}.valid_from`"
                :rules="[
                  {
                    required: true,
                    validator: (_rule, val, callback) => {
                      if (!val || !roleItem.valid_until) {
                        callback(new Error('Rentang tanggal periode aktif wajib diisi.'));
                      } else {
                        callback();
                      }
                    },
                    trigger: ['change', 'blur'],
                  },
                ]"
              >
                <DatePicker
                  :model-value="getRolePeriod(roleItem)"
                  mode="range"
                  clearable
                  placeholder="Pilih rentang tanggal aktif"
                  @update:model-value="(val) => setRolePeriod(roleItem, val)"
                />
              </ElFormItem>
            </div>
          </div>
        </div>
      </ElForm>
    </div>
  </Modal>
</template>
