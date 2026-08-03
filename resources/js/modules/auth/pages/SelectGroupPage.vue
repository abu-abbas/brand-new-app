<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { useAuthStore } from '@/stores/auth';
import { usePerangkatDaerahListQuery } from '@/modules/user-management/queries/usePerangkatDaerahListQuery';
import { useWilayahListQuery } from '@/modules/user-management/queries/useWilayahListQuery';
import { ArrowRight, CheckCheck, ShieldCheck, Users, LucideInfo } from '@lucide/vue';
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();
const route = useRoute();

const selectedGroupId = ref<string>(auth.user?.active_group_id || auth.user?.roles[0] || '');
const rememberPreference = ref<boolean>(false);
const isLoading = ref<boolean>(false);
const { data: wilayahResponse, isLoading: isLoadingWilayah } = useWilayahListQuery();
const { data: unitResponse, isLoading: isLoadingUnit } = usePerangkatDaerahListQuery();

const wilayahNames = computed(
  () => new Map((wilayahResponse.value?.data ?? []).map((item) => [item.code, item.name])),
);
const unitNames = computed(
  () => new Map((unitResponse.value?.data ?? []).map((item) => [item.code, item.name])),
);
const isLoadingReferences = computed(() => isLoadingWilayah.value || isLoadingUnit.value);

function splitCodes(value?: string | null): string[] {
  return (
    value
      ?.split(',')
      .map((code) => code.trim())
      .filter(Boolean) ?? []
  );
}

function resolveNames(codes: string[], lookup: Map<string, string>): string | null {
  return codes.length > 0 ? codes.map((code) => lookup.get(code) ?? code).join(', ') : null;
}

const availableRoles = computed(() => {
  if (!auth.user) return [];
  const userRolesMap = new Map<
    string,
    { code: string; name: string; wilayahCodes: Set<string>; unitCodes: Set<string> }
  >();

  if (auth.user.user_roles && auth.user.user_roles.length > 0) {
    auth.user.user_roles.forEach((ur) => {
      const group = userRolesMap.get(ur.role_code) ?? {
        code: ur.role_code,
        name: ur.role_name || ur.role_code,
        wilayahCodes: new Set<string>(),
        unitCodes: new Set<string>(),
      };
      splitCodes(ur.wilayah).forEach((code) => group.wilayahCodes.add(code));
      if (ur.unit) group.unitCodes.add(ur.unit);
      userRolesMap.set(ur.role_code, group);
    });
  } else if (auth.user.roles) {
    auth.user.roles.forEach((code) => {
      userRolesMap.set(code, {
        code,
        name: code,
        wilayahCodes: new Set<string>(),
        unitCodes: new Set<string>(),
      });
    });
  }

  return Array.from(userRolesMap.values()).map((group) => {
    const wilayahCodes = Array.from(group.wilayahCodes);
    const unitCodes = Array.from(group.unitCodes);

    return {
      code: group.code,
      name: group.name,
      wilayahLabel: resolveNames(wilayahCodes, wilayahNames.value),
      unitLabel: resolveNames(unitCodes, unitNames.value),
    };
  });
});

async function handleConfirmGroup() {
  if (!selectedGroupId.value) return;

  try {
    isLoading.value = true;
    await auth.setActiveGroup(selectedGroupId.value, rememberPreference.value);

    if (route.query.redirect && typeof route.query.redirect === 'string') {
      router.push(route.query.redirect);
    } else {
      router.push({ name: 'home' });
    }
  } catch {
    // Tanpa console statement sesuai konvensi CI/CD
  } finally {
    isLoading.value = false;
  }
}
</script>

