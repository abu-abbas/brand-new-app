<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

interface Props {
  class?: string;
  size?: number;
  duration?: number;
  borderWidth?: number;
  colorFrom?: string;
  colorTo?: string;
  delay?: number;
}

const props = withDefaults(defineProps<Props>(), {
  size: 300,
  duration: 8,
  borderWidth: 2,
  colorFrom: 'var(--primary)',
  colorTo: 'var(--chart-2)',
  delay: 0,
});

const style = computed(() => ({
  '--size': `${props.size}px`,
  '--duration': `${props.duration}s`,
  '--border-width': `${props.borderWidth}px`,
  '--color-from': props.colorFrom,
  '--color-to': props.colorTo,
  '--delay': `-${props.delay}s`,
}));
</script>

<template>
  <div
    :style="style"
    :class="
      cn(
        'border-beam-container pointer-events-none absolute inset-0 rounded-[inherit]',
        props.class,
      )
    "
  >
    <div class="border-beam-spinner" />
  </div>
</template>

<style scoped>
.border-beam-container {
  padding: var(--border-width);
  -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  mask-composite: exclude;
  overflow: hidden;
}

.border-beam-spinner {
  position: absolute;
  top: 50%;
  left: 50%;
  width: calc(100% + var(--size));
  height: calc(100% + var(--size));
  transform: translate(-50%, -50%);
  animation: border-beam-spin var(--duration) linear infinite;
  animation-delay: var(--delay);
  background: conic-gradient(
    from 0deg,
    transparent 0deg,
    transparent 280deg,
    var(--color-from) 320deg,
    var(--color-to) 360deg
  );
}

@keyframes border-beam-spin {
  from {
    transform: translate(-50%, -50%) rotate(0deg);
  }
  to {
    transform: translate(-50%, -50%) rotate(360deg);
  }
}
</style>
