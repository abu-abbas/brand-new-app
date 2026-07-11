<script setup lang="ts">
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { PanelLeft, Bell } from '@lucide/vue';

defineProps<{
  title?: string;
  parentTitle?: string;
}>();
</script>

<template>
  <!-- Top Clean Navigation / Header -->
  <header
    class="sticky top-0 z-10 flex h-14 items-center justify-between bg-background/95 backdrop-blur-md px-6 gap-4"
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger
        class="size-8 rounded-lg border border-border bg-card flex items-center justify-center hover:bg-accent cursor-pointer"
      >
        <PanelLeft class="size-4 text-foreground" />
      </SidebarTrigger>
      <div class="mx-3 h-4 w-px bg-border self-center" />

      <!-- Breadcrumbs style clean -->
      <div class="flex items-center gap-2 text-xs select-none">
        <span v-if="parentTitle" class="text-muted-foreground">
          {{ parentTitle }}
        </span>
        <span v-if="parentTitle" class="text-muted-foreground/60">/</span>
        <span class="font-medium text-foreground">
          {{ title || 'Dashboard' }}
        </span>
      </div>
    </div>

    <!-- Right Side Header (Notification Button Only) -->
    <div class="flex items-center gap-2">
      <Button
        variant="ghost"
        size="icon"
        class="size-8 rounded-lg bg-primary/15 text-primary border border-primary/30 hover:bg-primary/20 hover:text-primary cursor-pointer"
        aria-label="Notifications"
      >
        <Bell class="size-4" />
      </Button>
    </div>
  </header>
</template>
