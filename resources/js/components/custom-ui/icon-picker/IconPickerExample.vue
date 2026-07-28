<script setup lang="ts">
import { ref } from 'vue';
import { Sparkles, Palette } from '@lucide/vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import IconPicker from './IconPicker.vue';

const selectedIcon = ref('Sparkles');
const customIcon = ref('Bookmark');
</script>

<template>
  <div class="space-y-8 p-6">
    <!-- Header Section -->
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="flex items-center gap-2 text-2xl font-bold tracking-tight text-foreground">
          <Sparkles class="size-6 text-primary" />
          Icon Picker Component
        </h2>
        <p class="text-muted-foreground">
          Komponen dialog pencarian icon dinamis berbasis pustaka `@lucide/vue` dengan penyesuaian
          warna aksen tema.
        </p>
      </div>
    </div>

    <!-- Interactive Showcase Grid -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- Example 1: Basic Usage -->
      <Card class="border-border bg-card shadow-xs">
        <CardHeader>
          <div class="flex items-center justify-between">
            <Badge variant="secondary">Basic v-model</Badge>
            <Palette class="size-4 text-primary" />
          </div>
          <CardTitle class="mt-2 text-lg">Pilih Icon Komponen</CardTitle>
          <CardDescription> Pilih icon apapun dari 800+ koleksi Lucide Icons. </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex flex-wrap items-center gap-4">
            <IconPicker v-model="selectedIcon" placeholder="Pilih Icon..." />

            <div
              class="flex items-center gap-2 text-xs text-muted-foreground font-mono bg-muted/60 px-3 py-2 rounded-lg border border-border min-w-0 max-w-full overflow-hidden"
            >
              <span class="shrink-0">Value:</span>
              <code class="text-primary font-bold truncate">{{ selectedIcon || '(kosong)' }}</code>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Example 2: Custom Title & Clearable -->
      <Card class="border-border bg-card shadow-xs">
        <CardHeader>
          <div class="flex items-center justify-between">
            <Badge variant="outline">Custom Labels</Badge>
          </div>
          <CardTitle class="mt-2 text-lg">Icon Kategori / Modul</CardTitle>
          <CardDescription> Kustomisasi judul dialog dan placeholder. </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex items-center gap-4">
            <IconPicker
              v-model="customIcon"
              title="Pilih Icon Modul Utama"
              description="Icon ini akan ditampilkan pada navigasi menu utama aplikasi."
              placeholder="Pilih Icon Modul..."
            />

            <div
              class="flex items-center gap-2 text-xs text-muted-foreground font-mono bg-muted/60 px-3 py-2 rounded-lg border border-border"
            >
              <span>Value:</span>
              <code class="text-primary font-bold">{{ customIcon || '(kosong)' }}</code>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
