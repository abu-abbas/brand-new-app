<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Shield, User, Lock, Eye, EyeOff, ArrowRight, HelpCircle, Megaphone } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { AuthFacade } from '@/modules/auth/api/auth.facade';
import { useAuthStore } from '@/stores/auth';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import type { AppError } from '@/lib/axios';

defineEmits<{
  (e: 'open-announcement'): void;
}>();

const form = reactive({
  username: '',
  password: '',
  captcha_key: '',
  captcha: '',
});

const showPassword = ref(false);
const captchaImage = ref('');
const captchaLoading = ref(false);
const isLoading = ref(false);
const errorMessage = ref('');
const auth = useAuthStore();
const appBootstrap = useAppBootstrapStore();
const captchaEnabled = computed(() => appBootstrap.config.captcha.enabled);
const route = useRoute();
const router = useRouter();

const refreshCaptcha = async () => {
  captchaLoading.value = true;
  form.captcha = '';
  form.captcha_key = '';

  try {
    const challenge = await AuthFacade.captcha();
    captchaImage.value = challenge.img;
    form.captcha_key = challenge.key;
  } catch (error) {
    errorMessage.value = (error as AppError).message;
  } finally {
    captchaLoading.value = false;
  }
};

onMounted(() => {
  if (captchaEnabled.value) void refreshCaptcha();
});

const handleLogin = async () => {
  isLoading.value = true;
  errorMessage.value = '';

  try {
    await auth.login(form);
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    await router.replace(redirect);
  } catch (error) {
    const appError = error as AppError;
    const firstValidationError = Object.values(appError.validationErrors ?? {})
      .flat()
      .find((value) => value);
    errorMessage.value =
      typeof firstValidationError === 'object' &&
      firstValidationError !== null &&
      'message' in firstValidationError
        ? String(firstValidationError.message)
        : appError.message;
    if (captchaEnabled.value) void refreshCaptcha();
  } finally {
    isLoading.value = false;
  }
};
</script>

<template>
  <div
    class="flex flex-col justify-between h-full overflow-y-auto p-4 sm:p-5 lg:p-6 bg-background text-foreground"
  >
    <!-- Slim Form Container (Zero-Scroll for 1366x617) -->
    <div class="max-w-92.5 w-full mx-auto my-auto py-1">
      <!-- Top Bar: Portal Brand Logo & Mobile Announcement Button -->
      <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-2">
          <div
            class="h-7.5 w-7.5 rounded-lg bg-primary text-primary-foreground flex items-center justify-center shadow-sm"
          >
            <Shield class="h-4 w-4 fill-current stroke-none" />
          </div>
          <span class="text-base font-bold tracking-tight text-foreground">Portal</span>
        </div>

        <!-- Mobile Only Announcement Button -->
        <button
          type="button"
          class="lg:hidden h-7.5 px-3 rounded-md bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs tracking-wide shadow-md flex items-center gap-1.5 transition-all duration-200 active:scale-95 cursor-pointer border border-primary-foreground/10"
          @click="$emit('open-announcement')"
        >
          <div class="relative flex items-center justify-center">
            <Megaphone class="h-3 w-3" />
            <span
              class="absolute -top-1 -right-1 h-2 w-2 rounded-full bg-amber-400 animate-ping"
            ></span>
          </div>
          <span>Pengumuman</span>
        </button>
      </div>

      <!-- Subtitle -->
      <div class="text-[9px] font-bold tracking-[0.2em] uppercase text-primary mb-0.5">
        Official Access
      </div>

      <!-- Main Title -->
      <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-foreground mb-1">
        PPPK PW <span class="font-normal italic text-primary">Login</span>
      </h1>

      <!-- Description -->
      <p class="text-[11px] text-muted-foreground mb-2.5 xl:mb-7.5 leading-relaxed">
        Selamat datang kembali. Masukkan kredensial Anda untuk melanjutkan.
      </p>

      <form class="space-y-2.5" @submit.prevent="handleLogin">
        <!-- Username Field -->
        <div class="space-y-1">
          <label class="text-[10px] font-bold tracking-wider uppercase text-muted-foreground block">
            Username
          </label>
          <div class="relative flex items-center">
            <User class="absolute left-3 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
            <Input
              v-model="form.username"
              type="text"
              placeholder="ID Pegawai / Username"
              required
              autocomplete="username"
              class="pl-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary text-xs"
            />
          </div>
        </div>

        <!-- Password Field -->
        <div class="space-y-1">
          <label class="text-[10px] font-bold tracking-wider uppercase text-muted-foreground block">
            Password
          </label>
          <div class="relative flex items-center">
            <Lock class="absolute left-3 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
            <Input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
              autocomplete="current-password"
              class="pl-8.5 pr-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary text-xs"
            />
            <button
              type="button"
              class="absolute right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showPassword = !showPassword"
            >
              <Eye v-if="!showPassword" class="h-3.5 w-3.5" />
              <EyeOff v-else class="h-3.5 w-3.5" />
            </button>
          </div>
        </div>

        <!-- Security Verification / Captcha -->
        <template v-if="captchaEnabled">
          <div class="space-y-1">
            <label
              class="text-[10px] font-bold tracking-wider uppercase text-muted-foreground block"
            >
              Security Verification
            </label>
            <div class="grid grid-cols-5 gap-2">
              <div class="col-span-3">
                <Input
                  v-model="form.captcha"
                  type="text"
                  placeholder="CAPTCHA"
                  required
                  autocomplete="off"
                  class="h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary text-xs uppercase"
                />
              </div>
              <button
                type="button"
                title="Klik untuk acak captcha"
                :disabled="captchaLoading"
                class="col-span-2 h-9 bg-muted/70 dark:bg-muted/40 border border-input rounded-md flex items-center justify-center font-mono font-bold italic tracking-widest text-foreground text-xs hover:bg-muted transition-colors select-none cursor-pointer"
                @click="refreshCaptcha"
              >
                <img
                  v-if="captchaImage"
                  :src="captchaImage"
                  alt="Kode keamanan"
                  class="size-full rounded object-contain"
                />
                <span v-else>Memuat...</span>
              </button>
            </div>
          </div>

          <p v-if="errorMessage" role="alert" class="text-xs text-destructive">
            {{ errorMessage }}
          </p>
        </template>

        <div class="my-2.5 lg:my-7.5" />

        <!-- Submit Button -->
        <Button
          type="submit"
          :disabled="isLoading || (captchaEnabled && captchaLoading)"
          class="w-full h-9.5 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs tracking-wider uppercase shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 mt-2 cursor-pointer"
        >
          <span>{{ isLoading ? 'Memproses...' : 'Masuk Ke Sistem' }}</span>
          <ArrowRight v-if="!isLoading" data-icon="inline-end" />
        </Button>
      </form>
    </div>

    <!-- Footer (Bottom Aligned) -->
    <div
      class="pt-2 border-t border-border flex items-center justify-between text-[9px] font-semibold tracking-wider text-muted-foreground uppercase max-w-92.5 w-full mx-auto shrink-0 mt-1.5"
    >
      <span>VERSION 2.4.0</span>
      <button
        type="button"
        class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-muted-foreground hover:text-primary transition-colors cursor-pointer normal-case leading-none"
      >
        <HelpCircle class="h-3 w-3 shrink-0" />
        <span class="inline-block pt-px">Masalah saat login?</span>
      </button>
    </div>
  </div>
</template>
