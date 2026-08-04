<script setup lang="ts">
import { Shield } from '@lucide/vue';
import type { Component } from 'vue';

withDefaults(
  defineProps<{
    title: string;
    subtitle?: string;
    icon?: Component;
    iconClass?: string;
  }>(),
  {
    subtitle: undefined,
    icon: () => Shield,
    iconClass: 'h-5 w-5 fill-current stroke-none',
  },
);
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
          <component :is="icon" :class="iconClass" />
        </div>
        <div>
          <h1 class="text-xl font-bold tracking-tight text-foreground">{{ title }}</h1>
          <p v-if="subtitle" class="text-xs text-muted-foreground">{{ subtitle }}</p>
        </div>
      </div>

      <!-- Slot Konten Utama -->
      <slot />

      <!-- Slot Footer Link (jika ada) -->
      <div v-if="$slots.footer" class="mt-6 pt-4 border-t border-border/50 text-center">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>
