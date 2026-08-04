<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  Shield,
  User,
  Lock,
  Eye,
  EyeOff,
  ArrowRight,
  HelpCircle,
  Megaphone,
  AlertCircle,
  RotateCcw,
  Volume2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  InputGroup,
  InputGroupAddon,
  InputGroupButton,
  InputGroupInput,
} from '@/components/ui/input-group';
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

const errors = reactive({
  username: '',
  password: '',
  captcha: '',
  general: '',
});

function clearErrors() {
  errors.username = '';
  errors.password = '';
  errors.captcha = '';
  errors.general = '';
}

const showPassword = ref(false);
const captchaImage = ref('');
const captchaLoading = ref(false);
const isAudioPlaying = ref(false);
const isLoading = ref(false);
const auth = useAuthStore();
const appBootstrap = useAppBootstrapStore();
const captchaEnabled = computed(() => appBootstrap.config.captcha.enabled);
const route = useRoute();
const router = useRouter();

const refreshCaptcha = async (resetError = false) => {
  captchaLoading.value = true;
  form.captcha = '';
  form.captcha_key = '';
  if (resetError) {
    errors.captcha = '';
  }

  try {
    const challenge = await AuthFacade.captcha();
    captchaImage.value = challenge.img;
    form.captcha_key = challenge.key;
  } catch (error) {
    errors.general = (error as AppError).message;
  } finally {
    captchaLoading.value = false;
  }
};

const playAudioCaptcha = async () => {
  if (!form.captcha_key || isAudioPlaying.value) return;

  isAudioPlaying.value = true;
  try {
    const audio = await AuthFacade.playCaptchaAudio(form.captcha_key);
    audio.addEventListener('ended', () => {
      isAudioPlaying.value = false;
    });
    audio.addEventListener('error', () => {
      isAudioPlaying.value = false;
    });
  } catch {
    isAudioPlaying.value = false;
  }
};

onMounted(() => {
  if (captchaEnabled.value) void refreshCaptcha(true);
});

