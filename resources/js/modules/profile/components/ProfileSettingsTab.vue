<script setup lang="ts">
// import { ref, computed } from 'vue';
// import { useAuthStore } from '@/stores/auth';
import { useDarkMode } from '@/composables/useDarkMode';
import { useTheme, themes, type ThemeName } from '@/composables/useTheme';
// import { useConfirmDialog } from '@/composables/useConfirmDialog';
// import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
// import { Label } from '@/components/ui/label';
// import { Button } from '@/components/ui/button';
// import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
  Sun,
  Moon,
  Palette,
  Check,
  // ShieldCheck,
  // RotateCcw,
  // Loader2,
  // Sparkles,
  Settings,
} from '@lucide/vue';

// const auth = useAuthStore();
// const confirmDialog = useConfirmDialog();
const { isDark, toggleDarkMode } = useDarkMode();
const { activeTheme, setTheme } = useTheme();

// const userRoles = computed(() => auth.user?.roles ?? []);
// const rememberedGroup = computed(() => {
//   if (!auth.user) return null;
//   return (
//     localStorage.getItem(`app_default_group_id_${auth.user.userid}`) ||
//     auth.user.default_group_id ||
//     null
//   );
// });

// const isUpdatingGroup = ref(false);

// async function handleSetRememberedGroup(roleCode: string) {
//   try {
//     isUpdatingGroup.value = true;
//     await auth.setActiveGroup(roleCode, true);
//   } catch {
//     // Handled by interceptor
//   } finally {
//     isUpdatingGroup.value = false;
//   }
// }

// async function handleResetRememberedGroup() {
//   await confirmDialog({
//     title: 'Reset Group Default Login?',
//     description:
//       'Anda akan menghapus pengingat group default. Aplikasi akan meminta Anda memilih group setiap kali login jika memiliki banyak role.',
//     confirmLabel: 'Ya, Reset',
//     confirmVariant: 'destructive',
//     onConfirm: async () => {
//       await auth.resetDefaultGroup();
//     },
//   });
// }
</script>

