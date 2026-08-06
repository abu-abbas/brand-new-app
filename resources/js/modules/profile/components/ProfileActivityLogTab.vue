<script setup lang="ts">
import { computed, ref } from 'vue';
import { useIntersectionObserver } from '@vueuse/core';
import { useProfileActivityLogsQuery } from '../queries/use-profile-activity-logs.query';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Loader2, ShieldAlert, History, RefreshCw, Laptop } from '@lucide/vue';
import type { ActivityLogResource } from '@/api/generated/api';

const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading, isError, refetch } =
  useProfileActivityLogsQuery(15);

const loadMoreTriggerRef = ref<HTMLElement | null>(null);

useIntersectionObserver(loadMoreTriggerRef, ([{ isIntersecting }]) => {
  if (isIntersecting && hasNextPage.value && !isFetchingNextPage.value) {
    fetchNextPage();
  }
});

const logs = computed<ActivityLogResource[]>(() => {
  if (!data.value?.pages) return [];
  return data.value.pages.flatMap((page) => page.data ?? []);
});

const totalLogs = computed(() => {
  return data.value?.pages[0]?.meta?.total ?? logs.value.length;
});

function getDateHeader(isoString?: string): string {
  if (!isoString) return 'LAINNYA';
  try {
    const d = new Date(isoString);
    return new Intl.DateTimeFormat('id-ID', {
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
      .format(d)
      .toUpperCase();
  } catch {
    return 'LAINNYA';
  }
}

function formatTime(isoString?: string): string {
  if (!isoString) return '';
  try {
    const d = new Date(isoString);
    return new Intl.DateTimeFormat('id-ID', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(d);
  } catch {
    return '';
  }
}

function getCauserText(log: ActivityLogResource): { causer: string; impersonator?: string } {
  if (log.is_impersonated && log.impersonator_name) {
    return {
      causer: log.impersonator_name,
      impersonator: log.causer_name,
    };
  }

  return {
    causer: log.causer_name || 'Sistem',
    impersonator: undefined,
  };
}

const groupedLogs = computed(() => {
  const groups: { dateLabel: string; items: ActivityLogResource[] }[] = [];
  const map = new Map<string, ActivityLogResource[]>();

  for (const log of logs.value) {
    const dateLabel = getDateHeader(log.created_at);
    let items = map.get(dateLabel);
    if (!items) {
      items = [];
      map.set(dateLabel, items);
      groups.push({ dateLabel, items });
    }
    items.push(log);
  }

  return groups;
});
</script>

<template>
  <div class="space-y-4 pb-6">
    <div class="flex flex-col space-y-4 rounded-2xl border border-border/50 bg-card p-5">
      <div class="flex justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-primary/10 text-primary shrink-0">
            <History class="size-5" />
          </div>
          <div>
            <h4 class="text-base font-bold text-foreground">
              Riwayat Aktivitas & Audit Trail
              <span class="text-muted-foreground"> (Total {{ totalLogs }} Entri) </span>
            </h4>
            <p class="text-sm text-muted-foreground">
              Catatan kronologis semua kejadian dan tindakan pada akun Anda.
            </p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Button variant="outline" size="sm" class="rounded-lg gap-1.5" @click="refetch()">
            <RefreshCw class="size-3.5 text-muted-foreground" />
            <span>Refresh</span>
          </Button>
        </div>
      </div>

      <!-- Area List Scroll dengan ScrollArea shadcn-vue -->
      <ScrollArea class="h-120 w-full p-5">
        <!-- Loading Initial -->
        <div
          v-if="isLoading"
          class="flex flex-col items-center justify-center py-20 text-muted-foreground"
        >
          <Loader2 class="size-8 animate-spin text-primary mb-3" />
          <span class="text-sm font-semibold text-foreground">Memuat riwayat log aktivitas...</span>
        </div>

        <!-- Error State -->
        <div
          v-else-if="isError"
          class="flex flex-col items-center justify-center py-16 text-destructive"
        >
          <ShieldAlert class="size-10 mb-2 stroke-1.5" />
          <span class="text-sm font-semibold">Gagal memuat log aktivitas.</span>
          <Button variant="outline" size="sm" class="mt-4 rounded-lg" @click="refetch()">
            Coba Lagi
          </Button>
        </div>

        <!-- Empty State -->
        <div
          v-else-if="logs.length === 0"
          class="flex flex-col items-center justify-center py-20 text-muted-foreground"
        >
          <History class="size-12 stroke-1 mb-2 opacity-40" />
          <span class="text-sm font-semibold text-foreground">Belum ada catatan aktivitas.</span>
        </div>

        <!-- Timeline List Grouped by Date -->
        <div v-else class="space-y-6">
          <div v-for="(group, groupIndex) in groupedLogs" :key="group.dateLabel" class="space-y-3">
            <!-- Date Header with Divider Line (Sticky) -->
            <div
              class="sticky top-0 z-10 -mx-5 px-5 flex items-center gap-4 py-2 bg-card/95 backdrop-blur-xs"
            >
              <span
                class="text-2sm font-bold tracking-wider text-muted-foreground uppercase shrink-0"
              >
                {{ group.dateLabel }}
              </span>
              <div class="h-px flex-1 bg-border/60"></div>
            </div>

            <!-- Timeline Items for this Date -->
            <div class="space-y-4 pl-1">
              <div v-for="(log, itemIndex) in group.items" :key="log.id" class="relative pl-6 pb-2">
                <!-- Connecting Line between dots -->
                <div
                  v-if="itemIndex < group.items.length - 1"
                  class="absolute left-[3.5px] top-3 -bottom-5 w-px border-l border-dashed border-border"
                ></div>

                <!-- Bullet Dot -->
                <div
                  v-if="groupIndex === 0 && itemIndex === 0"
                  class="absolute left-0 top-1.25 flex size-2 items-center justify-center"
                >
                  <span
                    class="absolute inline-flex size-full animate-ping rounded-full bg-primary/75 opacity-85"
                  />
                  <span
                    class="relative inline-flex size-2 rounded-full bg-primary dark:bg-primary-500 ring-4 ring-primary/10"
                  />
                </div>
                <div
                  v-else
                  class="absolute left-0 top-1.25 size-2 rounded-full bg-slate-300 dark:bg-slate-700"
                />

                <!-- Item Details Row -->
                <div class="flex items-start justify-between gap-4">
                  <div class="space-y-1">
                    <h5 class="text-sm font-medium text-foreground leading-none">
                      {{ log.title }}
                    </h5>
                    <p class="text-xs text-muted-foreground">
                      Oleh:
                      <span class="font-medium text-foreground">
                        {{ getCauserText(log).causer }}
                      </span>
                      <span
                        v-if="getCauserText(log).impersonator"
                        class="italic text-muted-foreground"
                      >
                        (sebagai {{ getCauserText(log).impersonator }})
                      </span>
                    </p>
                    <div
                      v-if="log.ip_address || log.request_id"
                      class="flex items-center gap-2 text-2sm text-muted-foreground pt-0.5"
                    >
                      <span v-if="log.ip_address" class="inline-flex items-center gap-1">
                        <Laptop class="size-3 text-muted-foreground/80" />
                        IP: {{ log.ip_address }}
                      </span>
                      <span v-if="log.ip_address && log.request_id">•</span>
                      <span v-if="log.request_id">Req ID: {{ log.request_id.slice(0, 8) }}...</span>
                    </div>
                  </div>

                  <span class="text-xs text-muted-foreground/80 shrink-0 font-medium pt-0.5">
                    {{ formatTime(log.created_at) }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Sentinel Trigger & Loading Animation for Lazy Load -->
          <div
            ref="loadMoreTriggerRef"
            class="py-4 flex flex-col items-center justify-center min-h-12"
          >
            <div
              v-if="isFetchingNextPage"
              class="flex items-center gap-2 text-xs font-medium text-muted-foreground animate-pulse py-2"
            >
              <Loader2 class="size-4 animate-spin text-primary" />
              <span>Memuat aktivitas berikutnya...</span>
            </div>

            <span
              v-else-if="!hasNextPage && logs.length > 0"
              class="text-xs text-muted-foreground font-medium italic py-2"
            >
              Semua catatan log aktivitas telah dimuat.
            </span>
          </div>
        </div>
      </ScrollArea>
    </div>
  </div>
</template>
