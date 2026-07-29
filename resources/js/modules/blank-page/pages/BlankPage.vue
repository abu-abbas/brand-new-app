<script setup lang="ts">
import { computed, type Component } from 'vue';
import { useRoute } from 'vue-router';
import { FileCode2, Plus } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { Button } from '@/components/ui/button';

const route = useRoute();
const icon = computed<Component>(() => (route.meta.icon as Component) || FileCode2);
</script>

<template>
  <AdminLayout>
    <!-- Slot Header Actions (Tombol Tambah yang Navigasi ke Detail) -->
    <template #header-actions="{ push }">
      <Button
        variant="default"
        class="gap-2 font-medium cursor-pointer"
        @click.stop.prevent="push({ name: 'blank-page.detail' })"
      >
        <Plus class="size-4" />
        Mulai Tambah Konten
      </Button>
    </template>

    <!-- Main Content Area -->
    <div class="rounded-2xl border border-dashed border-border/80 bg-background/60 p-6 shadow-2xs">
      <!-- Clean Empty State Area -->
      <div
        class="flex min-h-90 flex-col items-center justify-center rounded-xl bg-muted/20 p-8 text-center"
      >
        <div
          class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary shadow-2xs"
        >
          <component :is="icon" class="size-7" />
        </div>
        <h3 class="mt-4 text-base font-semibold text-foreground">Siap Diisi Komponen</h3>
        <p class="mt-1 max-w-md text-sm text-muted-foreground">
          Halaman ini sudah terintegrasi dengan Layout Admin dan Vue Router. Anda dapat mulai
          menambahkan form, tabel data, atau kartu statistik di sini.
        </p>
      </div>
    </div>
  </AdminLayout>
</template>
