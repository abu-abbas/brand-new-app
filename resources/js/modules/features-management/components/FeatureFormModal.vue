<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { ElForm, ElFormItem, type FormInstance, type FormRules } from 'element-plus';
import { CircleAlert, CircleHelp, Link2, ListTree, RotateCcw, Trash2 } from '@lucide/vue';
import type {
  FeatureOptionResource,
  FeatureResource,
  StoreFeatureRequest,
  StoreFeatureRequestType,
} from '@/api/generated/api';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import Combobox from '@/components/custom-ui/combobox/Combobox.vue';
import { IconPicker } from '@/components/custom-ui/icon-picker';
import { Modal } from '@/components/custom-ui/modal';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { normalizeAppError } from '@/lib/axios';
import { cn } from '@/lib/utils';
import router from '@/router';
import { usePermissionStore } from '@/stores/permission';
import { FeaturesFacade } from '../api/features.facade';
import { FEATURE_PERMISSIONS } from '../permissions';
import {
  useCreateFeatureMutation,
  useDeleteFeatureMutation,
  useRestoreFeatureMutation,
  useUpdateFeatureMutation,
} from '../mutations/use-feature-mutations';
import { toKebabCase } from './feature-form.utils';

const permissionStore = usePermissionStore();

type FeatureForm = {
  name: string;
  alias: string;
  type: StoreFeatureRequestType;
  parent: string;
  route: string;
  icon: string;
  order: number;
  show_on_sidebar: boolean;
  is_restricted: boolean;
  description: string;
};

const props = defineProps<{ feature?: FeatureResource | null }>();
const open = defineModel<boolean>('open', { default: false });
const formRef = ref<FormInstance>();
const aliasEdited = ref(false);
const loadingOptions = ref(false);
const parentOptions = ref<FeatureOptionResource[]>([]);
const serverErrors = reactive<Record<string, string>>({});
const submitError = ref('');
const createMutation = useCreateFeatureMutation();
const updateMutation = useUpdateFeatureMutation();
const deleteMutation = useDeleteFeatureMutation();
const restoreMutation = useRestoreFeatureMutation();
const confirmDialog = useConfirmDialog();
let hydratingForm = false;

const isEdit = computed(() => Boolean(props.feature));
const isDeleted = computed(() => Boolean(props.feature?.deleted_at));
const isPending = computed(
  () =>
    createMutation.isPending.value ||
    updateMutation.isPending.value ||
    deleteMutation.isPending.value ||
    restoreMutation.isPending.value,
);

const typeOptions = [
  { label: 'Menu', description: 'Navigasi & halaman', value: 'menu' },
  { label: 'CRUD', description: 'Aksi pengelolaan', value: 'crud' },
  { label: 'Filter', description: 'Pembatas data', value: 'filter' },
] satisfies Array<{
  label: string;
  description: string;
  value: StoreFeatureRequestType;
}>;

const form = reactive<FeatureForm>({
  name: '',
  alias: '',
  type: 'menu',
  parent: '',
  route: '',
  icon: '',
  order: 1,
  show_on_sidebar: true,
  is_restricted: false,
  description: '',
});

const availableParents = computed(() =>
  parentOptions.value.filter(
    (feature) =>
      feature.alias !== props.feature?.alias && (form.type !== 'menu' || feature.type === 'menu'),
  ),
);
const parentComboboxOptions = computed(() =>
  availableParents.value.map((feature) => ({
    value: feature.alias,
    label: feature.name,
    alias: feature.alias,
    type: feature.type,
  })),
);
const routeComboboxOptions = router
  .getRoutes()
  .flatMap((route) =>
    typeof route.name === 'string'
      ? [{ value: route.name, label: route.name, path: route.path }]
      : [],
  )
  .sort((first, second) => first.label.localeCompare(second.label));
