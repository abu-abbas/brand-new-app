<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import {
  Shield,
  Lock,
  Eye,
  EyeOff,
  CheckCircle2,
  AlertCircle,
  ArrowRight,
  ArrowLeft,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import StrongPasswordChecklist from '../components/StrongPasswordChecklist.vue';
import { AuthFacade } from '../api/auth.facade';
import type { AppError } from '@/lib/axios';

const route = useRoute();
const router = useRouter();

const email = computed(() => (typeof route.query.email === 'string' ? route.query.email : ''));
const token = computed(() => (typeof route.query.token === 'string' ? route.query.token : ''));

const password = ref('');
const passwordConfirmation = ref('');
const showPassword = ref(false);
const showConfirmation = ref(false);

const error = ref('');
const successMessage = ref('');
const isLoading = ref(false);

const isFormValid = computed(() => {
  const pwd = password.value;
  const isStrong =
    pwd.length >= 8 &&
    pwd.length <= 255 &&
    /[A-Z]/.test(pwd) &&
    /[a-z]/.test(pwd) &&
    /[0-9]/.test(pwd) &&
    /[!@#$%^&*()_+\-.]/.test(pwd);

  return isStrong && pwd === passwordConfirmation.value && passwordConfirmation.value.length > 0;
});

const handleSubmit = async () => {
  error.value = '';
  successMessage.value = '';

  if (!email.value || !token.value) {
    error.value = 'Tautan reset password tidak valid atau parameter tidak lengkap.';
    return;
  }

  if (password.value !== passwordConfirmation.value) {
    error.value = 'Konfirmasi password tidak cocok dengan password baru.';
    return;
  }

  isLoading.value = true;

  try {
    const res = await AuthFacade.resetPassword({
      email: email.value,
      token: token.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
    });
    successMessage.value =
      res.message || 'Password berhasil diperbarui. Silakan login menggunakan password baru Anda.';
    window.setTimeout(() => {
      void router.push('/login');
    }, 2500);
  } catch (err) {
    const appErr = err as AppError;
    error.value = appErr.message || 'Gagal mereset password. Pastikan token belum kedaluwarsa.';
  } finally {
    isLoading.value = false;
  }
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
      <div class="flex items-center gap-2.5 mb-6">
        <div
          class="h-9 w-9 rounded-lg bg-primary text-primary-foreground flex items-center justify-center shadow-sm"
        >
          <Shield class="h-5 w-5 fill-current stroke-none" />
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-foreground">Atur Password Baru</h1>
          <p class="text-xs text-muted-foreground">Lengkapi kata sandi akun Anda</p>
        </div>
      </div>

      <!-- Invalid Link State -->
      <div
        v-if="!email || !token"
        class="p-4 rounded-xl bg-destructive/10 border border-destructive/30 text-destructive text-xs space-y-3"
      >
        <div class="flex items-start gap-2 font-medium">
          <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
          <span>Tautan tidak valid atau parameter token/email tidak ditemukan di URL.</span>
        </div>
        <p class="text-muted-foreground text-[11px]">
          Silakan ajukan kembali pengiriman tautan dari halaman Lupa Password.
        </p>
        <RouterLink
          to="/forgot-password"
          class="inline-flex items-center gap-1 text-primary font-semibold hover:underline"
        >
          <span>Ke Halaman Lupa Password</span>
          <ArrowRight class="h-3 w-3" />
        </RouterLink>
      </div>

      <!-- Success State -->
      <div
        v-else-if="successMessage"
        class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs space-y-3"
      >
        <div class="flex items-start gap-2 font-medium">
          <CheckCircle2 class="h-4 w-4 shrink-0 mt-0.5 text-emerald-600 dark:text-emerald-400" />
          <span>{{ successMessage }}</span>
        </div>
        <p class="text-muted-foreground text-[11px]">
          Anda akan dialihkan ke halaman login secara otomatis...
        </p>
        <RouterLink
          to="/login"
          class="inline-flex items-center gap-1 text-primary font-semibold hover:underline"
        >
          <span>Login Sekarang</span>
          <ArrowRight class="h-3 w-3" />
        </RouterLink>
      </div>

      <!-- Form Reset Password -->
      <form v-else class="space-y-4" novalidate @submit.prevent="handleSubmit">
        <!-- Error Alert -->
        <div
          v-if="error"
          class="p-3 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-xs flex items-start gap-2"
        >
          <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
          <span>{{ error }}</span>
        </div>

        <!-- Password Field -->
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

        <!-- Confirmation Field -->
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
          <span>{{ isLoading ? 'Memproses...' : 'Simpan Password Baru' }}</span>
          <ArrowRight v-if="!isLoading" class="h-4 w-4" />
        </Button>
      </form>

      <!-- Footer Link -->
      <div
        v-if="email && token && !successMessage"
        class="mt-6 pt-4 border-t border-border/50 text-center"
      >
        <RouterLink
          to="/login"
          class="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors font-medium"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          <span>Kembali ke Halaman Login</span>
        </RouterLink>
      </div>
    </div>
  </div>
</template>
