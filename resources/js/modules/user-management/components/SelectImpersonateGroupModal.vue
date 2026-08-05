<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import Modal from '@/components/custom-ui/modal/Modal.vue';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { ShieldCheck } from '@lucide/vue';

interface RoleOption {
  code: string;
  name: string;
  subtitle?: string | null;
}

const props = defineProps<{
  open: boolean;
  targetName: string;
  roles: Array<
    | string
    | {
        code?: string;
        v_code?: string;
        role_code?: string;
        name?: string;
        role_name?: string;
        v_name?: string;
        subtitle?: string | null;
        unitName?: string | null;
        unit_name?: string | null;
        wilayah_name?: string | null;
      }
  >;
  loading?: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'submit', groupCode: string): void;
}>();

const selectedGroup = ref<string | null>(null);

const isOpen = computed({
  get: () => props.open,
  set: (val: boolean) => emit('update:open', val),
});

const parsedRoles = computed<RoleOption[]>(() => {
  return props.roles.map((r) => {
    if (typeof r === 'string') {
      return { code: r, name: r };
    }
    const code = r.code ?? r.v_code ?? r.role_code ?? 'UNKNOWN';
    const name = r.role_name ?? r.name ?? r.v_name ?? code;
    const subtitle = r.subtitle ?? r.unitName ?? r.unit_name ?? r.wilayah_name ?? null;
    return { code, name, subtitle };
  });
});

watch(
  () => props.open,
  (newVal) => {
    if (newVal) {
      selectedGroup.value = null;
    }
  },
);

function handleSubmit() {
  if (selectedGroup.value) {
    emit('submit', selectedGroup.value);
  }
}
</script>

<template>
  <Modal
    v-model:open="isOpen"
    title="Pilih Group"
    :description="`Pengguna ${targetName} memiliki lebih dari satu group. Pilih salah satu group untuk dibekukan selama sesi impersonate.`"
    confirm-text="Mulai Impersonate"
    cancel-text="Batal"
    :loading="loading"
    :confirm-disabled="!selectedGroup"
    :hide-confirm="false"
    as-form
    @confirm="handleSubmit"
  >
    <div class="space-y-3 py-1">
      <RadioGroup v-model="selectedGroup" class="space-y-2.5">
        <Label
          v-for="role in parsedRoles"
          :key="role.code"
          :for="`role-${role.code}`"
          class="group flex items-center justify-between rounded-2xl border p-3.5 cursor-pointer transition-all duration-150"
          :class="
            selectedGroup === role.code
              ? 'border-blue-400/80 bg-blue-50/60 dark:bg-blue-950/20 shadow-2xs'
              : 'border-border/70 bg-card hover:bg-accent/40 hover:border-border'
          "
        >
          <div class="flex items-center gap-3.5 min-w-0">
            <div
              class="flex size-10 items-center justify-center rounded-2xl transition-colors shrink-0"
              :class="
                selectedGroup === role.code
                  ? 'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400'
                  : 'bg-muted/70 text-muted-foreground group-hover:bg-muted'
              "
            >
              <ShieldCheck class="size-5" />
            </div>

            <div class="flex flex-col min-w-0">
              <span class="font-semibold text-sm text-foreground truncate">
                {{ role.name }}
              </span>
              <span v-if="role.subtitle" class="text-xs text-muted-foreground truncate mt-0.5">
                {{ role.subtitle }}
              </span>
            </div>
          </div>

          <RadioGroupItem :id="`role-${role.code}`" :value="role.code" class="sr-only" />
        </Label>
      </RadioGroup>
    </div>
  </Modal>
</template>
