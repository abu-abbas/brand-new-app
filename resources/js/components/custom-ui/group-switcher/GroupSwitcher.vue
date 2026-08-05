<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useAuthStore } from '@/stores/auth';
import {
  Check,
  ChevronDown,
  LayoutGrid,
  LogOut,
  RotateCcw,
  ShieldCheck,
  CheckCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const confirmDialog = useConfirmDialog();
const router = useRouter();

const isSwitching = ref(false);

const availableRoles = computed(() => {
  if (!auth.user) return [];
  const userRolesMap = new Map<string, { code: string; name: string; unit?: string | null }>();

  if (auth.user.user_roles && auth.user.user_roles.length > 0) {
    auth.user.user_roles.forEach((ur) => {
      userRolesMap.set(ur.role_code, {
        code: ur.role_code,
        name: ur.role_name || ur.role_code,
        unit: ur.unit,
      });
    });
  } else if (auth.user.roles) {
    auth.user.roles.forEach((code) => {
      userRolesMap.set(code, {
        code,
        name: code,
      });
    });
  }

  return Array.from(userRolesMap.values());
});

const activeRoleName = computed(() => {
  const activeCode = auth.activeGroup;
  if (!activeCode) return 'Pilih Group';
  const roleObj = availableRoles.value.find((r) => r.code === activeCode);
  return roleObj ? roleObj.name : activeCode;
});

const hasDefaultGroup = computed(() => Boolean(auth.user?.default_group_id));

async function handleSelectGroup(roleCode: string) {
  if (roleCode === auth.activeGroup || isSwitching.value) return;

  try {
    isSwitching.value = true;
    await auth.setActiveGroup(roleCode, false);
    window.location.reload();
  } catch {
    // Sesuai konvensi, hindari console statement agar CI/CD lulus
  } finally {
    isSwitching.value = false;
  }
}

function handleResetDefaultGroup() {
  confirmDialog({
    title: 'Reset Preferensi Group Utama',
    description:
      'Apakah Anda yakin ingin mereset preferensi Group utama? Anda akan diminta memilih Group Kerja kembali pada setiap kali login.',
    confirmLabel: 'Reset Preferensi',
    cancelLabel: 'Batal',
    onConfirm: async () => {
      await auth.resetDefaultGroup();
    },
  });
}

function handleOpenSelectGroupPage() {
  void router.push({ name: 'auth.select-group', query: { switch: 'true' } });
}

function handleLogout() {
  confirmDialog({
    title: 'Keluar dari Aplikasi',
    description: 'Apakah Anda yakin ingin keluar dari akun ini?',
    confirmLabel: 'Keluar',
    cancelLabel: 'Batal',
    confirmVariant: 'destructive',
    onConfirm: async () => {
      await auth.logout();
      window.location.assign('/login');
    },
  });
}
</script>

<template>
  <div v-if="availableRoles.length > 0 && !auth.isImpersonating" class="flex items-center">
    <DropdownMenu>
      <DropdownMenuTrigger as-child>
        <Button
          variant="outline"
          size="sm"
          class="h-8 gap-2 border-border/60 bg-background/50 hover:bg-accent text-xs font-medium cursor-pointer"
          :disabled="isSwitching"
        >
          <CheckCheck class="h-3.5 w-3.5 text-primary" />
          <span class="max-w-30 truncate sm:max-w-45">{{ activeRoleName }}</span>
          <ChevronDown class="h-3 w-3 text-muted-foreground" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="w-56">
        <DropdownMenuLabel class="text-xs font-normal text-muted-foreground">
          Group Kerja Aktif
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          v-for="role in availableRoles"
          :key="role.code"
          class="flex items-center justify-between cursor-pointer text-xs py-2"
          :class="{ 'bg-accent/60 font-medium': role.code === auth.activeGroup }"
          @select="handleSelectGroup(role.code)"
        >
          <div class="flex items-center space-x-2 truncate">
            <ShieldCheck
              class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
              :class="{ 'text-primary/85': role.code === auth.activeGroup }"
            />
            <span class="truncate">{{ role.name }}</span>
          </div>
          <Check
            v-if="role.code === auth.activeGroup"
            class="h-3.5 w-3.5 text-primary shrink-0 ml-2"
          />
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        <DropdownMenuItem
          class="flex items-center space-x-2 cursor-pointer text-xs py-2"
          @select="handleOpenSelectGroupPage"
        >
          <LayoutGrid class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
          <span>Halaman Pilih Group...</span>
        </DropdownMenuItem>

        <DropdownMenuItem
          v-if="hasDefaultGroup"
          class="flex items-center space-x-2 cursor-pointer text-xs py-2 text-amber-600 dark:text-amber-400"
          @select="handleResetDefaultGroup"
        >
          <RotateCcw class="h-3.5 w-3.5 shrink-0" />
          <span>Reset Preferensi Group</span>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        <DropdownMenuItem
          class="flex items-center space-x-2 cursor-pointer text-2sm py-2 text-destructive/85"
          @select="handleLogout"
        >
          <LogOut class="h-3.5 w-3.5 shrink-0" />
          <span>Keluar</span>
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </div>
</template>
