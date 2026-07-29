<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
</script>

<template>
  <AdminLayout>
    <!-- Slot Header Actions dengan Scoped Slot goBack -->
    <template #header-actions="{ goBack }">
      <Button variant="secondary" class="cursor-pointer" @click="goBack({ name: 'blank-page' })">
        Batal
      </Button>
      <Button variant="default" class="cursor-pointer"> Simpan </Button>
    </template>

    <!-- Main Content Area untuk Detail / Form -->
    <div class="rounded-2xl border border-dashed border-border/80 bg-background/60 p-6 shadow-2xs">
      <div
        class="flex min-h-90 flex-col items-center justify-center rounded-xl bg-muted/20 p-8 text-center"
      >
        <Card class="w-full max-w-lg border-border/60 bg-card shadow-2xs text-left">
          <CardContent class="p-6 space-y-4">
            <h3 class="text-base font-semibold text-foreground">Halaman Detail / Form</h3>
            <p class="text-xs text-muted-foreground">
              Lihat bagian tombol icon di kiri judul di atas — otomatis berubah menjadi **Tombol
              Kembali** (*Back Button*). Selain itu, tombol Batal di kanan menggunakan scoped slot
              <code class="font-mono text-primary">#header-actions="{ goBack }"</code>.
            </p>
            <div
              class="rounded-lg border border-border/80 bg-muted/40 p-4 text-xs font-mono text-foreground"
            >
              goBack({ name: 'blank-page' })
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AdminLayout>
</template>