const handleLogin = async () => {
  clearErrors();

  let hasError = false;
  if (!form.username.trim()) {
    errors.username = 'Username wajib diisi.';
    hasError = true;
  }
  if (!form.password) {
    errors.password = 'Password wajib diisi.';
    hasError = true;
  }
  if (captchaEnabled.value && !form.captcha.trim()) {
    errors.captcha = 'Kode keamanan wajib diisi.';
    hasError = true;
  }

  if (hasError) return;

  isLoading.value = true;

  try {
    await auth.login(form);
    const rawRedirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    const isAuthRoute =
      rawRedirect.startsWith('/change-password') ||
      rawRedirect.startsWith('/reset-password') ||
      rawRedirect.startsWith('/forgot-password') ||
      rawRedirect.startsWith('/login');

    const redirect = isAuthRoute ? '/' : rawRedirect;
    await router.replace(redirect);
  } catch (error) {
    const appError = error as AppError;

    if (appError.validationErrors && Object.keys(appError.validationErrors).length > 0) {
      for (const [field, fieldErrors] of Object.entries(appError.validationErrors)) {
        const firstErr = Array.isArray(fieldErrors) ? fieldErrors[0] : fieldErrors;
        const msg =
          typeof firstErr === 'object' && firstErr !== null && 'message' in firstErr
            ? String((firstErr as { message: string }).message)
            : String(firstErr);
        if (field in errors) {
          errors[field as keyof typeof errors] = msg;
        } else {
          errors.general = msg;
        }
      }
    } else {
      errors.general = appError.message;
    }

    if (captchaEnabled.value) void refreshCaptcha(false);
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
        {{ appBootstrap.config.name }}
        <span class="font-normal italic text-primary">Login</span>
      </h1>

      <!-- Description -->
      <p class="text-[11px] text-muted-foreground mb-2.5 xl:mb-6 leading-relaxed">
        Selamat datang kembali. Masukkan kredensial Anda untuk melanjutkan.
      </p>

      <!-- General Error Alert -->
      <div
        v-if="errors.general"
        class="mb-3 p-3 rounded-lg bg-destructive/10 border border-destructive/30 text-destructive text-xs flex items-start gap-2.5 animate-in fade-in slide-in-from-top-1 duration-200"
        role="alert"
      >
        <AlertCircle class="h-4 w-4 shrink-0 mt-0.5" />
        <span class="font-medium leading-snug">{{ errors.general }}</span>
      </div>

      <form class="space-y-3" novalidate @submit.prevent="handleLogin">
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
              autocomplete="username"
              class="pl-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 text-xs"
              :class="
                errors.username
                  ? 'border-destructive focus-visible:ring-destructive focus-visible:border-destructive'
                  : 'focus-visible:ring-primary focus-visible:border-primary'
              "
              @input="errors.username = ''"
            />
          </div>
          <p
            v-if="errors.username"
            class="text-[11px] font-medium text-destructive mt-1 leading-none"
          >
            {{ errors.username }}
          </p>
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
              autocomplete="current-password"
              class="pl-8.5 pr-8.5 h-9 bg-muted/40 dark:bg-muted/20 border-input text-foreground focus-visible:ring-1 text-xs"
              :class="
                errors.password
                  ? 'border-destructive focus-visible:ring-destructive focus-visible:border-destructive'
                  : 'focus-visible:ring-primary focus-visible:border-primary'
              "
              @input="errors.password = ''"
            />
            <button
              type="button"
              tabindex="-1"
              class="absolute right-3 text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="showPassword = !showPassword"
            >
              <Eye v-if="!showPassword" class="h-3.5 w-3.5" />
              <EyeOff v-else class="h-3.5 w-3.5" />
            </button>
          </div>
          <p
            v-if="errors.password"
            class="text-[11px] font-medium text-destructive mt-1 leading-none"
          >
            {{ errors.password }}
          </p>
        </div>

        <!-- Security Verification / Captcha -->
        <template v-if="captchaEnabled">
          <div class="space-y-1.5">
            <label
              class="text-[10px] font-bold tracking-wider uppercase text-muted-foreground block"
            >
              Verifikasi Keamanan
            </label>

            <!-- InputGroup Card ala AI Prompt Box -->
            <InputGroup
              class="rounded-lg border bg-muted/40 dark:bg-muted/20 px-1.5 py-1 shadow-xs"
            >
              <!-- Input Text di Atas (Centered Vertically) -->
              <InputGroupInput
                v-model="form.captcha"
                type="text"
                placeholder="Masukkan Captcha"
                autocomplete="off"
                class="h-9 p-1.5! border-0 shadow-none focus-visible:ring-0 text-xs uppercase font-medium placeholder:normal-case placeholder:font-normal flex items-center"
                :class="errors.captcha ? 'text-destructive' : ''"
                @input="errors.captcha = ''"
              />

              <div class="mb-2.5"></div>

              <!-- Bottom Row: Refresh (kiri) | Gambar Captcha (tengah) | Sound (kanan) - Tanpa Border Separator -->
              <InputGroupAddon
                align="block-end"
                class="flex items-center justify-between p-0 px-0.5 pb-0! gap-2 border-0"
              >
                <!-- Left: Refresh Button -->
                <InputGroupButton
                  variant="ghost"
                  size="icon-xs"
                  title="Acak kode captcha"
                  type="button"
                  tabindex="-1"
                  :disabled="captchaLoading"
                  class="shrink-0"
                  @click="refreshCaptcha(true)"
                >
                  <RotateCcw
                    class="h-4 w-4 text-muted-foreground"
                    :class="{ 'animate-spin': captchaLoading }"
                  />
                </InputGroupButton>

                <!-- Center: Gambar Captcha -->
                <div
                  class="relative flex-1 h-8 flex items-center justify-center overflow-hidden rounded bg-blue dark:bg-muted/20 px-2 select-none"
                >
                  <img
                    v-if="captchaImage"
                    :src="captchaImage"
                    alt="Kode keamanan"
                    class="h-full w-auto object-contain dark:mix-blend-screen dark:invert dark:hue-rotate-180"
                  />
                  <span v-else class="text-[11px] font-medium text-muted-foreground animate-pulse"
                    >Memuat...</span
                  >
                  <div
                    v-if="captchaLoading"
                    class="absolute inset-0 bg-muted/40 backdrop-blur-[1px] flex items-center justify-center z-10"
                  >
                    <RotateCcw class="h-3.5 w-3.5 text-primary animate-spin" />
                  </div>
                </div>

                <!-- Right: Sound Button -->
                <InputGroupButton
                  variant="default"
                  size="icon-xs"
                  title="Dengarkan audio captcha"
                  type="button"
                  tabindex="-1"
                  :disabled="captchaLoading || isAudioPlaying || !form.captcha_key"
                  class="shrink-0 rounded-full"
                  @click="playAudioCaptcha"
                >
                  <Volume2
                    class="text-primary-foreground/65"
                    :class="{ 'animate-pulse text-primary': isAudioPlaying }"
                  />
                </InputGroupButton>
              </InputGroupAddon>
            </InputGroup>

            <p
              v-if="errors.captcha"
              class="text-[11px] font-medium text-destructive mt-1 leading-none"
            >
              {{ errors.captcha }}
            </p>
          </div>
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
      <RouterLink
        to="/forgot-password"
        class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-muted-foreground hover:text-primary transition-colors cursor-pointer normal-case leading-none"
      >
        <HelpCircle class="h-3 w-3 shrink-0" />
        <span class="inline-block pt-px">Masalah saat login?</span>
      </RouterLink>
    </div>
  </div>
</template>
