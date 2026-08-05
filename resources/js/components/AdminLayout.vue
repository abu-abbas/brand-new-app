<script setup lang="ts">
/* global Event */
import { ref, onMounted, onUnmounted, type Component } from 'vue';
import { useRouter, type RouteLocationRaw } from 'vue-router';
import AdminSidebar from '@/components/AdminSidebar.vue';
import AdminHeader from '@/components/AdminHeader.vue';
import PageHeader from '@/components/PageHeader.vue';
import ImpersonateBanner from '@/components/ImpersonateBanner.vue';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ArrowUp } from '@lucide/vue';

defineProps<{
  title?: string;
  parentTitle?: string;
  subtitle?: string;
  icon?: Component;
  hideHeader?: boolean;
  showBack?: boolean;
  backUrl?: RouteLocationRaw;
  useNative?: boolean;
  onBack?: () => void;
}>();

const router = useRouter();

function handleBack(customTarget?: RouteLocationRaw | Event) {
  if (
    customTarget &&
    !(typeof window !== 'undefined' && customTarget instanceof Event) &&
    !(typeof customTarget === 'object' && customTarget !== null && 'preventDefault' in customTarget)
  ) {
    const rawTarget = customTarget as RouteLocationRaw;
    if (typeof rawTarget === 'string' && router.hasRoute(rawTarget)) {
      router.push({ name: rawTarget });
    } else {
      router.push(rawTarget);
    }
  } else {
    router.back();
  }
}

const containerRef = ref<HTMLElement | null>(null);
const viewportEl = ref<HTMLElement | null>(null);
const showScrollTop = ref(false);

function handleScroll(e: Event) {
  const target = e.target as HTMLElement;
  showScrollTop.value = Boolean(target && target.scrollTop > 200);
}

function scrollToTop() {
  if (viewportEl.value) {
    viewportEl.value.scrollTo({ top: 0, behavior: 'smooth' });
  }
}

import { useConfirmDialog } from '@/composables/useConfirmDialog';

const confirmDialog = useConfirmDialog();

onMounted(() => {
  if (typeof window !== 'undefined') {
    const rawNotice = sessionStorage.getItem('impersonate_notice');
    if (rawNotice) {
      sessionStorage.removeItem('impersonate_notice');
      try {
        const notice = JSON.parse(rawNotice);
        const isTtl = notice.code === 'IMP-WF-003';
        void confirmDialog({
          title: isTtl ? 'Batas Waktu Sesi Impersonate Berakhir' : 'Sesi Impersonate Dibatalkan',
          description:
            notice.message ||
            'Sesi impersonate telah berakhir dan identitas Admin Anda telah dipulihkan.',
          confirmLabel: 'Mengerti',
          cancelLabel: false,
        });
      } catch {
        // ignore parse error
      }
    }
  }

  if (!containerRef.value) return;
  const target = containerRef.value.querySelector<HTMLElement>(
    '[data-reka-scroll-area-viewport], [data-radix-scroll-area-viewport], .h-full',
  );
  if (target) {
    viewportEl.value = target;
    target.addEventListener('scroll', handleScroll, { passive: true });
  }
});

onUnmounted(() => {
  if (viewportEl.value) {
    viewportEl.value.removeEventListener('scroll', handleScroll);
  }
});
</script>

<template>
  <SidebarProvider class="flex h-svh w-full flex-col overflow-hidden">
    <ImpersonateBanner />
    <div class="relative flex flex-1 min-h-0 w-full overflow-hidden bg-background font-sans">
      <!-- 1. SIDEBAR (Warna Putih/Clean) -->
      <AdminSidebar />

      <!-- 2. MAIN INSET CONTENT -->
      <SidebarInset class="flex flex-1 min-h-0 flex-col overflow-hidden bg-background">
        <!-- Header Clean (Warna Putih) -->
        <AdminHeader />

        <!-- Main Content Area: Muted Container dengan Rounded Corners & Cross-Platform ScrollArea -->
        <div
          ref="containerRef"
          class="relative flex flex-1 flex-col min-h-0 p-4 pt-1 md:p-6 md:pt-1"
        >
          <ScrollArea class="flex-1 min-h-0 rounded-2xl bg-muted/60 dark:bg-muted/30 shadow-2xs">
            <main class="space-y-4 md:p-6">
              <!-- Slot Header dengan Default PageHeader -->
              <slot v-if="!hideHeader" name="header" :go-back="handleBack">
                <PageHeader
                  :title="title"
                  :subtitle="subtitle"
                  :icon="icon"
                  :show-back="showBack"
                  :back-url="backUrl"
                  :use-native="useNative"
                  :on-back="onBack"
                >
                  <template v-if="$slots['header-actions']" #actions>
                    <slot name="header-actions" :go-back="handleBack" :push="router.push" />
                  </template>
                </PageHeader>
              </slot>

              <!-- Default Slot untuk Konten Utama Halaman (dengan Scoped Slot goBack) -->
              <slot :go-back="handleBack" />
            </main>
          </ScrollArea>

          <!-- Floating Scroll To Top Shortcut Button -->
          <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
          >
            <div v-if="showScrollTop" class="absolute bottom-8 right-8 z-40">
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button
                      variant="default"
                      size="icon"
                      class="size-10 rounded-full shadow-lg hover:shadow-xl transition-all cursor-pointer bg-primary text-primary-foreground"
                      @click="scrollToTop"
                    >
                      <ArrowUp class="size-5" />
                      <span class="sr-only">Kembali ke atas</span>
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent side="left">
                    <p>Kembali ke atas</p>
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </div>
          </Transition>
        </div>
      </SidebarInset>
    </div>
  </SidebarProvider>
</template>
