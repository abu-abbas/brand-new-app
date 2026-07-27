<script setup lang="ts">
import { ref, type HTMLAttributes } from 'vue';
import { Check, Copy } from '@lucide/vue';
import { Button, type ButtonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<{
    text: string;
    label?: string;
    copiedLabel?: string;
    duration?: number;
    variant?: ButtonVariants['variant'];
    size?: ButtonVariants['size'];
    showLabel?: boolean;
    class?: HTMLAttributes['class'];
  }>(),
  {
    label: 'Salin',
    copiedLabel: 'Tersalin',
    duration: 2000,
    variant: 'ghost',
    size: 'sm',
    showLabel: true,
    class: undefined,
  },
);

const emit = defineEmits<{
  (e: 'copy', payload: { text: string; success: boolean }): void;
}>();

const isCopied = ref(false);
let timer: ReturnType<typeof window.setTimeout> | number | null = null;

async function handleCopy() {
  if (!props.text) return;

  try {
    if (typeof window !== 'undefined' && window.navigator?.clipboard) {
      await window.navigator.clipboard.writeText(props.text);
    }
    isCopied.value = true;
    emit('copy', { text: props.text, success: true });

    if (timer !== null && typeof window !== 'undefined') {
      window.clearTimeout(timer as number);
    }
    if (typeof window !== 'undefined') {
      timer = window.setTimeout(() => {
        isCopied.value = false;
      }, props.duration);
    }
  } catch {
    emit('copy', { text: props.text, success: false });
  }
}
</script>

<template>
  <Button
    :variant="variant"
    :size="size"
    :class="
      cn(
        'gap-1.5 font-medium transition-all text-xs',
        variant === 'ghost' && 'text-muted-foreground hover:text-foreground',
        isCopied && variant === 'ghost' && 'text-emerald-500 hover:text-emerald-600',
        isCopied && variant !== 'ghost' && 'bg-emerald-600 text-white hover:bg-emerald-700',
        props.class,
      )
    "
    @click="handleCopy"
  >
    <Check
      v-if="isCopied"
      :class="cn('size-3.5 shrink-0', variant === 'ghost' && 'text-emerald-500')"
    />
    <Copy v-else class="size-3.5 shrink-0" />
    <span v-if="showLabel">
      {{ isCopied ? copiedLabel : label }}
    </span>
  </Button>
</template>