const selectedParent = computed<unknown>({
  get: () => form.parent || null,
  set: (value) => {
    form.parent = typeof value === 'string' ? value : '';
    validateField('parent');
  },
});
const selectedRoute = computed<unknown>({
  get: () => form.route || null,
  set: (value) => {
    form.route = typeof value === 'string' ? value : '';
    validateField('route');
  },
});

const rules: FormRules<FeatureForm> = {
  name: [
    { required: true, message: 'Nama fitur wajib diisi.', trigger: 'blur' },
    { max: 100, message: 'Nama fitur maksimal 100 karakter.', trigger: 'blur' },
  ],
  alias: [
    { required: true, message: 'Alias wajib diisi.', trigger: 'blur' },
    { max: 150, message: 'Alias maksimal 150 karakter.', trigger: 'blur' },
    {
      pattern: /^[a-z0-9]+(?:-[a-z0-9]+)*$/,
      message: 'Gunakan huruf kecil, angka, dan tanda hubung.',
      trigger: 'blur',
    },
  ],
  type: [{ required: true, message: 'Tipe fitur wajib dipilih.', trigger: 'change' }],
  parent: [
    {
      validator: (_rule, value: string, callback) => {
        const parent = parentOptions.value.find((option) => option.alias === value);
        if (form.type === 'menu' && parent && parent.type !== 'menu') {
          callback(new Error('Menu hanya dapat menjadi child dari menu.'));
        } else {
          callback();
        }
      },
      trigger: 'change',
    },
  ],
  route: [
    { max: 250, message: 'Route maksimal 250 karakter.', trigger: 'change' },
    {
      validator: (_rule, value: string, callback) => {
        if (value && !routeComboboxOptions.some((route) => route.value === value)) {
          callback(new Error('Pilih route name yang terdaftar.'));
        } else {
          callback();
        }
      },
      trigger: 'change',
    },
  ],
  icon: [{ max: 50, message: 'Icon maksimal 50 karakter.', trigger: 'change' }],
  order: [
    { type: 'integer', message: 'Urutan harus berupa angka.', trigger: 'change' },
    {
      type: 'integer',
      min: 1,
      max: 32767,
      message: 'Urutan harus antara 1–32767.',
      trigger: 'change',
    },
  ],
  show_on_sidebar: [
    { type: 'boolean', message: 'Pilihan sidebar tidak valid.', trigger: 'change' },
  ],
  is_restricted: [
    { type: 'boolean', message: 'Pilihan restricted tidak valid.', trigger: 'change' },
  ],
  description: [{ max: 100, message: 'Deskripsi maksimal 100 karakter.', trigger: 'blur' }],
};

function resetForm(): void {
  const feature = props.feature;
  hydratingForm = true;
  aliasEdited.value = Boolean(feature);
  Object.assign(form, {
    name: feature?.name ?? '',
    alias: feature?.alias ?? '',
    type:
      feature?.type === 'crud' || feature?.type === 'filter' || feature?.type === 'menu'
        ? feature.type
        : 'menu',
    parent: feature?.parent ?? '',
    route: feature?.route ?? '',
    icon: feature?.icon ?? '',
    order: feature?.order ?? 1,
    show_on_sidebar: feature?.show_on_sidebar ?? true,
    is_restricted: (feature as unknown as { is_restricted?: boolean })?.is_restricted ?? false,
    description: feature?.description ?? '',
  });
  hydratingForm = false;
  submitError.value = '';
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);
  formRef.value?.clearValidate();
}

async function loadParentOptions(): Promise<void> {
  loadingOptions.value = true;
  try {
    parentOptions.value = (await FeaturesFacade.options()).data;
  } catch (error) {
    submitError.value = normalizeAppError(error).message;
  } finally {
    loadingOptions.value = false;
  }
}

function validationMessage(value: unknown): string {
  if (!Array.isArray(value) || !value.length) return '';
  const first = value[0];
  return typeof first === 'string'
    ? first
    : typeof first === 'object' && first !== null && 'message' in first
      ? String(first.message)
      : '';
}

