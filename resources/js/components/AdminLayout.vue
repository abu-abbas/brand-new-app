<script setup lang="ts">
import AdminSidebar from '@/components/AdminSidebar.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { ScrollArea } from '@/components/ui/scroll-area';

// Definisikan properti opsional jika layout butuh judul atau navigasi breadcrumb khusus
defineProps<{
  title?: string;
  parentTitle?: string;
}>();
</script>

<template>
  <SidebarProvider>
    <div class="flex h-svh w-full overflow-hidden bg-background font-sans">
      <!-- 1. SIDEBAR (Warna Putih/Clean) -->
      <AdminSidebar />

      <!-- 2. MAIN INSET CONTENT -->
      <SidebarInset class="flex h-svh flex-1 flex-col overflow-hidden bg-background">
        <!-- Header Clean (Warna Putih) -->
        <AdminHeader :parent-title="parentTitle" :title="title" />

        <!-- Main Content Area: Muted Container dengan Rounded Corners & Cross-Platform ScrollArea -->
        <div class="flex flex-1 flex-col min-h-0 p-4 lg:pl-2.5 pt-1 md:p-6 md:pt-1">
          <ScrollArea class="flex-1 min-h-0 rounded-2xl bg-muted/60 dark:bg-muted/30 shadow-2xs">
            <main class="space-y-6 p-4 md:p-6">
              <slot />
            </main>
          </ScrollArea>
        </div>
      </SidebarInset>
    </div>
  </SidebarProvider>
</template>
