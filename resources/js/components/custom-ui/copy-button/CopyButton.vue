<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { Check, Copy } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
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

const { copy, copied: isCopied } = useClipboard({
  copiedDuring: props.duration,
  legacy: true,
});

async function handleCopy() {
  if (!props.text) return;
  try {
    await copy(props.text);
    emit('copy', { text: props.text, success: true });
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
        // State default untuk varian ghost
        variant === 'ghost' && !isCopied && 'text-muted-foreground hover:text-foreground',
        // State copied untuk varian ghost / outline / link
        isCopied &&
          (variant === 'ghost' || variant === 'outline' || variant === 'link') &&
          'text-primary font-semibold border-primary/40 bg-primary/10 hover:bg-primary/15',
        // State copied untuk varian solid (default, secondary, destructive)
        isCopied &&
          variant !== 'ghost' &&
          variant !== 'outline' &&
          variant !== 'link' &&
          'bg-primary text-primary-foreground font-semibold hover:bg-primary/90',
        props.class,
      )
    "
    @click="handleCopy"
  >
    <Check
      v-if="isCopied"
      :class="
        cn(
          'size-3.5 shrink-0',
          (variant === 'ghost' || variant === 'outline' || variant === 'link') && 'text-primary',
          variant !== 'ghost' &&
            variant !== 'outline' &&
            variant !== 'link' &&
            'text-primary-foreground',
        )
      "
    />
    <Copy v-else class="size-3.5 shrink-0" />
    <span v-if="showLabel">
      {{ isCopied ? copiedLabel : label }}
    </span>
  </Button>
</template>
