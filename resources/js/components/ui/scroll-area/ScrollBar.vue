<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';
import { ScrollAreaScrollbar, type ScrollAreaScrollbarProps, ScrollAreaThumb } from 'reka-ui';

const props = withDefaults(
  defineProps<ScrollAreaScrollbarProps & { class?: HTMLAttributes['class'] }>(),
  {
    orientation: 'vertical',
  },
);
</script>

<template>
  <ScrollAreaScrollbar
    v-bind="props"
    :class="
      cn(
        'flex touch-none select-none transition-colors duration-150 ease-out',
        orientation === 'vertical' && 'h-full w-2 border-l border-l-transparent p-0.5',
        orientation === 'horizontal' && 'h-2 flex-col border-t border-t-transparent p-0.5',
        props.class,
      )
    "
  >
    <ScrollAreaThumb
      class="relative flex-1 rounded-full bg-muted-foreground/30 hover:bg-muted-foreground/50 transition-colors"
    />
  </ScrollAreaScrollbar>
</template>
