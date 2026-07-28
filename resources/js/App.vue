<script setup lang="ts">
import { useTheme } from '@/composables/useTheme';
import { useDarkMode } from '@/composables/useDarkMode';
import ConfirmDialog from '@/components/custom-ui/confirm-dialog/ConfirmDialog.vue';

// Inisialisasi tema warna (accent color) dan dark mode secara global di root aplikasi
useTheme();
useDarkMode();
</script>

<template>
  <ConfirmDialog />
  <router-view />
</template>
