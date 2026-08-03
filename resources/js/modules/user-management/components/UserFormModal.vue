<script setup lang="ts">
import Modal from '@/components/custom-ui/modal/Modal.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useUserScope, type PerangkatDaerahOption } from '@/composables/useUserScope';
import { usePermissionStore } from '@/stores/permission';
import { Trash2, User as UserIcon } from '@lucide/vue';
import { ElForm, ElFormItem, type FormInstance, type FormRules } from 'element-plus';
import { computed, reactive, ref, watch } from 'vue';
import { useCreateUserMutation } from '../mutations/useCreateUserMutation';
import { useDeleteUserMutation } from '../mutations/useDeleteUserMutation';
import { useUpdateUserMutation } from '../mutations/useUpdateUserMutation';
import { USER_PERMISSIONS } from '../permissions';
import { usePerangkatDaerahListQuery } from '../queries/usePerangkatDaerahListQuery';
import { useUserDetailQuery } from '../queries/useUserDetailQuery';

const props = defineProps<{
  open: boolean;
  userId?: string | null;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'saved'): void;
}>();

const permission = usePermissionStore();
const confirmDialog = useConfirmDialog();
const { isNeedUnit, currentUserUnitCode, filterPdList } = useUserScope();
const formRef = ref<FormInstance>();

const targetId = computed(() => props.userId ?? null);
const isEdit = computed(() => !!props.userId);

const { data: userDetailRes, isLoading: isFetchingDetail } = useUserDetailQuery(targetId);
const { data: pdRes } = usePerangkatDaerahListQuery();

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

const form = reactive<{
  userid: string;
  username: string;
  email: string;
  unit_code?: string;
  is_active: boolean;
  is_external: boolean;
}>({
  userid: '',
  username: '',
  email: '',
  unit_code: undefined,
  is_active: true,
  is_external: false,
});

const isExternalUser = computed(
  () => form.is_external || Boolean(userDetailRes.value?.data?.is_external),
);
const canDeleteUser = computed(() => permission.can(USER_PERMISSIONS.DELETE));

const serverErrors = reactive<Record<string, string>>({});

const rules: FormRules = {
  userid: [
    { required: true, message: 'User ID wajib diisi.', trigger: 'blur' },
    { min: 3, max: 100, message: 'User ID antara 3-100 karakter.', trigger: 'blur' },
  ],
  username: [
    { required: true, message: 'Nama Pengguna wajib diisi.', trigger: 'blur' },
    { min: 2, max: 255, message: 'Nama Pengguna antara 2-255 karakter.', trigger: 'blur' },
  ],
  email: [
    { required: true, message: 'Email wajib diisi.', trigger: 'blur' },
    { type: 'email', message: 'Format email tidak valid.', trigger: ['blur', 'change'] },
  ],
};

const resetForm = () => {
  form.userid = '';
  form.username = '';
  form.email = '';
  form.unit_code = isNeedUnit.value ? currentUserUnitCode.value : undefined;
  form.is_active = true;
  form.is_external = false;
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);
  formRef.value?.resetFields();
};

const syncFormDataFromDetail = () => {
  const u = userDetailRes.value?.data;
  if (!u || !props.userId) return;

  form.userid = u.userid || '';
  form.username = u.username || u.name || '';
  form.email = u.email || '';
  form.unit_code = u.unit?.code || undefined;
  form.is_active = u.is_active ?? true;
  form.is_external = Boolean(u.is_external);
};

