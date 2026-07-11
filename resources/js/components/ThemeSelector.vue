<script setup lang="ts">
import { useTheme, type ThemeName } from '@/composables/useTheme';
import { ref, onMounted, onUnmounted } from 'vue';
import { ChevronDown } from '@lucide/vue';

const { activeTheme, setTheme } = useTheme();
const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const themes: { name: ThemeName; label: string; color: string }[] = [
  { name: 'default', label: 'Sky (Default)', color: '#0ea5e9' },
  { name: 'amber', label: 'Amber', color: '#f59e0b' },
  { name: 'blue', label: 'Blue', color: '#3b82f6' },
  { name: 'cyan', label: 'Cyan', color: '#06b6d4' },
  { name: 'emerald', label: 'Emerald', color: '#10b981' },
  { name: 'fuchsia', label: 'Fuchsia', color: '#d946ef' },
  { name: 'green', label: 'Green', color: '#22c55e' },
  { name: 'indigo', label: 'Indigo', color: '#6366f1' },
  { name: 'lime', label: 'Lime', color: '#84cc16' },
  { name: 'neutral', label: 'Neutral', color: '#737373' },
  { name: 'orange', label: 'Orange', color: '#f97316' },
  { name: 'pink', label: 'Pink', color: '#ec4899' },
  { name: 'purple', label: 'Purple', color: '#a855f7' },
  { name: 'red', label: 'Red', color: '#ef4444' },
  { name: 'rose', label: 'Rose', color: '#f43f5e' },
  { name: 'teal', label: 'Teal', color: '#14b8a6' },
  { name: 'violet', label: 'Violet', color: '#8b5cf6' },
  { name: 'yellow', label: 'Yellow', color: '#eab308' },
];

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectTheme = (name: ThemeName) => {
  setTheme(name);
  isOpen.value = false;
};

const getActiveThemeDetail = () => {
  return themes.find((t) => t.name === activeTheme.value) || themes[0];
};

const handleClickOutside = (event: MouseEvent) => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
  <div ref="dropdownRef" class="relative w-full text-left">
    <!-- Dropdown Trigger Button -->
    <button
      type="button"
      class="flex w-full items-center justify-between rounded-lg border border-border bg-card px-4 py-2.5 text-sm font-medium shadow-xs hover:bg-accent/50 cursor-pointer focus:outline-hidden"
      @click="toggleDropdown"
    >
      <div class="flex flex-col items-start gap-0.5">
        <span class="text-xs text-muted-foreground font-normal">Theme</span>
        <span class="text-sm font-semibold tracking-tight">{{ getActiveThemeDetail().label }}</span>
      </div>
      <div class="flex items-center gap-2">
        <span
          class="size-3.5 rounded-full border border-black/10 dark:border-white/10 shrink-0"
          :style="{ backgroundColor: getActiveThemeDetail().color }"
        />
        <ChevronDown
          class="size-4 text-muted-foreground transition-transform duration-200"
          :class="isOpen ? 'rotate-180' : ''"
        />
      </div>
    </button>

    <!-- Dropdown Content -->
    <div
      v-if="isOpen"
      class="absolute right-0 mt-1.5 z-50 w-full max-h-72 overflow-y-auto rounded-lg border border-border bg-card shadow-lg focus:outline-hidden py-1"
    >
      <button
        v-for="t in themes"
        :key="t.name"
        type="button"
        class="flex w-full items-center justify-between px-4 py-2.5 text-sm font-medium text-foreground hover:bg-accent cursor-pointer transition-colors text-left"
        :class="activeTheme === t.name ? 'bg-accent/40 font-semibold' : ''"
        @click="selectTheme(t.name)"
      >
        <span>{{ t.label }}</span>
        <span
          class="size-3.5 rounded-full border border-black/10 dark:border-white/10 shrink-0"
          :style="{ backgroundColor: t.color }"
        />
      </button>
    </div>
  </div>
</template>
