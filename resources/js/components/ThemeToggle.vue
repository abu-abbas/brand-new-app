<script setup lang="ts">
import { useDarkMode } from '@/composables/useDarkMode';
import { Button } from '@/components/ui/button';
import { Moon, Sun } from '@lucide/vue';

const { isDark, toggleDarkMode } = useDarkMode();
</script>

<template>
  <Button
    variant="outline"
    size="icon"
    class="rounded-full size-10"
    aria-label="Toggle theme"
    @click="toggleDarkMode($event)"
  >
    <Sun v-if="isDark" class="size-5 text-yellow-400" />
    <Moon v-else class="size-5 text-foreground" />
  </Button>
</template>
