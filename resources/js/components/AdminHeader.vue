<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { PanelLeft, Bell } from '@lucide/vue';

const route = useRoute();
const router = useRouter();

const breadcrumbs = computed(() => route.meta?.breadcrumbs ?? []);

function navigateBreadcrumb(target: string) {
  if (router.hasRoute(target)) {
    router.push({ name: target });
  } else {
    router.push(target);
  }
}
</script>

<template>
  <!-- Top Clean Navigation / Header -->
  <header
    :class="
      cn(
        'shrink-0 flex h-14 items-center justify-between bg-background/95 backdrop-blur-md px-4 lg:pl-2.5 md:px-6 gap-4 border-none',
      )
    "
  >
    <div class="flex items-center gap-2">
      <SidebarTrigger
        class="size-8 rounded-lg border border-border bg-card flex items-center justify-center hover:bg-accent cursor-pointer"
      >
        <PanelLeft class="size-4 text-foreground" />
      </SidebarTrigger>
      <div class="mx-1 h-4 w-px bg-border self-center" />

      <!-- Breadcrumbs dari route.meta.breadcrumbs -->
      <div class="flex items-center gap-2 text-xs select-none">
        <template v-if="breadcrumbs.length > 0">
          <template v-for="(crumb, i) in breadcrumbs" :key="i">
            <span v-if="i > 0" class="text-muted-foreground/60">/</span>
            <button
              v-if="crumb.route !== null"
              type="button"
              class="text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
              @click="navigateBreadcrumb(crumb.route!)"
            >
              {{ crumb.label }}
            </button>
            <span v-else class="font-medium text-foreground">{{ crumb.label }}</span>
          </template>
        </template>
        <span v-else class="font-medium text-foreground">
          {{ route.meta?.title || 'Dashboard' }}
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