function validateField(field: keyof FeatureForm): void {
  delete serverErrors[field];
  void formRef.value?.validateField(field).catch(() => undefined);
}

function selectType(value: unknown): void {
  if (value === 'menu' || value === 'crud' || value === 'filter') {
    form.type = value;
    validateField('type');
  }
}

function updateOrder(value: string | number): void {
  form.order = Number(value);
}

async function submit(): Promise<void> {
  if (isDeleted.value) return;

  submitError.value = '';
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);

  if (!formRef.value || !(await formRef.value.validate().catch(() => false))) return;

  const payload: StoreFeatureRequest = {
    name: form.name.trim(),
    alias: form.alias.trim(),
    type: form.type,
    parent: form.parent || null,
    route: form.type === 'menu' ? form.route.trim() || null : null,
    icon: form.type === 'menu' ? form.icon || null : null,
    order: form.order,
    show_on_sidebar: form.type === 'menu' ? form.show_on_sidebar : false,
    is_restricted: form.is_restricted,
    description: form.description.trim() || null,
  };

  try {
    const feature = props.feature;
    await confirmDialog({
      title: feature ? 'Simpan perubahan fitur?' : 'Simpan fitur ini?',
      description: feature
        ? `Perubahan pada fitur “${payload.name}” akan disimpan.`
        : `Fitur “${payload.name}” akan ditambahkan ke daftar fitur.`,
      confirmLabel: 'Simpan',
      loadingLabel: 'Menyimpan...',
      onConfirm: async () => {
        try {
          if (feature) {
            await updateMutation.mutateAsync({ alias: feature.alias, data: payload });
          } else {
            await createMutation.mutateAsync(payload);
          }
          open.value = false;
        } catch (error) {
          const normalized = normalizeAppError(error);
          const errors = normalized.validationErrors ?? {};
          Object.entries(errors).forEach(([field, value]) => {
            serverErrors[field] = validationMessage(value);
          });
          submitError.value = Object.keys(serverErrors).length ? '' : normalized.message;
          throw error;
        }
      },
      successTitle: feature ? 'Perubahan berhasil disimpan' : 'Fitur berhasil disimpan',
      successDescription: feature
        ? `Fitur “${payload.name}” sudah diperbarui.`
        : `Fitur “${payload.name}” sudah ditambahkan.`,
      errorTitle: 'Fitur gagal disimpan',
      errorDescription: (error) => normalizeAppError(error).message,
    });
  } catch {
    // Error sudah ditampilkan oleh ConfirmDialog dan dipetakan ke field terkait.
  }
}

async function deleteFeature(): Promise<void> {
  const feature = props.feature;
  if (!feature) return;

  try {
    await confirmDialog({
      title: 'Hapus fitur ini?',
      description: `Fitur “${feature.name}” akan dipindahkan ke data terhapus.`,
      confirmLabel: 'Hapus Fitur',
      confirmVariant: 'destructive',
      loadingLabel: 'Menghapus...',
      onConfirm: async () => {
        await deleteMutation.mutateAsync(feature.alias);
        open.value = false;
      },
      successTitle: 'Fitur berhasil dihapus',
      successDescription: `Fitur “${feature.name}” sudah dipindahkan ke data terhapus.`,
      errorTitle: 'Fitur gagal dihapus',
      errorDescription: (error) => normalizeAppError(error).message,
    });
  } catch {
    // Error sudah ditampilkan oleh ConfirmDialog.
  }
}

async function restoreFeature(): Promise<void> {
  const feature = props.feature;
  if (!feature?.deleted_at) return;

  try {
    await confirmDialog({
      title: 'Pulihkan fitur ini?',
      description: `Fitur “${feature.name}” akan dikembalikan ke daftar fitur aktif.`,
      confirmLabel: 'Pulihkan Fitur',
      loadingLabel: 'Memulihkan...',
      onConfirm: async () => {
        await restoreMutation.mutateAsync(feature.alias);
        open.value = false;
      },
      successTitle: 'Fitur berhasil dipulihkan',
      successDescription: `Fitur “${feature.name}” sudah kembali aktif.`,
      errorTitle: 'Fitur gagal dipulihkan',
      errorDescription: (error) => normalizeAppError(error).message,
    });
  } catch {
    // Error sudah ditampilkan oleh ConfirmDialog.
  }
}

