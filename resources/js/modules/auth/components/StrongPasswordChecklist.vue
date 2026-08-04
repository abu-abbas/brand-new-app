<script setup lang="ts">
import { computed } from 'vue';
import { Check, X } from '@lucide/vue';

const props = defineProps<{
  password?: string;
}>();

const checks = computed(() => {
  const pwd = props.password || '';
  return [
    { label: 'Minimal 8 karakter (maksimal 255)', valid: pwd.length >= 8 && pwd.length <= 255 },
    { label: 'Mengandung huruf besar (A-Z)', valid: /[A-Z]/.test(pwd) },
    { label: 'Mengandung huruf kecil (a-z)', valid: /[a-z]/.test(pwd) },
    { label: 'Mengandung angka (0-9)', valid: /[0-9]/.test(pwd) },
    { label: 'Mengandung simbol (!@#$%^&*()_+.-)', valid: /[!@#$%^&*()_+\-.]/.test(pwd) },
  ];
});
</script>

<template>
  <div class="rounded-lg border bg-muted/30 p-3 text-xs space-y-1.5">
    <div class="font-medium text-muted-foreground mb-1">Syarat keamanan password:</div>
    <div
      v-for="(item, index) in checks"
      :key="index"
      class="flex items-center gap-2 transition-colors"
      :class="
        item.valid ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-muted-foreground'
      "
    >
      <Check
        v-if="item.valid"
        class="h-3.5 w-3.5 shrink-0 text-emerald-600 dark:text-emerald-400"
      />
      <X v-else class="h-3.5 w-3.5 shrink-0 text-muted-foreground/60" />
      <span>{{ item.label }}</span>
    </div>
  </div>
</template>
