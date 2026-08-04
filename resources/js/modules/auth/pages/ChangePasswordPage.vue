<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  Shield,
  Lock,
  Eye,
  EyeOff,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  LogOut,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import StrongPasswordChecklist from '../components/StrongPasswordChecklist.vue';
import { AuthFacade } from '../api/auth.facade';
import { useAuthStore } from '@/stores/auth';
import type { AppError } from '@/lib/axios';

const authStore = useAuthStore();
const router = useRouter();

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');

const showCurrentPassword = ref(false);
const showPassword = ref(false);
const showConfirmation = ref(false);

const error = ref('');
const successMessage = ref('');
const isLoading = ref(false);

const isExpired = computed(() => Boolean(authStore.user?.must_change_password));

const isFormValid = computed(() => {
  const pwd = password.value;
  const isStrong =
    pwd.length >= 8 &&
    pwd.length <= 255 &&
    /[A-Z]/.test(pwd) &&
    /[a-z]/.test(pwd) &&
    /[0-9]/.test(pwd) &&
    /[!@#$%^&*()_+\-.]/.test(pwd);

  return currentPassword.value.length > 0 && isStrong && pwd === passwordConfirmation.value;
});

const handleSubmit = async () => {
  error.value = '';
  successMessage.value = '';

  if (!currentPassword.value) {
    error.value = 'Password saat ini wajib diisi.';
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Konfirmasi password tidak cocok dengan password baru.';
    return;
  }

  isLoading.value = true;

  try {
    const res = await AuthFacade.changePassword({
      current_password: currentPassword.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    successMessage.value =
      res.message || 'Password berhasil diubah. Seluruh sesi Anda telah diakhiri.';
    window.setTimeout(async () => {
      await authStore.logout();
      window.location.assign('/login');
    }, 2000);
  } catch (err) {
    const appErr = err as AppError;
    error.value = appErr.message || 'Gagal mengubah password.';
  } finally {
    isLoading.value = false;
  }
};

const handleLogout = async () => {
  await authStore.logout();
  await router.replace('/login');
};
</script>

<template>
  <div
    class="min-h-screen w-screen flex items-center justify-center bg-muted/20 p-4 font-sans text-foreground"
  >
    <div
      class="w-full max-w-md bg-background border border-border/60 rounded-2xl p-6 sm:p-8 shadow-xl"
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-2.5">
          <div
            class="h-9 w-9 rounded-lg bg-primary text-primary-foreground flex items-center justify-center shadow-sm"
          >
            <Shield class="h-5 w-5 fill-current stroke-none" />
          </div>
          <div>
            <h1 class="text-xl font-bold tracking-tight text-foreground">Ubah Password</h1>
            <p class="text-xs text-muted-foreground">Pembaruan keamanan akun</p>
          </div>
        </div>

        <Button
          variant="ghost"
          size="sm"
          class="text-xs text-muted-foreground hover:text-foreground gap-1.5"
          @click="handleLogout"
        >
          <LogOut class="h-3.5 w-3.5" />
          <span>Keluar</span>
        </Button>
      </div>

      <!-- Expired Warning Banner -->
      <div
        v-if="isExpired"
        class="mb-4 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 text-xs flex items-start gap-2.5"
      >
        <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
        <div class="leading-relaxed">
          <span class="font-semibold block mb-0.5">Password Anda Telah Kedaluwarsa!</span>
          Sesuai kebijakan keamanan, password wajib diperbarui setiap 3 bulan sekali. Silakan
          perbarui password Anda untuk dapat melanjutkan.
        </div>
      </div>

      <!-- Success Alert -->
      <div
        v-if="successMessage"
        class="mb-4 p-3 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs flex items-start gap-2"
      >
        <CheckCircle2 class="h-4 w-4 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-400" />
        <span>{{ successMessage }}</span>
      </div>

      <!-- Error Alert -->
      <div
        v-if="error"
        class="mb-4 p-3 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-xs flex items-start gap-2"
      >
        <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
        <span>{{ error }}</span>
      </div>

      <!-- Form -->
      <form v-if="!successMessage" class="space-y-4" novalidate @submit.prevent="handleSubmit">
        <!-- Current Password -->
        <div class="space-y-1">
          <label class="text-xs font-semibold text-muted-foreground block">Password Saat Ini</label>
          <div class="relative flex items-center">
            <Lock class="absolute left-3 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="currentPassword"
              :type="showCurrentPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="current-password"
              class="pl-9 pr-9 h-10 bg-muted/30 border-input text-xs"
              @input="error = ''"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showCurrentPassword = !showCurrentPassword"
            >
              <Eye v-if="!showCurrentPassword" class="h-4 w-4" />
              <EyeOff v-else class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- New Password -->
        <div class="space-y-1">
          <label class="text-xs font-semibold text-muted-foreground block">Password Baru</label>
          <div class="relative flex items-center">
            <Lock class="absolute left-3 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="new-password"
              class="pl-9 pr-9 h-10 bg-muted/30 border-input text-xs"
              @input="error = ''"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showPassword = !showPassword"
            >
              <Eye v-if="!showPassword" class="h-4 w-4" />
              <EyeOff v-else class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Confirm New Password -->
        <div class="space-y-1">
          <label class="text-xs font-semibold text-muted-foreground block"
            >Konfirmasi Password Baru</label
          >
          <div class="relative flex items-center">
            <Lock class="absolute left-3 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="passwordConfirmation"
              :type="showConfirmation ? 'text' : 'password'"
              placeholder="••••••••"
              autocomplete="new-password"
              class="pl-9 pr-9 h-10 bg-muted/30 border-input text-xs"
              @input="error = ''"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showConfirmation = !showConfirmation"
            >
              <Eye v-if="!showConfirmation" class="h-4 w-4" />
              <EyeOff v-else class="h-4 w-4" />
            </button>
          </div>
          <p
            v-if="passwordConfirmation && password !== passwordConfirmation"
            class="text-[11px] font-medium text-destructive mt-1"
          >
            Konfirmasi password tidak cocok.
          </p>
        </div>

        <!-- Strong Password Checklist -->
        <StrongPasswordChecklist :password="password" />

        <Button
          type="submit"
          :disabled="isLoading || !isFormValid"
          class="w-full h-10 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-2sm tracking-wide shadow-md flex items-center justify-center gap-2 cursor-pointer mt-2"
        >
          <span>{{ isLoading ? 'Memproses...' : 'Simpan Perubahan Password' }}</span>
          <ArrowRight v-if="!isLoading" class="h-4 w-4" />
        </Button>
      </form>
    </div>
  </div>
</template>
