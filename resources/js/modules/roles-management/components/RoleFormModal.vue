<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { ElForm, ElFormItem, type FormInstance, type FormRules } from 'element-plus';
import { CircleAlert, CircleHelp, RotateCcw, ShieldCheck, Trash2 } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import DatePicker from '@/components/custom-ui/date-picker/DatePicker.vue';
import { Modal } from '@/components/custom-ui/modal';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { normalizeAppError } from '@/lib/axios';
import { useAuthStore } from '@/stores/auth';
import { usePermissionStore } from '@/stores/permission';
import PermissionTree from './PermissionTree.vue';
import {
  RolesFacade,
  type PermissionTreeNode,
  type RoleRow,
  type StoreRolePayload,
} from '../api/roles.facade';
import { ROLE_PERMISSIONS } from '../permissions';

interface Props {
  role?: RoleRow | null;
}

const props = withDefaults(defineProps<Props>(), {
  role: null,
});

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
  (e: 'submitted'): void;
}>();

const formRef = ref<FormInstance>();
const confirmDialog = useConfirmDialog();
const authStore = useAuthStore();
const permissionStore = usePermissionStore();

const userRoleCode = computed(() => {
  return String(authStore.activeGroup ?? '').toUpperCase();
});

const userRolePrefix = computed(() => {
  if (isRoot.value) return '';
  return `${userRoleCode.value}_`;
});

const codeSuffix = ref('');

function updateCodeFromSuffix(value: string): void {
  const formatted = value.replace(/\s+/g, '_').toUpperCase();
  codeSuffix.value = formatted;
  if (isEdit.value) {
    const prefix = userRolePrefix.value;
    const rawCode = props.role?.code ?? '';
    if (prefix && rawCode.toUpperCase().startsWith(prefix)) {
      form.code = formatted.trim() ? `${prefix}${formatted}` : '';
    } else {
      form.code = formatted;
    }
  } else {
    form.code = formatted.trim() ? `${userRolePrefix.value}${formatted}` : '';
  }
  validateField('code');
}

const isRoot = computed(() => Boolean(authStore.user?.is_root));

const isEdit = computed(() => Boolean(props.role));
const isDeleted = computed(() => Boolean(props.role?.deleted_at));
const isLocked = ref(false);
const isPending = ref(false);
const submitError = ref('');
const serverErrors = reactive<Record<string, string>>({});

// Toggle state untuk limitasi group
const hasTimeLimit = ref(false);
const permissionNodes = ref<PermissionTreeNode[]>([]);
const isTreeLoading = ref(false);

const form = reactive<StoreRolePayload>({
  code: '',
  name: '',
  level: 0,
  need_region: false,
  need_unit: false,
  features: [],
  active_periode: { start: null, end: null },
});

// Rules validasi Element Plus Form
const rules: FormRules = {
  code: [
    { required: true, message: 'Kode Group wajib diisi.', trigger: 'blur' },
    {
      validator: (_rule, value, callback) => {
        if (!value) return callback();
        if (!/^[A-Z0-9_]+$/.test(value)) {
          return callback(
            new Error('Kode Group hanya boleh berisi huruf kapital, angka, dan underscore (_).'),
          );
        }
        callback();
      },
      trigger: 'blur',
    },
  ],
  name: [
    { required: true, message: 'Nama Group wajib diisi.', trigger: 'blur' },
    { min: 3, max: 150, message: 'Nama Group antara 3 - 150 karakter.', trigger: 'blur' },
  ],
};

function resetForm(): void {
  const role = props.role;
  isLocked.value = Boolean(role?.locked);
  hasTimeLimit.value = Boolean(role?.active_periode?.start || role?.active_periode?.end);

  const rawCode = role?.code ?? '';
  if (!isRoot.value) {
    const prefix = userRolePrefix.value;
    if (prefix && rawCode.toUpperCase().startsWith(prefix)) {
      codeSuffix.value = rawCode.substring(prefix.length);
    } else {
      codeSuffix.value = rawCode;
    }
  } else {
    codeSuffix.value = rawCode;
  }

  Object.assign(form, {
    code: rawCode,
    name: role?.name ?? '',
    level: role?.level ?? 0,
    need_region: Boolean(role?.need_region),
    need_unit: Boolean(role?.need_unit),
    features: role?.feature_aliases
      ? [...role.feature_aliases]
      : (role?.features?.map((f) => (typeof f === 'string' ? f : f.alias)) ?? []),
    active_periode: role?.active_periode ? { ...role.active_periode } : { start: null, end: null },
  });

  if (!isEdit.value && !isRoot.value) {
    form.need_region = false;
    form.need_unit = true;
    form.code = codeSuffix.value.trim() ? `${userRolePrefix.value}${codeSuffix.value}` : '';
  }

  submitError.value = '';
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);
  formRef.value?.clearValidate();
}

