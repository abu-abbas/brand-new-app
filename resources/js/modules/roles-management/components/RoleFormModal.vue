<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import { ElForm, ElFormItem, type FormInstance, type FormRules } from 'element-plus';
import { ShieldCheck, CircleHelp } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import DatePicker from '@/components/custom-ui/date-picker/DatePicker.vue';
import { Modal } from '@/components/custom-ui/modal';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import PermissionTree from './PermissionTree.vue';
import { RolesFacade, type RoleRow, type StoreRoleRequest } from '../api/roles.facade';

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

const isEdit = ref(false);
const isPending = ref(false);
const submitError = ref('');
const serverErrors = reactive<Record<string, string>>({});

// Toggle state untuk limitasi group
const hasTimeLimit = ref(false);

const form = reactive<StoreRoleRequest>({
  code: '',
  name: '',
  region: false,
  regional_device: false,
  permissions: [],
  active_date_range: { start: null, end: null },
});

// Rules validasi Element Plus Form
const rules: FormRules<StoreRoleRequest> = {
  code: [
    { required: true, message: 'Kode Group wajib diisi.', trigger: 'blur' },
    { min: 3, max: 50, message: 'Kode Group antara 3-50 karakter.', trigger: 'blur' },
  ],
  name: [
    { required: true, message: 'Nama Group wajib diisi.', trigger: 'blur' },
    { min: 3, max: 100, message: 'Nama Group antara 3-100 karakter.', trigger: 'blur' },
  ],
};

function resetForm(): void {
  const role = props.role;
  isEdit.value = Boolean(role);
  hasTimeLimit.value = Boolean(role?.active_date_range?.start || role?.active_date_range?.end);
  Object.assign(form, {
    code: role?.code ?? '',
    name: role?.name ?? '',
    region: Boolean(role?.region),
    regional_device: Boolean(role?.regional_device),
    permissions: role?.permissions ? [...role.permissions] : [],
    active_date_range: role?.active_date_range
      ? { ...role.active_date_range }
      : { start: null, end: null },
  });
  submitError.value = '';
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);
  formRef.value?.clearValidate();
}

watch(
  () => form.region,
  (enabled) => {
    if (enabled) {
      form.regional_device = false;
    }
  },
);

watch(
  () => form.regional_device,
  (enabled) => {
    if (enabled) {
      form.region = false;
    }
  },
);

watch(hasTimeLimit, (enabled) => {
  if (!enabled && form.active_date_range) {
    form.active_date_range.start = null;
    form.active_date_range.end = null;
  }
});

function validateField(field: keyof StoreRoleRequest): void {
  delete serverErrors[field];
  void formRef.value?.validateField(field).catch(() => undefined);
}

async function submit(): Promise<void> {
  submitError.value = '';

  const isValid = await formRef.value?.validate().catch(() => false);
  if (!isValid) return;

  const role = props.role;
  const payload: StoreRoleRequest = {
    code: form.code.trim(),
    name: form.name.trim(),
    region: form.region,
    regional_device: form.regional_device,
    permissions: [...form.permissions],
    active_date_range: hasTimeLimit.value ? form.active_date_range : undefined,
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
          await RolesFacade.update(role.code, payload);
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
    // Error sudah ditangani oleh ConfirmDialog
  }
}

watch(open, (isOpen) => {
  if (!isOpen) return;
  resetForm();
});
</script>

<template>
  <Modal
    v-model:open="open"
    size="xl"
    as-form
    :close-on-interact-outside="false"
    :loading="isPending"
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
            {{ isEdit ? 'Edit Group & Hak Akses' : 'Tambah Group Baru' }}
          </DialogTitle>
          <DialogDescription class="text-2sm">
            {{
              isEdit
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

          <!-- Baris Kode Group (4/12) & Nama Group (8/12) -->
          <div class="grid gap-3 sm:grid-cols-12">
            <ElFormItem
              label="Kode Group"
              prop="code"
              :error="serverErrors.code"
              class="sm:col-span-4"
            >
              <Input
                v-model="form.code"
                :disabled="isEdit"
                maxlength="50"
                placeholder="adm_sys"
                @input="validateField('code')"
              />
            </ElFormItem>

            <ElFormItem
              label="Nama Group"
              prop="name"
              :error="serverErrors.name"
              class="sm:col-span-8"
            >
              <Input
                v-model="form.name"
                maxlength="100"
                placeholder="Administrator Utama"
                @input="validateField('name')"
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

          <PermissionTree v-model="form.permissions" />
        </div>

        <!-- 3. Limitasi Group & Hak Akses (Paling Bawah) -->
        <div class="flex flex-col gap-3">
          <div>
            <h3 class="font-semibold">Limitasi Group & Hak Akses</h3>
            <p class="text-xs text-muted-foreground">
              Batasi group dan hak akses sesuai dengan pengaturan.
            </p>
          </div>

          <!-- limit berdasarkan wilayah -->
          <div
            class="rounded-lg border border-muted/60 bg-muted/60 p-3 space-y-3"
            :class="{ 'border-primary/10': form.region }"
          >
            <div class="flex items-center justify-between">
              <div class="flex flex-col gap-0.5">
                <span class="font-semibold text-2sm text-foreground">Ruang Lingkup Wilayah</span>
                <span class="text-xs text-muted-foreground">
                  Aktifkan untuk membatasi user berdasarkan wilayah tertentu.
                </span>
              </div>

              <Switch v-model="form.region" />
            </div>
          </div>

          <!-- limit berdasarkan perangkat daerah -->
          <div
            class="rounded-lg border border-muted/60 bg-muted/60 p-3 space-y-3"
            :class="{ 'border-primary/10': form.regional_device }"
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

              <Switch v-model="form.regional_device" />
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

              <Switch v-model="hasTimeLimit" />
            </div>

            <Transition
              enter-active-class="transition-opacity duration-150 ease-out"
              enter-from-class="opacity-0"
              enter-to-class="opacity-100"
              leave-active-class="transition-opacity duration-100 ease-in"
              leave-from-class="opacity-100"
              leave-to-class="opacity-0"
            >
              <ElFormItem v-if="hasTimeLimit" prop="active_date_range" class="w-full">
                <DatePicker
                  v-model="form.active_date_range"
                  mode="range"
                  placeholder="Pilih rentang tanggal aktif"
                  clearable
                  class="w-full"
                />
              </ElFormItem>
            </Transition>
          </div>
        </div>
      </div>
    </ElForm>
  </Modal>
</template>