watch(
  () => props.open,
  (val) => {
    if (val) {
      if (!props.userId) {
        resetForm();
      } else if (userDetailRes.value) {
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

const createUserMutation = useCreateUserMutation();
const updateUserMutation = useUpdateUserMutation();
const deleteUserMutation = useDeleteUserMutation();

const isSubmitting = computed(
  () =>
    createUserMutation.isPending.value ||
    updateUserMutation.isPending.value ||
    deleteUserMutation.isPending.value,
);

const handleSubmit = async () => {
  Object.keys(serverErrors).forEach((key) => delete serverErrors[key]);

  if (formRef.value) {
    const valid = await formRef.value.validate().catch(() => false);
    if (!valid) return;
  }

  try {
    if (isEdit.value && props.userId) {
      await updateUserMutation.mutateAsync({
        id: props.userId,
        data: {
          username: form.username,
          email: form.email || undefined,
          unit_code: form.unit_code || undefined,
          is_active: form.is_active,
          is_external: form.is_external,
        },
      });
    } else {
      await createUserMutation.mutateAsync({
        userid: form.userid,
        username: form.username,
        email: form.email || undefined,
        unit_code: form.unit_code || undefined,
        is_active: form.is_active,
        is_external: false,
      });
    }

    emit('update:open', false);
    emit('saved');
  } catch (err: unknown) {
    const errorObj = err as { response?: { data?: { errors?: Record<string, unknown> } } };
    if (errorObj?.response?.data?.errors) {
      const sErrors = errorObj.response.data.errors;
      Object.keys(sErrors).forEach((key) => {
        const fieldError = sErrors[key];
        if (Array.isArray(fieldError) && (fieldError[0] as { message?: string })?.message) {
          serverErrors[key] = (fieldError[0] as { message: string }).message;
        } else if (typeof fieldError === 'string') {
          serverErrors[key] = fieldError;
        }
      });
    }
  }
};

const handleDeleteUser = async () => {
  if (!props.userId) return;

  await confirmDialog({
    title: 'Hapus pengguna ini?',
    description: `Pengguna "${form.username}" (${form.userid}) akan dihapus dari daftar pengguna aktif (soft delete).`,
    confirmLabel: 'Hapus Pengguna',
    confirmVariant: 'destructive',
    loadingLabel: 'Menghapus...',
    onConfirm: async () => {
      await deleteUserMutation.mutateAsync(props.userId as string);
      emit('update:open', false);
      emit('saved');
    },
  });
};
</script>

<template>
  <Modal
    :open="open"
    size="lg"
    as-form
    :close-on-interact-outside="false"
    :loading="isSubmitting || isFetchingDetail"
    body-class="custom-form space-y-0"
    @update:open="(val) => emit('update:open', val)"
    @confirm="handleSubmit"
  >
    <template #header>
      <div class="flex items-start gap-3">
        <div
          class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
        >
          <UserIcon class="size-5" />
        </div>
        <div class="flex min-w-0 flex-col gap-0.5">
          <DialogTitle class="font-semibold leading-snug">
            {{ isEdit ? 'Edit Informasi Pengguna' : 'Tambah Pengguna Baru' }}
          </DialogTitle>
          <DialogDescription class="text-2sm">
            {{
              isEdit
                ? 'Ubah data profil pengguna dan status keaktifan akun.'
                : 'Buat akun pengguna baru dalam sistem.'
            }}
          </DialogDescription>
        </div>
      </div>
    </template>

    <ElForm
      ref="formRef"
      :model="form"
      :rules="rules"
      label-position="top"
      require-asterisk-position="right"
      status-icon
    >
      <div class="flex flex-col gap-4.5">
        <div class="grid grid-cols-3 gap-4">
          <ElFormItem label="User ID" prop="userid" :error="serverErrors.userid" class="mb-0!">
            <Input
              id="userid"
              v-model="form.userid"
              placeholder="Contoh: 19920101..."
              :disabled="isEdit"
            />
          </ElFormItem>

          <div class="col-span-2">
            <ElFormItem label="Email" prop="email" :error="serverErrors.email" class="mb-0!">
              <Input
                id="email"
                v-model="form.email"
                type="email"
                placeholder="user@jakarta.go.id"
              />
            </ElFormItem>
          </div>
        </div>

        <ElFormItem
          label="Nama Pengguna"
          prop="username"
          :error="serverErrors.username"
          class="mb-0!"
        >
          <Input id="username" v-model="form.username" placeholder="Nama lengkap..." />
        </ElFormItem>
        <ElFormItem
          label="Perangkat Daerah"
          prop="unit_code"
          :error="serverErrors.unit_code"
          class="mb-0!"
        >
          <Select v-model="form.unit_code">
            <SelectTrigger id="unit_code" class="w-full h-9 text-xs">
              <SelectValue placeholder="Pilih Perangkat Daerah" class="truncate" />
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

        <div class="flex items-center justify-between p-3.5 rounded-lg border bg-muted/30">
          <div class="space-y-0.5">
            <Label class="text-sm font-medium">Status Aktif Akun</Label>
            <p class="text-xs text-muted-foreground">
              Pengguna nonaktif tidak dapat melakukan login ke sistem
            </p>
          </div>
          <Switch v-model="form.is_active" />
        </div>

        <!-- Alert Zona Hapus Pengguna (Paling Bawah Modal pada Mode Edit - Khusus Pengguna Lokal) -->
        <div v-if="isEdit && canDeleteUser && !isExternalUser">
          <Alert
            variant="destructive"
            class="bg-destructive/5 border-dashed border-destructive/30 p-4"
          >
            <Trash2 class="w-5 h-5 text-destructive" />
            <div class="ml-2 space-y-2">
              <AlertTitle class="text-sm font-semibold">Hapus Pengguna</AlertTitle>
              <AlertDescription class="text-xs text-muted-foreground leading-relaxed">
                Arsip pengguna "{{ form.username }}" ({{ form.userid }}). Data akan dipindahkan ke
                arsip terhapus.
              </AlertDescription>

              <div class="pt-1">
                <Button
                  type="button"
                  variant="destructive"
                  size="sm"
                  :disabled="isSubmitting"
                  @click="handleDeleteUser"
                >
                  <Trash2 class="w-3.5 h-3.5 mr-1.5" />
                  Hapus Pengguna
                </Button>
              </div>
            </div>
          </Alert>
        </div>
      </div>
    </ElForm>
  </Modal>
</template>