<template>
  <div
    class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 lg:p-8 bg-muted/60 dark:bg-muted/30 font-sans text-foreground relative overflow-hidden select-none"
  >
    <!-- Background Glow Decoration -->
    <div
      class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-primary/10 rounded-full blur-3xl pointer-events-none"
    />

    <!-- Main Container Card -->
    <div
      class="w-full max-w-md rounded-2xl border border-border/60 bg-background/95 backdrop-blur-xl shadow-xl p-6 sm:p-8 relative z-10 transition-all duration-300 animate-in fade-in zoom-in-95"
    >
      <!-- Header Section -->
      <div class="text-center mb-5">
        <div
          class="mx-auto mb-3.5 flex h-13 w-13 items-center justify-center rounded-2xl bg-primary/10 text-primary shadow-inner ring-4 ring-primary/5"
        >
          <Users class="h-6 w-6" />
        </div>
        <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-foreground">
          Pilih Group
        </h1>
        <p class="text-xs text-muted-foreground mt-1.5 leading-relaxed">
          Akun Anda terdaftar di beberapa group. Silakan pilih group yang ingin digunakan untuk sesi
          ini.
        </p>
      </div>

      <!-- Content & Group Options List -->
      <div class="space-y-4">
        <div class="grid gap-3">
          <div
            v-for="group in availableRoles"
            :key="group.code"
            class="group relative flex cursor-pointer items-center justify-between rounded-lg border p-3.5 transition-all duration-200"
            :class="[
              selectedGroupId === group.code
                ? 'border-primary/50 bg-primary/10 ring-0 ring-primary/20'
                : 'border-border/50 bg-border/20 hover:bg-primary/5 hover:border-primary/10',
            ]"
            @click="selectedGroupId = group.code"
          >
            <div class="flex min-w-0 items-center space-x-3">
              <div
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-colors"
                :class="[
                  selectedGroupId === group.code
                    ? 'bg-primary/20 text-primary/80 shadow-xs'
                    : 'bg-border/80 text-muted-foreground group-hover:bg-border',
                ]"
              >
                <ShieldCheck class="size-5" />
              </div>
              <div class="min-w-0">
                <p class="text-sm font-semibold leading-tight text-foreground truncate">
                  {{ group.name }}
                </p>
                <p
                  v-if="group.wilayahLabel"
                  class="mt-1 text-[11px] leading-snug text-muted-foreground"
                >
                  {{ isLoadingReferences ? 'Memuat...' : group.wilayahLabel }}
                </p>
                <p
                  v-if="group.unitLabel"
                  class="mt-1 text-[11px] leading-snug text-muted-foreground"
                >
                  {{ isLoadingReferences ? 'Memuat...' : group.unitLabel }}
                </p>
              </div>
            </div>

            <div class="flex items-center space-x-2 shrink-0 ml-2">
              <Badge
                v-if="auth.user?.default_group_id === group.code"
                variant="secondary"
                class="text-[10px] font-normal px-2 py-0.5 bg-primary/10 text-primary border-0"
              >
                Default
              </Badge>
              <CheckCheck
                v-if="selectedGroupId === group.code"
                class="size-5 text-primary/65 animate-in zoom-in-50 duration-200"
              />
            </div>
          </div>
        </div>

        <!-- Remember Preference Switch (Toggle) -->
        <div
          class="flex items-center justify-between gap-4 bg-muted/60 border border-muted/80 p-3 rounded-xl transition-colors"
          :class="{
            'border-primary/20 bg-primary/5': rememberPreference,
          }"
        >
          <LucideInfo class="size-5 self-baseline text-primary" />
          <div class="space-y-0.5 select-none">
            <Label
              for="remember-group"
              class="text-xs font-semibold text-foreground cursor-pointer"
            >
              Ingat Pilihan Saya
            </Label>
            <p class="text-[11px] text-muted-foreground leading-snug">
              Simpan group ini sebagai pilihan utama saat Anda login berikutnya.
            </p>
          </div>
          <Switch
            id="remember-group"
            class="cursor-pointer select-none shrink-0"
            :model-value="rememberPreference"
            @update:model-value="(val) => (rememberPreference = Boolean(val))"
          />
        </div>

        <!-- Confirm Submit Button -->
        <div class="pt-3">
          <Button
            class="w-full h-10.5 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-sm tracking-wide shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer active:scale-[0.98] rounded-xl group"
            :disabled="!selectedGroupId || isLoading"
            @click="handleConfirmGroup"
          >
            <span>{{ isLoading ? 'Memproses...' : 'Lanjutkan ke Beranda' }}</span>
            <ArrowRight
              v-if="!isLoading"
              class="h-4 w-4 transition-transform group-hover:translate-x-1"
            />
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
