<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();
</script>

<template>
  <div
    data-slot="alert-description"
    :class="cn('text-sm [&_p]:leading-relaxed text-muted-foreground', props.class)"
  >
    <slot />
  </div>
</template>
