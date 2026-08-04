<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import { Mail, ArrowRight, ArrowLeft, AlertCircle, Info } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AuthCard from '../components/AuthCard.vue';
import { AuthFacade } from '../api/auth.facade';
import type { AppError } from '@/lib/axios';

const email = ref('');
const sentEmail = ref('');
const error = ref('');
const isSent = ref(false);
const isLoading = ref(false);
const countdown = ref(0);
let timer: number | null = null;

function startCountdown(seconds = 60) {
  countdown.value = seconds;
  if (timer !== null) window.clearInterval(timer);
  timer = window.setInterval(() => {
    countdown.value -= 1;
    if (countdown.value <= 0) {
      if (timer !== null) window.clearInterval(timer);
      timer = null;
    }
  }, 1000) as unknown as number;
}

onUnmounted(() => {
  if (timer !== null) window.clearInterval(timer);
});

const handleSubmit = async () => {
  error.value = '';

  if (!email.value.trim()) {
    error.value = 'Email wajib diisi.';
    return;
  }

  isLoading.value = true;

  try {
    await AuthFacade.forgotPassword({ email: email.value.trim() });
    sentEmail.value = email.value.trim();
    isSent.value = true;
    startCountdown(60);
  } catch (err) {
    const appErr = err as AppError;
    error.value = appErr.message || 'Terjadi kesalahan saat memproses permintaan.';
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <AuthCard title="Lupa Password" subtitle="Pemulihan akses akun internal">
    <!-- SUCCESS SCREEN -->
    <div v-if="isSent" class="text-center py-2 space-y-4">
      <div
        class="h-14 w-14 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto shadow-sm"
      >
        <Mail class="h-7 w-7" />
      </div>

      <div class="space-y-1.5">
        <h2 class="text-xl font-bold tracking-tight text-foreground">Tautan Berhasil Dikirim</h2>
        <p class="text-xs text-muted-foreground leading-relaxed px-2">
          Jika email
          <strong class="text-foreground font-semibold">{{ sentEmail }}</strong> terdaftar sebagai
          akun internal, kami telah mengirimkan tautan untuk pemulihan password.
        </p>
      </div>

      <div
        class="p-3.5 rounded-xl bg-muted/30 border border-border/60 text-left text-xs text-muted-foreground space-y-1.5"
      >
        <p class="font-semibold text-foreground flex items-center gap-1.5">
          <Info class="h-4 w-4 text-primary shrink-0" />
          <span>Petunjuk Lanjutan:</span>
        </p>
        <ul class="list-disc pl-4 space-y-1 text-[11px] leading-normal">
          <li>Buka email Anda dan klik tautan reset password.</li>
          <li>
            Periksa juga folder <strong>Spam</strong> atau <strong>Junk</strong> jika tidak ada di
            Inbox.
          </li>
        </ul>
      </div>

      <div class="pt-2 space-y-3">
        <Button
          as-child
          class="w-full h-10 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs tracking-wide shadow-md flex items-center justify-center gap-2 cursor-pointer"
        >
          <RouterLink to="/login">
            <ArrowLeft class="h-4 w-4" />
            <span>Kembali ke Halaman Login</span>
          </RouterLink>
        </Button>

        <div class="text-xs text-muted-foreground pt-1">
          <span v-if="countdown > 0" class="text-[11px] text-muted-foreground">
            Tidak menerima email? Kirim ulang dalam <strong>{{ countdown }}s</strong>
          </span>
          <button
            v-else
            type="button"
            class="text-2sm text-primary font-medium hover:underline cursor-pointer"
            @click="isSent = false"
          >
            Kirim Ulang / Ubah Email
          </button>
        </div>
      </div>
    </div>

    <!-- INITIAL FORM SCREEN -->
    <div v-else>
      <!-- Alert Error -->
      <div
        v-if="error"
        class="mb-4 p-3 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-xs flex items-start gap-2"
      >
        <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
        <span>{{ error }}</span>
      </div>

      <!-- Form -->
      <form class="space-y-4" novalidate @submit.prevent="handleSubmit">
        <div class="space-y-1">
          <label class="text-xs font-semibold text-muted-foreground block">Email Terdaftar</label>
          <div class="relative flex items-center">
            <Mail class="absolute left-3 h-4 w-4 text-muted-foreground pointer-events-none" />
            <Input
              v-model="email"
              type="email"
              placeholder="nama@domain.com"
              autocomplete="email"
              :disabled="isLoading"
              class="pl-9 h-10 bg-muted/30 border-input text-xs"
              @input="error = ''"
            />
          </div>
        </div>

        <Button
          type="submit"
          :disabled="isLoading"
          class="w-full h-10 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-2sm tracking-wide shadow-md flex items-center justify-center gap-2 cursor-pointer"
        >
          <span>{{ isLoading ? 'Memproses...' : 'Kirim Tautan Reset' }}</span>
          <ArrowRight v-if="!isLoading" class="h-4 w-4" />
        </Button>
      </form>
    </div>

    <!-- Footer Link -->
    <template v-if="!isSent" #footer>
      <RouterLink
        to="/login"
        class="inline-flex items-center gap-1.5 text-xs text-muted-foreground hover:text-primary transition-colors font-medium"
      >
        <ArrowLeft class="h-3.5 w-3.5" />
        <span>Kembali ke Halaman Login</span>
      </RouterLink>
    </template>
  </AuthCard>
</template>
