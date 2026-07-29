<script setup lang="ts">
/* global Event */
import { computed, type Component } from 'vue';
import { useRoute, useRouter, type RouteLocationRaw } from 'vue-router';
import { ArrowLeft, FileCode2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

const props = defineProps<{
  title?: string;
  subtitle?: string;
  icon?: Component;
  showBack?: boolean;
  backUrl?: RouteLocationRaw;
  useNative?: boolean;
  onBack?: (customTarget?: RouteLocationRaw | Event) => void;
}>();

const route = useRoute();
const router = useRouter();

const resolvedTitle = computed(
  () => props.title ?? (route.meta?.title as string | undefined) ?? 'Halaman Kosong',
);
const resolvedSubtitle = computed(
  () => props.subtitle ?? (route.meta?.subtitle as string | undefined),
);
const resolvedIcon = computed<Component | undefined>(
  () => props.icon ?? (route.meta?.icon as Component | undefined) ?? FileCode2,
);
const resolvedBackUrl = computed<RouteLocationRaw | undefined>(
  () => props.backUrl ?? (route.meta?.backUrl as RouteLocationRaw | undefined),
);

const isBackActive = computed(() =>
  Boolean(props.showBack || props.onBack || resolvedBackUrl.value),
);

function handleBack(customTarget?: RouteLocationRaw | Event) {
  if (props.onBack) {
    props.onBack(customTarget);
    return;
  }

  const isExplicit =
    Boolean(customTarget) &&
    !(typeof window !== 'undefined' && customTarget instanceof Event) &&
    !(
      typeof customTarget === 'object' &&
      customTarget !== null &&
      'preventDefault' in customTarget
    );

  const rawTarget = isExplicit ? (customTarget as RouteLocationRaw) : resolvedBackUrl.value;

  if (rawTarget) {
    if (props.useNative && typeof rawTarget === 'string') {
      window.location.href = rawTarget;
      return;
    }

    if (typeof rawTarget === 'string' && router.hasRoute(rawTarget)) {
      router.push({ name: rawTarget });
    } else {
      router.push(rawTarget);
    }
  } else {
    router.back();
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center justify-between gap-4">
    <!-- Left Section: Icon / Back Button & Title/Subtitle -->
    <div class="flex items-center gap-3 min-w-0">
      <!-- Icon Container / Back Button Slot -->
      <slot name="icon">
        <!-- Back Button Mode: Ghost Variant dengan Icon & Teks "Kembali" (Tanpa Title & Subtitle) -->
        <Button v-if="isBackActive" variant="ghost" aria-label="Kembali" @click="handleBack">
          <ArrowLeft class="size-4" />
          <span>Kembali</span>
        </Button>

        <!-- Default Icon Badge (Ditampilkan jika tidak dalam mode Back Button) -->
        <div
          v-else-if="resolvedIcon"
          class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-2xs"
        >
          <component :is="resolvedIcon" class="size-5" />
        </div>
      </slot>

      <!-- Text Container: Hanya ditampilkan jika tidak dalam mode Back Button -->
      <div v-if="!isBackActive" class="space-y-0.5 min-w-0">
        <h1 class="text-lg font-bold tracking-tight leading-snug text-foreground truncate">
          <slot name="title">{{ resolvedTitle }}</slot>
        </h1>
        <p
          v-if="resolvedSubtitle || $slots.subtitle"
          class="text-2sm text-muted-foreground leading-normal truncate"
        >
          <slot name="subtitle">{{ resolvedSubtitle }}</slot>
        </p>
      </div>
    </div>

    <!-- Right Section: Actions Slot -->
    <div v-if="$slots.actions" class="flex items-center gap-2.5 shrink-0">
      <slot name="actions" :go-back="handleBack" />
    </div>
  </div>
</template>
