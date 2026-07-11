<script setup lang="ts">
import AdminSidebar from '@/components/AdminSidebar.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';

// Definisikan properti opsional jika layout butuh judul atau navigasi breadcrumb khusus
defineProps<{
  title?: string;
  parentTitle?: string;
}>();
</script>

<template>
  <SidebarProvider>
    <div class="flex min-h-screen w-full bg-muted/40 font-sans">
      <!-- 1. SIDEBAR (Pecah ke Sub-Komponen Mandiri) -->
      <AdminSidebar />

      <!-- 2. MAIN INSET CONTENT -->
      <SidebarInset class="flex flex-1 flex-col">
        <!-- Header Clean (Pecah ke Sub-Komponen Mandiri) -->
        <AdminHeader :parent-title="parentTitle" :title="title" />

        <!-- Main Slot Content (Konten Halaman Utama) -->
        <main class="flex flex-1 flex-col gap-6 p-6">
          <slot />
        </main>
      </SidebarInset>
    </div>
  </SidebarProvider>
</template>