<template>
  <div class="space-y-6 pb-6">
    <div class="flex flex-col rounded-2xl border border-border/50 bg-card p-5 space-y-4">
      <div class="flex items-center gap-3">
        <div class="p-2.5 rounded-xl bg-primary/10 text-primary shadow-xs shrink-0">
          <Settings class="size-5" />
        </div>
        <div class="flex flex-col">
          <div class="text-base font-bold">Pengaturan Pribadi</div>
          <div class="text-2sm">Pengaturan preferensi pribadi Anda</div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-y-4.5 mt-4">
        <div class="flex items-center gap-3 p-3 rounded-xl border border-border/50 bg-muted/30">
          <div
            class="p-2 rounded-lg border-2 border-card"
            :class="{
              'bg-amber-400/20 text-amber-600': !isDark,
              'bg-indigo-500/20 text-indigo-400': isDark,
            }"
          >
            <component :is="isDark ? Moon : Sun" class="size-4" />
          </div>
          <div class="flex-1">
            <p class="text-2sm font-medium text-muted-foreground">Mode Tampilan (Theme)</p>
            <p
              class="text-sm font-bold text-foreground truncate"
              :title="!isDark ? 'Mode Terang' : 'Mode Gelap'"
            >
              Mode {{ !isDark ? 'Terang' : 'Gelap' }}
            </p>
          </div>
          <Switch
            class="ml-auto cursor-pointer select-none shrink-0"
            :model-value="isDark"
            @click="toggleDarkMode($event)"
          />
        </div>

        <div class="flex items-center gap-3 p-3 rounded-xl">
          <div class="flex flex-col min-w-0">
            <p class="flex items-center gap-2 text-2sm font-medium text-muted-foreground">
              <Palette class="size-4 text-primary" />
              Warna Aksen (Accent Color)
            </p>
            <div class="flex flex-wrap gap-2 mt-4">
              <TooltipProvider v-for="t in themes" :key="t.name">
                <Tooltip>
                  <TooltipTrigger as-child>
                    <span
                      class="cursor-pointer size-7.5 rounded-full shrink-0 border-2 border-card flex items-center justify-center text-white shadow-xs"
                      :style="{ backgroundColor: t.color }"
                      @click="setTheme(t.name as ThemeName)"
                    >
                      <Check v-if="activeTheme === t.name" class="size-4.5 stroke-3" />
                    </span>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>{{ t.label }}</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. Group Aktif Diingat Untuk Login -->
    <!-- <Card class="border border-border/80 shadow-2xs bg-card hover:border-border transition-colors">
      <CardHeader class="pb-3 border-b border-border/40">
        <div class="flex items-center gap-3">
          <div
            class="p-2.5 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 shrink-0"
          >
            <ShieldCheck class="size-5" />
          </div>
          <div>
            <CardTitle class="text-base font-bold"
              >Group Login Diingat (Remembered Active Group)</CardTitle
            >
            <CardDescription class="text-xs">
              Group/Role default yang diaktifkan secara otomatis setiap kali Anda berhasil login.
            </CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent class="pt-4 space-y-4">
        <div
          class="flex flex-wrap items-center justify-between gap-3 p-4 rounded-xl bg-muted/30 border border-border/40"
        >
          <div>
            <span class="text-xs font-semibold text-muted-foreground block mb-1">
              Group Default Diingat Saat Ini:
            </span>
            <div class="flex items-center gap-2">
              <Badge
                v-if="rememberedGroup"
                variant="default"
                class="text-xs px-3.5 py-1 font-bold rounded-lg bg-primary text-primary-foreground shadow-xs"
              >
                <Sparkles class="size-3.5 mr-1.5" />
                {{ rememberedGroup }}
              </Badge>
              <span v-else class="text-xs text-muted-foreground italic">
                Belum ada group yang diingat.
              </span>
            </div>
          </div>

          <Button
            v-if="rememberedGroup"
            variant="outline"
            size="sm"
            class="rounded-xl text-destructive hover:bg-destructive/10 hover:text-destructive border-destructive/20"
            :disabled="isUpdatingGroup"
            @click="handleResetRememberedGroup"
          >
            <RotateCcw class="size-3.5 mr-1.5" />
            Reset Group Default
          </Button>
        </div>

        <Separator class="bg-border/40" />

        <div>
          <Label class="text-xs font-bold text-foreground block mb-3">
            Pilih Group Default untuk Login Berikutnya:
          </Label>

          <div
            v-if="userRoles.length > 0"
            class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3"
          >
            <button
              v-for="role in userRoles"
              :key="role"
              type="button"
              class="p-3.5 rounded-xl border text-left transition-all flex items-center justify-between"
              :class="
                rememberedGroup === role
                  ? 'border-primary bg-primary/10 ring-2 ring-primary/30 shadow-xs'
                  : 'border-border/60 bg-card hover:border-border'
              "
              :disabled="isUpdatingGroup"
              @click="handleSetRememberedGroup(role)"
            >
              <div class="flex items-center gap-2.5">
                <ShieldCheck class="size-4 text-primary shrink-0" />
                <span class="text-xs font-bold text-foreground">{{ role }}</span>
              </div>

              <Loader2
                v-if="isUpdatingGroup && rememberedGroup === role"
                class="size-4 animate-spin text-primary shrink-0"
              />
              <span
                v-else
                class="size-4 rounded-full border border-primary/40 flex items-center justify-center"
                :class="rememberedGroup === role ? 'bg-primary text-primary-foreground' : ''"
              >
                <Check v-if="rememberedGroup === role" class="size-2.5 stroke-[3]" />
              </span>
            </button>
          </div>
          <p v-else class="text-xs text-muted-foreground italic">
            Anda tidak memiliki multiple group.
          </p>
        </div>
      </CardContent>
    </Card> -->
  </div>
</template>