async function loadPermissionTree(): Promise<void> {
  isTreeLoading.value = true;
  try {
    const nodes = await RolesFacade.getPermissionTree();
    permissionNodes.value = nodes;
  } finally {
    isTreeLoading.value = false;
  }
}

watch(
  () => form.need_region,
  (enabled) => {
    if (enabled) {
      form.need_unit = false;
    }
  },
);

watch(
  () => form.need_unit,
  (enabled) => {
    if (enabled) {
      form.need_region = false;
    }
  },
);

watch(hasTimeLimit, (enabled) => {
  if (!enabled && form.active_periode) {
    form.active_periode.start = null;
    form.active_periode.end = null;
  }
});

function validateField(field: keyof StoreRolePayload): void {
  delete serverErrors[field];
  void formRef.value?.validateField(field).catch(() => undefined);
}

watch(
  () => form.code,
  (val) => {
    if (val && /\s/.test(val)) {
      form.code = val.replace(/\s+/g, '_');
    }
  },
);

async function submit(): Promise<void> {
  if (isDeleted.value) return;

  submitError.value = '';

  const isValid = await formRef.value?.validate().catch(() => false);
  if (!isValid) return;

  const role = props.role;
  const payload: StoreRolePayload = {
    code: form.code.trim(),
    name: form.name.trim(),
    level: isRoot.value ? Number(form.level ?? 0) : undefined,
    need_region: form.need_region,
    need_unit: form.need_unit,
    features: [...form.features],
    active_periode: hasTimeLimit.value ? form.active_periode : null,
  };

  try {
    await confirmDialog({
      title: role ? 'Simpan perubahan group?' : 'Simpan group baru ini?',
      description: role
        ? `Perubahan pada group "${payload.name}" akan disimpan.`
        : `Group "${payload.name}" akan ditambahkan ke daftar group.`,
      confirmLabel: 'Simpan',
      loadingLabel: 'Menyimpan...',
      onConfirm: async () => {
        if (role) {
          await RolesFacade.update(role.id, payload);
        } else {
          await RolesFacade.create(payload);
        }
        open.value = false;
        emit('submitted');
      },
      successTitle: role ? 'Group berhasil diperbarui' : 'Group berhasil disimpan',
      successDescription: role
        ? `Group "${payload.name}" sudah diperbarui.`
        : `Group "${payload.name}" sudah ditambahkan.`,
      errorTitle: 'Group gagal disimpan',
      errorDescription: (error) =>
        error instanceof Error ? error.message : 'Terjadi kesalahan sistem.',
    });
  } catch {
    // Error ditangani oleh ConfirmDialog
  }
}

async function deleteRole(): Promise<void> {
  const role = props.role;
  if (!role) return;

  try {
    await confirmDialog({
      title: 'Hapus group ini?',
      description: `Group "${role.name}" (${role.code}) akan dihapus dari daftar group aktif.`,
      confirmLabel: 'Hapus Group',
      confirmVariant: 'destructive',
      loadingLabel: 'Menghapus...',
      onConfirm: async () => {
        await RolesFacade.delete(role.id);
        open.value = false;
        emit('submitted');
      },
      successTitle: 'Group berhasil dihapus',
      successDescription: `Group "${role.name}" sudah dihapus dari daftar group aktif.`,
      errorTitle: 'Group gagal dihapus',
      errorDescription: (error) => normalizeAppError(error).message,
    });
  } catch {
    // Error sudah ditampilkan oleh ConfirmDialog.
  }
}

async function restoreRole(): Promise<void> {
  const role = props.role;
  if (!role?.deleted_at) return;

  try {
    await confirmDialog({
      title: 'Pulihkan group ini?',
      description: `Group "${role.name}" (${role.code}) akan dikembalikan ke daftar group aktif.`,
      confirmLabel: 'Pulihkan Group',
      loadingLabel: 'Memulihkan...',
      onConfirm: async () => {
        await RolesFacade.restore(role.id);
        open.value = false;
        emit('submitted');
      },
      successTitle: 'Group berhasil dipulihkan',
      successDescription: `Group "${role.name}" sudah kembali aktif.`,
      errorTitle: 'Group gagal dipulihkan',
      errorDescription: (error) => normalizeAppError(error).message,
    });
  } catch {
    // Error sudah ditampilkan oleh ConfirmDialog.
  }
}

