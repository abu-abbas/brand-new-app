<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useAuthStore } from '@/stores/auth';
import { Clock, LogOut, UserCheck } from '@lucide/vue';

const authStore = useAuthStore();
const confirmDialog = useConfirmDialog();

const isImpersonating = computed(() => authStore.isImpersonating);
const targetName = computed(() => authStore.user?.name ?? authStore.user?.userid ?? 'Target');
const activeGroupName = computed(() => {
  if (authStore.user?.impersonated_active_group_name) {
    return authStore.user.impersonated_active_group_name;
  }

  const groupCode = authStore.impersonatedActiveGroup ?? authStore.activeGroup;
  if (!groupCode) return '-';

  if (authStore.user?.user_roles && authStore.user.user_roles.length > 0) {
    const match = authStore.user.user_roles.find((ur) => ur.role_code === groupCode);
    if (match?.role_name) {
      return match.role_name;
    }
  }
  return groupCode;
});

const activeUnitName = computed(() => {
  if (authStore.user?.impersonated_active_group_unit_name) {
    return authStore.user.impersonated_active_group_unit_name;
  }

  const groupCode = authStore.impersonatedActiveGroup ?? authStore.activeGroup;
  if (!groupCode || !authStore.user?.user_roles) return null;

  const match = authStore.user.user_roles.find((ur) => ur.role_code === groupCode);
  return match?.unit_name ?? null;
});

const formattedExpiresAt = computed(() => {
  const expiresIso = authStore.impersonateExpiresAt;
  if (!expiresIso) return '-';
  try {
    const date = new Date(expiresIso);
    const datePart = new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
    }).format(date);
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${datePart}, ${hours}:${minutes}:${seconds}`;
  } catch {
    return expiresIso;
  }
});

async function handleLeave() {
  const confirmed = await confirmDialog({
    title: 'Kembali ke Akun Admin?',
    description: `Sesi impersonate untuk ${targetName.value} akan dihentikan dan hak akses akun Admin Anda akan dipulihkan.`,
    confirmLabel: 'Ya, Kembali ke Admin',
    cancelLabel: 'Batal',
    confirmVariant: 'default',
    successTitle: 'Sesi Berhasil Dipulihkan',
    successDescription: 'Identitas Admin Anda telah dipulihkan.',
    onConfirm: async () => {
      await authStore.leaveImpersonate();
    },
  });

  if (confirmed) {
    const returnUrl = sessionStorage.getItem('impersonate_return_url');
    sessionStorage.removeItem('impersonate_return_url');
    if (returnUrl) {
      window.location.href = returnUrl;
    } else {
      window.location.reload();
    }
  }
}
</script>

<template>
  <div
    v-if="isImpersonating"
    class="shrink-0 z-50 flex flex-wrap items-center justify-between gap-3 bg-amber-600 px-4 py-1.5 text-amber-50 shadow-md dark:bg-amber-700"
    role="region"
    aria-label="Impersonation Banner"
  >
    <div class="flex flex-wrap items-center gap-4 text-xs font-medium sm:text-sm">
      <div class="flex items-center gap-1.5 font-semibold">
        <UserCheck class="h-4 w-4 shrink-0 text-amber-200" />
        <span>
          Bertindak sebagai: <strong>{{ targetName }}</strong>
        </span>
      </div>

      <div class="hidden sm:inline-block text-amber-200/60">|</div>

      <div>
        <span class="text-amber-200/90">Group Aktif:</span>
        <span class="ml-1 font-semibold">{{ activeGroupName }}</span>
        <span v-if="activeUnitName" class="ml-1 font-normal text-amber-200/90"
          >({{ activeUnitName }})</span
        >
      </div>

      <div class="hidden sm:inline-block text-amber-200/60">|</div>

      <div class="flex items-center gap-1">
        <Clock class="h-3.5 w-3.5 shrink-0 text-amber-200" />
        <span>Berakhir: {{ formattedExpiresAt }}</span>
      </div>
    </div>

    <Button
      variant="secondary"
      size="sm"
      class="h-7 cursor-pointer gap-1.5 bg-amber-950/40 text-amber-100 hover:bg-amber-950/70 hover:text-white border border-amber-400/30"
      @click="handleLeave"
    >
      <LogOut class="size-3.5" />
      <span class="text-2sm leading-snug tracking-tight"
        >Kembali ke Akun {{ authStore.user?.impersonator?.name }}</span
      >
    </Button>
  </div>
</template>
