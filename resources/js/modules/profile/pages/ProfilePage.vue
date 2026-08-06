<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import AdminLayout from '@/components/AdminLayout.vue';
import { useAuthStore } from '@/stores/auth';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import ProfileActivityLogTab from '../components/ProfileActivityLogTab.vue';
import ProfileSettingsTab from '../components/ProfileSettingsTab.vue';
import {
  User,
  Users,
  Shield,
  KeyRound,
  Mail,
  Building2,
  CheckCircle2,
  Sparkles,
} from '@lucide/vue';

const auth = useAuthStore();

const user = computed(() => auth.user);

const initials = computed(() => {
  if (!user.value?.name) return 'U';
  return user.value.name
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase();
});

const activeRoleName = computed(() => {
  const activeCode = user.value?.active_group_id;
  if (!activeCode) return '-';
  const userRoles = user.value?.user_roles;
  if (userRoles && userRoles.length > 0) {
    const found = userRoles.find((ur) => ur.role_code === activeCode);
    if (found?.role_name) return found.role_name;
  }
  return activeCode;
});
</script>

<template>
  <AdminLayout
    title="Profil Saya"
    subtitle="Kelola informasi akun diri, riwayat log aktivitas, dan preferensi pengaturan pribadi Anda."
    :icon="User"
  >
    <div class="space-y-6 mx-auto">
      <!-- 1. Header Banner Profil (Bersih tanpa gradasi) -->
      <div class="rounded-2xl border border-border/50 bg-card p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <!-- Avatar Ring -->
            <div class="relative shrink-0">
              <div
                class="size-14 md:size-18 rounded-xl bg-primary text-primary-foreground font-bold text-2xl md:text-3xl flex items-center justify-center ring-4 ring-background"
              >
                {{ initials }}
              </div>
              <span
                class="absolute -bottom-1 -right-1 size-5 rounded-full bg-emerald-500 ring-4 ring-background flex items-center justify-center"
                title="Akun Aktif"
              >
                <CheckCircle2 class="size-3 text-white" />
              </span>
            </div>

            <!-- Profile Info Summary -->
            <div class="space-y-1.5 min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-bold tracking-tight text-foreground truncate">
                  {{ user?.name || '-' }}
                </h2>
                <Badge
                  :variant="user?.is_active ? 'default' : 'destructive'"
                  class="rounded-full px-3 py-0.5 text-xs font-semibold"
                >
                  {{ user?.is_active ? 'Aktif' : 'Non-aktif' }}
                </Badge>
              </div>

              <div
                class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground"
              >
                <span class="flex items-center gap-1.5">
                  <User class="size-4 text-muted-foreground shrink-0" />
                  <strong class="font-medium text-foreground">
                    {{ user?.userid || '-' }}
                  </strong>
                </span>
                <span class="hidden sm:inline text-border">•</span>
                <span class="flex items-center gap-1.5 truncate">
                  <Mail class="size-4 text-muted-foreground shrink-0" />
                  <span class="truncate tracking-tight">{{ user?.email || '-' }}</span>
                </span>
                <span class="hidden sm:inline text-border">•</span>
                <span class="flex items-center gap-1.5">
                  <Sparkles class="size-4 shrink-0" />
                  <span class="font-semibold tracking-tight">Root Admin</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex items-center gap-2 self-stretch sm:self-auto justify-end">
            <Button variant="outline" size="sm" class="rounded-lg font-medium gap-2" as-child>
              <RouterLink :to="{ name: 'auth.change-password' }">
                <KeyRound class="size-3.5 text-muted-foreground" />
                <span class="text-2sm tracking-tight">Ubah Password</span>
              </RouterLink>
            </Button>
          </div>
        </div>

        <!-- Metric Cards Row (Clean tanpa gradasi) -->
        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4.5">
          <div class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30">
            <div class="p-2 rounded-lg bg-primary/10 text-foreground border-2 border-card">
              <Shield class="size-4" />
            </div>
            <div class="min-w-0">
              <p class="text-2sm font-medium text-muted-foreground">Group Sesi Ini</p>
              <p class="text-sm font-bold text-foreground truncate" :title="activeRoleName">
                {{ activeRoleName }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30">
            <div class="p-2 rounded-lg bg-primary/10 text-foreground border-2 border-card">
              <Users class="size-4" />
            </div>
            <div class="min-w-0">
              <p class="text-2sm font-medium text-muted-foreground">Jumlah Group</p>
              <p class="text-sm font-bold text-foreground">
                {{ user?.user_roles?.filter((ur) => !ur.is_expired)?.length || 0 }} Group
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30">
            <div class="p-2 rounded-lg bg-primary/10 text-foreground border-2 border-card">
              <Building2 class="size-4" />
            </div>
            <div class="min-w-0">
              <p class="text-2sm font-medium text-muted-foreground">Kode Unit</p>
              <p class="text-sm font-bold text-foreground font-mono truncate">
                {{ user?.unit?.code || '-' }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30">
            <div class="p-2 rounded-lg bg-primary/10 text-foreground border-2 border-card">
              <CheckCircle2 class="size-4" />
            </div>
            <div class="min-w-0">
              <p class="text-2sm font-medium text-muted-foreground">Verifikasi Email</p>
              <p class="text-sm font-bold text-foreground">
                {{ user?.is_verified ? 'Terverifikasi' : 'Belum' }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-3 gap-4.5">
        <div class="col-span-1">
          <ProfileSettingsTab />
        </div>
        <div class="col-span-2">
          <ProfileActivityLogTab />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