watch(open, (isOpen) => {
  if (!isOpen) return;
  resetForm();
  void loadPermissionTree();
});
</script>

<template>
  <Modal
    v-model:open="open"
    size="xl"
    as-form
    :close-on-interact-outside="false"
    :loading="isPending"
    :hide-confirm="isDeleted"
    :confirm-text="isEdit ? 'Simpan Perubahan' : 'Simpan Group'"
    body-class="custom-form space-y-0"
    @confirm="submit"
  >
    <template #header>
      <div class="flex items-start gap-3">
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
        >
          <ShieldCheck class="size-5" />
        </div>
        <div class="flex min-w-0 flex-col gap-1">
          <DialogTitle class="font-semibold leading-snug">
            {{
              isDeleted ? 'Pulihkan Group' : isEdit ? 'Edit Group & Hak Akses' : 'Tambah Group Baru'
            }}
          </DialogTitle>
          <DialogDescription class="text-2sm">
            {{
              isDeleted
                ? 'Tinjau data group sebelum memulihkannya.'
                : isEdit
                  ? 'Perbarui identitas group dan konfigurasi hak akses aplikasi.'
                  : 'Atur identitas group pengguna dan tentukan hak akses (permissions) fitur.'
            }}
          </DialogDescription>
        </div>
      </div>
    </template>

    <Alert v-if="submitError" variant="destructive" class="mb-4">
      <CircleHelp />
      <AlertDescription>{{ submitError }}</AlertDescription>
    </Alert>

    <ElForm
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      require-asterisk-position="right"
      status-icon
    >
      <div class="flex flex-col gap-5">
        <!-- 1. Identitas Group -->
        <div class="flex flex-col gap-3">
          <div>
            <h3 class="font-semibold">Identitas Group</h3>
            <p class="text-xs text-muted-foreground">
              Tentukan kode unik dan nama identitas group pengguna.
            </p>
          </div>

          <!-- Baris Kode Group, Nama Group, & Level Group (Jika Root) -->
          <div class="grid gap-3 sm:grid-cols-12">
            <ElFormItem
              label="Kode Group"
              prop="code"
              :error="serverErrors.code"
              class="sm:col-span-4"
            >
              <div
                v-if="!isRoot && !isEdit"
                class="flex h-9 w-full items-center rounded-md border border-input bg-background px-3 text-xs shadow-2xs transition-colors focus-within:ring-2 focus-within:ring-ring focus-within:border-primary"
              >
                <span
                  v-if="userRolePrefix"
                  class="font-mono font-semibold text-muted-foreground select-none shrink-0 pr-0.5"
                >
                  {{ userRolePrefix }}
                </span>
                <input
                  :value="codeSuffix"
                  class="flex-1 bg-transparent border-0 p-0 h-full text-xs font-mono uppercase text-foreground placeholder:text-muted-foreground focus:outline-hidden focus:ring-0"
                  maxlength="100"
                  placeholder="OPERATOR"
                  @input="(e) => updateCodeFromSuffix((e.target as HTMLInputElement).value)"
                />
              </div>
              <Input
                v-else
                v-model="form.code"
                :disabled="isEdit || isDeleted || Boolean(role?.locked)"
                maxlength="100"
                placeholder="ADM_OPD"
                @input="validateField('code')"
              />
            </ElFormItem>

            <ElFormItem
              label="Nama Group"
              prop="name"
              :error="serverErrors.name"
              :class="isRoot ? 'sm:col-span-5' : 'sm:col-span-8'"
            >
              <Input
                v-model="form.name"
                :disabled="isDeleted"
                maxlength="255"
                placeholder="Administrator Utama"
                @input="validateField('name')"
              />
            </ElFormItem>

            <ElFormItem
              v-if="isRoot"
              label="Level Group"
              prop="level"
              :error="serverErrors.level"
              class="sm:col-span-3"
            >
              <Input
                v-model.number="form.level"
                type="number"
                min="0"
                max="999"
                :disabled="isDeleted"
                placeholder="0"
              />
            </ElFormItem>
          </div>
        </div>

        <!-- 2. Hak Akses Group -->
        <div class="flex flex-col gap-3">
          <div>
            <h3 class="font-semibold">Hak Akses Group</h3>
            <p class="text-xs text-muted-foreground">
              Tentukan fitur dan izin akses yang diperbolehkan bagi group ini.
            </p>
          </div>

          <PermissionTree v-model="form.features" :nodes="permissionNodes" :disabled="isDeleted" />
        </div>

        <!-- 3. Limitasi Group & Hak Akses (Paling Bawah) -->
        <div class="flex flex-col gap-3">
          <div>
            <h3 class="font-semibold">Limitasi Group & Hak Akses</h3>
            <p class="text-xs text-muted-foreground">
              Batasi group dan hak akses sesuai dengan pengaturan.
            </p>
          </div>

          <!-- limit berdasarkan wilayah (Tampil untuk ROOT atau saat edit role) -->
          <div
            v-if="isRoot || isEdit"
            class="rounded-lg border border-muted/60 bg-muted/60 p-3 space-y-3"
            :class="{ 'border-primary/10': form.need_region }"
          >
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5">
                <span class="font-semibold text-2sm text-foreground">Ruang Lingkup Wilayah</span>
                <span class="text-xs text-muted-foreground">
                  Aktifkan untuk membatasi user berdasarkan wilayah tertentu.
                </span>
              </div>

              <Switch v-model="form.need_region" :disabled="isDeleted || !isRoot" />
            </div>
          </div>

          <!-- limit berdasarkan perangkat daerah -->
          <div
            class="rounded-lg border border-muted/60 bg-muted/60 p-3 space-y-3"
            :class="{ 'border-primary/10': form.need_unit }"
          >
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5">
                <span class="font-semibold text-2sm text-foreground">
                  Ruang Lingkup Perangkat Daerah
                </span>
                <span class="text-xs text-muted-foreground">
                  Aktifkan untuk membatasi user berdasarkan perangkat daerah tertentu.
                </span>
              </div>

              <Switch v-model="form.need_unit" :disabled="isDeleted || !isRoot" />
            </div>
          </div>

          <!-- limit by periode -->
          <div
            class="rounded-lg border border-muted/60 bg-muted/60 p-3 space-y-3"
            :class="{ 'border-primary/10': hasTimeLimit }"
          >
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5">
                <span class="font-semibold text-2sm text-foreground">Periode Aktif</span>
                <span class="text-xs text-muted-foreground">
                  Aktifkan untuk membatasi dengan periode.
                </span>
              </div>

              <Switch v-model="hasTimeLimit" :disabled="isDeleted" />
            </div>

            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-100 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <ElFormItem v-if="hasTimeLimit" prop="active_periode" class="w-full">
                <DatePicker
                  v-model="form.active_periode"
                  mode="range"
                  placeholder="Pilih rentang tanggal aktif"
                  clearable
                  :disabled="isDeleted"
                  class="w-full"
                />
              </ElFormItem>
            </Transition>
          </div>
        </div>
      </div>
    </ElForm>

    <template v-if="isEdit && !isDeleted && !isLocked">
      <section class="flex flex-col gap-3 mt-3">
        <Alert variant="destructive" class="bg-destructive/5 border-dashed border-destructive/20">
          <CircleAlert />
          <div class="ml-2">
            <AlertTitle>Hapus Group</AlertTitle>
            <AlertDescription class="text-2sm leading-snug">
              Tindakan ini akan menghapus group dari daftar aktif. Group yang dihapus dapat
              dipulihkan kembali selama kodenya belum digunakan oleh group lain.
            </AlertDescription>
            <Button
              v-if="permissionStore.can(ROLE_PERMISSIONS.DELETE)"
              type="button"
              variant="destructive"
              size="sm"
              class="mt-3"
              :disabled="isPending"
              @click="deleteRole"
            >
              <Trash2 data-icon="inline-start" />
              Hapus
            </Button>
          </div>
        </Alert>
      </section>
    </template>

    <template v-else-if="isDeleted">
      <section class="mt-3 flex flex-col gap-3">
        <Alert class="bg-blue-500/5 border-dashed border-blue-200">
          <RotateCcw />
          <div class="ml-2">
            <AlertTitle>Pulihkan group</AlertTitle>
            <AlertDescription class="text-2sm leading-snug">
              Group akan dikembalikan ke daftar group aktif. Pemulihan hanya dapat dilakukan jika
              kode group belum digunakan oleh group aktif lain.
            </AlertDescription>
            <Button
              v-if="permissionStore.can(ROLE_PERMISSIONS.UPDATE)"
              type="button"
              size="sm"
              class="mt-3"
              :disabled="isPending"
              @click="restoreRole"
            >
              <RotateCcw data-icon="inline-start" />
              Pulihkan Group
            </Button>
          </div>
        </Alert>
      </section>
    </template>
  </Modal>
</template>
