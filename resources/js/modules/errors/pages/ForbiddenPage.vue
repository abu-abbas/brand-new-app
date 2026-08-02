<script setup lang="ts">
import { useRouter } from 'vue-router';
import { ShieldAlert, Home, ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const router = useRouter();

function goHome() {
  router.push({ name: 'home' });
}

function goBack() {
  if (window.history.length > 1) {
    router.back();
  } else {
    router.push({ name: 'home' });
  }
}
</script>

<template>
  <div
    class="min-h-screen w-full flex items-center justify-center bg-background px-4 py-12 font-sans text-foreground"
  >
    <div class="relative w-full max-w-md text-center">
      <!-- Background subtle glow effect -->
      <div
        class="absolute -top-12 left-1/2 -z-10 h-64 w-64 -translate-x-1/2 rounded-full bg-destructive/10 blur-3xl"
      ></div>

      <!-- Icon & Status Badge -->
      <div class="mb-6 flex flex-col items-center justify-center">
        <div
          class="relative flex size-20 items-center justify-center rounded-2xl border border-destructive/20 bg-destructive/10 text-destructive shadow-sm"
        >
          <ShieldAlert class="size-10 stroke-[1.75]" />
        </div>
        <span
          class="mt-4 inline-flex items-center rounded-full border border-destructive/20 bg-destructive/5 px-3 py-1 text-xs font-semibold text-destructive font-mono"
        >
          ERROR 403
        </span>
      </div>

      <!-- Heading & Description -->
      <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">Akses Ditolak</h1>
      <p class="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-base">
        Maaf, Anda tidak memiliki izin atau hak akses yang cukup untuk membuka halaman ini. Silakan
        hubungi administrator jika Anda memerlukan akses.
      </p>

      <!-- Action CTA Buttons -->
      <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
        <Button class="w-full gap-2 font-medium sm:w-auto" size="lg" @click="goHome">
          <Home class="size-4" />
          Kembali ke Beranda
        </Button>
        <Button
          variant="outline"
          class="w-full gap-2 font-medium sm:w-auto"
          size="lg"
          @click="goBack"
        >
          <ArrowLeft class="size-4" />
          Halaman Sebelumnya
        </Button>
      </div>
    </div>
  </div>
</template>