watch(
  () => form.name,
  (name) => {
    if (!isEdit.value && !aliasEdited.value) form.alias = toKebabCase(name);
    delete serverErrors.name;
  },
);

watch(
  () => form.type,
  () => {
    if (hydratingForm) return;
    if (!availableParents.value.some((option) => option.alias === form.parent)) form.parent = '';
    if (form.type !== 'menu') {
      form.route = '';
      form.icon = '';
    }
    form.show_on_sidebar = form.type === 'menu';
    formRef.value?.clearValidate(['parent', 'route', 'icon']);
  },
  { flush: 'sync' },
);

watch(open, (isOpen) => {
  if (!isOpen) return;
  resetForm();
  void loadParentOptions();
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
    :confirm-text="isEdit ? 'Simpan Perubahan' : 'Simpan Fitur'"
    body-class="custom-form space-y-0"
    @confirm="submit"
  >
    <template #header>
      <div class="flex items-start gap-3">
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
        >
          <ListTree class="size-5" />
        </div>
        <div class="flex min-w-0 flex-col gap-1">
          <DialogTitle class="font-semibold leading-snug">
            {{ isDeleted ? 'Pulihkan Fitur' : isEdit ? 'Edit Fitur' : 'Tambah Fitur' }}
          </DialogTitle>
          <DialogDescription class="text-2sm">
            {{
              isDeleted
                ? 'Tinjau data fitur sebelum memulihkannya.'
                : isEdit
                  ? 'Perbarui identitas, struktur, dan tampilan fitur.'
                  : 'Atur identitas, struktur, dan tampilan fitur baru.'
            }}
          </DialogDescription>
        </div>
      </div>
    </template>

    <Alert v-if="submitError" variant="destructive" class="mb-5">
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
      <section class="flex flex-col gap-4 mb-5">
        <div>
          <h3 class="font-semibold">Identitas fitur</h3>
          <p class="text-xs text-muted-foreground">Nama publik dan alias unik untuk sistem.</p>
        </div>

        <div class="grid gap-x-4 md:grid-cols-2">
          <ElFormItem label="Nama fitur" prop="name" :error="serverErrors.name">
            <Input
              v-model="form.name"
              :disabled="isDeleted"
              maxlength="100"
              placeholder="Contoh: Manajemen Pengguna"
              :aria-invalid="Boolean(serverErrors.name)"
              @blur="validateField('name')"
            />
          </ElFormItem>
          <ElFormItem label="Alias" prop="alias" :error="serverErrors.alias">
            <div class="relative w-full">
              <Link2
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
              />
              <Input
                v-model="form.alias"
                class="pl-9"
                :disabled="isEdit"
                maxlength="150"
                placeholder="manajemen-pengguna"
                :aria-invalid="Boolean(serverErrors.alias)"
                @update:model-value="aliasEdited = true"
                @blur="validateField('alias')"
              />
            </div>
          </ElFormItem>
        </div>

        <ElFormItem label="Deskripsi" prop="description" :error="serverErrors.description">
          <div class="w-full relative">
            <Textarea
              v-model="form.description"
              :disabled="isDeleted"
              class="min-h-20 resize-none"
              maxlength="100"
              placeholder="Jelaskan fungsi fitur secara singkat (opsional)."
              :aria-invalid="Boolean(serverErrors.description)"
              @blur="validateField('description')"
            />
            <p class="absolute bottom-1.5 right-2 text-xs text-muted-foreground">
              {{ form.description.length }}/100
            </p>
          </div>
        </ElFormItem>
      </section>

      <section class="flex flex-col gap-4">
        <div>
          <h3 class="font-semibold">Struktur fitur</h3>
          <p class="text-xs text-muted-foreground">Tentukan tipe, induk, dan posisi tampil.</p>
        </div>

        <ElFormItem label="Tipe" prop="type" :error="serverErrors.type">
          <RadioGroup
            :model-value="form.type"
            :disabled="isDeleted"
            class="grid gap-2 sm:grid-cols-3"
            @update:model-value="selectType"
          >
            <label
              v-for="option in typeOptions"
              :key="option.value"
              :for="`feature-type-${option.value}`"
              :class="
                cn(
                  'flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-colors',
                  form.type === option.value
                    ? 'border-primary bg-primary/5'
                    : 'border-border hover:bg-accent',
                )
              "
            >
              <RadioGroupItem
                :id="`feature-type-${option.value}`"
                :value="option.value"
                class="mt-0.5"
                :aria-invalid="Boolean(serverErrors.type)"
              />
              <span class="min-w-0">
                <span class="block text-sm font-medium">{{ option.label }}</span>
                <span class="block truncate text-xs text-muted-foreground">
                  {{ option.description }}
                </span>
              </span>
            </label>
          </RadioGroup>
        </ElFormItem>

        <div class="grid gap-x-4 md:grid-cols-[minmax(0,1fr)_140px]">
          <ElFormItem label="Fitur induk" prop="parent" :error="serverErrors.parent">
            <div class="w-full">
              <Combobox
                v-model="selectedParent"
                :disabled="isDeleted"
                :options="parentComboboxOptions"
                :loading="loadingOptions"
                clear-all
                placeholder="Tanpa fitur induk"
                search-placeholder="Cari fitur induk..."
              >
                <template #option="{ item }">
                  <span class="flex min-w-0 flex-1 items-center justify-between gap-4">
                    <span class="truncate">{{ item.label }}</span>
                    <span class="shrink-0 text-xs text-muted-foreground">
                      {{ item.alias }} · {{ item.type }}
                    </span>
                  </span>
                </template>
              </Combobox>
            </div>
          </ElFormItem>
          <ElFormItem label="Urutan" prop="order" :error="serverErrors.order">
            <Input
              type="number"
              :model-value="form.order"
              :disabled="isDeleted"
              min="1"
              max="32767"
              :aria-invalid="Boolean(serverErrors.order)"
              @update:model-value="updateOrder"
              @blur="validateField('order')"
            />
          </ElFormItem>
        </div>
      </section>

      <template v-if="form.type === 'menu'">
        <section class="flex flex-col gap-4 pt-5">
          <div>
            <h3 class="font-semibold">Navigasi menu</h3>
            <p class="text-xs text-muted-foreground">
              Route dan icon hanya digunakan oleh fitur bertipe menu.
            </p>
          </div>

          <div class="grid gap-x-4 md:grid-cols-2">
            <ElFormItem label="Route" prop="route" :error="serverErrors.route">
              <div class="w-full">
                <Combobox
                  v-model="selectedRoute"
                  :disabled="isDeleted"
                  :options="routeComboboxOptions"
                  clear-all
                  placeholder="Pilih route name"
                  search-placeholder="Cari route name..."
                >
                  <template #option="{ item }">
                    <span class="flex min-w-0 flex-1 flex-col">
                      <span class="truncate">{{ item.label }}</span>
                      <span class="truncate text-2xs text-muted-foreground">{{ item.path }}</span>
                    </span>
                  </template>
                </Combobox>
              </div>
            </ElFormItem>
            <ElFormItem label="Icon" prop="icon" :error="serverErrors.icon">
              <IconPicker
                v-model="form.icon"
                :disabled="isDeleted"
                class="w-full"
                placeholder="Pilih icon menu"
                @change="validateField('icon')"
              />
            </ElFormItem>
          </div>

          <ElFormItem prop="show_on_sidebar" :error="serverErrors.show_on_sidebar" class="mb-0">
            <div
              class="flex w-full items-center justify-between gap-4 rounded-lg border border-muted/85 bg-muted/85 p-3"
              :class="{ 'border-primary/10': form.show_on_sidebar }"
            >
              <div class="flex min-w-0 flex-col gap-1">
                <Label for="show-on-sidebar">Tampilkan pada sidebar</Label>
                <p class="text-xs text-muted-foreground">
                  Menu dapat diakses melalui navigasi utama aplikasi.
                </p>
              </div>
              <Switch
                id="show-on-sidebar"
                v-model="form.show_on_sidebar"
                :disabled="isDeleted"
                :aria-invalid="Boolean(serverErrors.show_on_sidebar)"
                @update:model-value="validateField('show_on_sidebar')"
              />
            </div>
          </ElFormItem>
        </section>
      </template>

      <ElFormItem prop="is_restricted" :error="serverErrors.is_restricted" class="mb-0 mt-4">
        <div
          class="flex w-full items-center justify-between gap-4 rounded-lg border border-amber-500/30 bg-amber-500/5 p-3"
          :class="{ 'border-amber-500/60 bg-amber-500/15': form.is_restricted }"
        >
          <div class="flex min-w-0 flex-col gap-1">
            <Label for="is-restricted" class="font-semibold text-foreground"
              >Fitur Khusus Admin (Restricted)</Label
            >
            <p class="text-xs text-muted-foreground">
              Aktifkan untuk menyembunyikan fitur sensitif/admin dari pohon hak akses pengelola
              group biasa.
            </p>
          </div>
          <Switch
            id="is-restricted"
            v-model="form.is_restricted"
            :disabled="isDeleted"
            :aria-invalid="Boolean(serverErrors.is_restricted)"
            @update:model-value="validateField('is_restricted')"
          />
        </div>
      </ElFormItem>
    </ElForm>

    <template v-if="isEdit && !isDeleted">
      <section class="flex flex-col gap-3 mt-3">
        <Alert variant="destructive" class="bg-destructive/5 border-dashed border-destructive/20">
          <CircleAlert />
          <div class="ml-2">
            <AlertTitle>Hapus Fitur</AlertTitle>
            <AlertDescription class="text-2sm leading-snug">
              Tindakan ini memengaruhi status fitur. Fitur akan disembunyikan dari data aktif dan
              tetap tersimpan sebagai data terhapus.
            </AlertDescription>
            <Button
              v-if="permissionStore.can(FEATURE_PERMISSIONS.DELETE)"
              type="button"
              variant="destructive"
              size="sm"
              class="mt-3"
              :disabled="isPending"
              @click="deleteFeature"
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
            <AlertTitle>Pulihkan fitur</AlertTitle>
            <AlertDescription class="text-2sm leading-snug">
              Fitur akan dikembalikan ke data aktif. Pemulihan hanya dapat dilakukan jika alias
              belum digunakan fitur aktif lain.
            </AlertDescription>
            <Button
              v-if="permissionStore.can(FEATURE_PERMISSIONS.UPDATE)"
              type="button"
              size="sm"
              class="mt-3"
              :disabled="isPending"
              @click="restoreFeature"
            >
              <RotateCcw data-icon="inline-start" />
              Pulihkan Fitur
            </Button>
          </div>
        </Alert>
      </section>
    </template>
  </Modal>
</template>

<style>
.feature-form {
  --el-color-danger: var(--destructive);
}

.feature-form .el-form-item {
  margin-bottom: 1.25rem;
}

.feature-form .el-form-item__label {
  height: auto;
  margin-bottom: 0.45rem;
  color: var(--foreground);
  font-size: 0.8125rem;
  font-weight: 600;
  line-height: 1.25rem;
}

.feature-form .el-form-item__error {
  position: static;
  padding-top: 0.35rem;
  color: var(--destructive);
  font-size: 0.75rem;
}
</style>
