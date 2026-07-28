<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Shield, User, Lock, Eye, EyeOff, ArrowRight, HelpCircle, Megaphone } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

defineEmits<{
  (e: 'open-announcement'): void;
}>();

const form = reactive({
  username: '',
  password: '',
  captcha: '',
});

const showPassword = ref(false);
const captchaCode = ref('X72B9');
const isLoading = ref(false);

const handleLogin = () => {
  isLoading.value = true;
  window.setTimeout(() => {
    isLoading.value = false;
  }, 1000);
};

const handleGoogleLogin = () => {
  // Toast or SSO redirect handler
};

const refreshCaptcha = () => {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let result = '';
  for (let i = 0; i < 5; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  captchaCode.value = result;
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
            class="h-7.5 w-7.5 rounded-xl bg-primary text-primary-foreground flex items-center justify-center shadow-sm"
          >
            <Shield class="h-4 w-4 fill-current stroke-none" />
          </div>
          <span class="text-base font-bold tracking-tight text-foreground">Portal</span>
        </div>

        <!-- Mobile Only Announcement Button -->
        <button
          type="button"
          class="lg:hidden h-7.5 px-3 rounded-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs tracking-wide shadow-md flex items-center gap-1.5 transition-all duration-200 active:scale-95 cursor-pointer border border-primary-foreground/10"
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
              class="pl-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary rounded-xl text-xs"
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
              class="pl-8.5 pr-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary rounded-xl text-xs"
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
        <div class="space-y-1">
          <label class="text-[10px] font-bold tracking-wider uppercase text-muted-foreground block">
            Security Verification
          </label>
          <div class="grid grid-cols-5 gap-2">
            <div class="col-span-3">
              <Input
                v-model="form.captcha"
                type="text"
                placeholder="CAPTCHA"
                required
                class="h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 focus-visible:ring-primary focus-visible:border-primary rounded-xl text-xs uppercase"
              />
            </div>
            <button
              type="button"
              title="Klik untuk acak captcha"
              class="col-span-2 h-9 bg-muted/70 dark:bg-muted/40 border border-input rounded-xl flex items-center justify-center font-mono font-bold italic tracking-widest text-foreground text-xs hover:bg-muted transition-colors select-none cursor-pointer"
              @click="refreshCaptcha"
            >
              {{ captchaCode }}
            </button>
          </div>
        </div>

        <!-- spacing -->
        <div class="mt-2.5 my-8.5" />

        <!-- Submit Button -->
        <Button
          type="submit"
          :disabled="isLoading"
          class="w-full h-9.5 bg-primary hover:bg-primary/90 text-primary-foreground font-semibold text-xs tracking-wider uppercase rounded-xl shadow-md hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2 mt-1 cursor-pointer"
        >
          <span>Masuk Ke Sistem</span>
          <ArrowRight class="h-3.5 w-3.5" />
        </Button>

        <!-- Divider: Atau masuk dengan -->
        <div class="relative flex items-center justify-center my-1.5">
          <div class="border-t border-border w-full"></div>
          <span
            class="bg-background px-2 text-[9px] text-muted-foreground uppercase tracking-wider font-semibold shrink-0"
          >
            atau masuk dengan
          </span>
          <div class="border-t border-border w-full"></div>
        </div>

        <!-- Google SSO Login Button -->
        <button
          type="button"
          class="w-full h-9 bg-card hover:bg-muted/60 border border-border text-foreground font-medium text-xs rounded-xl shadow-xs flex items-center justify-center gap-2 transition-all duration-200 active:scale-[0.99] cursor-pointer"
          @click="handleGoogleLogin"
        >
          <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"
            />
          </svg>
          <span>Google Account</span>
        </button>

        <!-- Help Link -->
        <div class="text-center pt-0.5">
          <button
            type="button"
            class="inline-flex items-center gap-1 text-[11px] text-muted-foreground hover:text-primary transition-colors font-medium cursor-pointer"
          >
            <HelpCircle class="h-3 w-3" />
            <span>Masalah saat login?</span>
          </button>
        </div>
      </form>
    </div>

    <!-- Footer (Bottom Aligned) -->
    <div
      class="pt-2 border-t border-border flex items-center justify-between text-[9px] font-semibold tracking-wider text-muted-foreground uppercase max-w-92.5 w-full mx-auto shrink-0 mt-1.5"
    >
      <span>VERSION 2.4.0</span>
      <span>SEKRETARIAT JENDERAL</span>
    </div>
  </div>
</template>
