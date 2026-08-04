<script setup lang="ts">
import { ref } from 'vue';
import { X } from '@lucide/vue';
import LoginForm from '../components/LoginForm.vue';
import AnnouncementPanel from '@/modules/announcement/components/AnnouncementPanel.vue';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog';

const isAnnouncementModalOpen = ref(false);
</script>

<template>
  <div
    class="fixed inset-0 flex flex-col lg:flex-row bg-muted/20 font-sans text-foreground overflow-hidden"
  >
    <!-- Left Panel: Slim Login Form Panel (Full Height, Vertically Centered Content) -->
    <section
      class="w-full lg:w-[38%] xl:w-[34%] 2xl:w-[30%] h-full shrink-0 flex flex-col bg-background z-0 overflow-hidden border-r border-border/40 justify-center"
    >
      <LoginForm class="h-full" @open-announcement="isAnnouncementModalOpen = true" />
    </section>

    <!-- Right Panel: Announcement Panel (Full Height, Vertically & Horizontally Centered Content) -->
    <section class="hidden lg:block flex-1 h-full bg-[#f4f4f6] dark:bg-[#09090b] overflow-hidden">
      <AnnouncementPanel class="h-full" />
    </section>

    <!-- Mobile Only: Modal Dialog for Announcement -->
    <Dialog :open="isAnnouncementModalOpen" @update:open="isAnnouncementModalOpen = $event">
      <DialogContent
        class="max-w-md sm:max-w-lg w-[92vw] p-0 overflow-hidden rounded-2xl border-none shadow-2xl"
      >
        <DialogHeader class="sr-only">
          <DialogTitle>Papan Pengumuman</DialogTitle>
          <DialogDescription>Pengumuman terkini dan panduan akses portal</DialogDescription>
        </DialogHeader>

        <div class="relative h-[85vh] flex flex-col bg-[#f4f4f6] dark:bg-[#09090b]">
          <!-- Close Button Overlay -->
          <button
            type="button"
            class="absolute top-3 right-3 z-50 h-8 w-8 rounded-full bg-background/90 shadow border border-border flex items-center justify-center text-muted-foreground hover:text-foreground hover:bg-background transition-colors cursor-pointer"
            @click="isAnnouncementModalOpen = false"
          >
            <X class="h-4 w-4" />
          </button>

          <!-- Pure AnnouncementPanel without pt-4 wrapper leak -->
          <AnnouncementPanel class="h-full" />
        </div>
      </DialogContent>
    </Dialog>
  </div>
</template>
