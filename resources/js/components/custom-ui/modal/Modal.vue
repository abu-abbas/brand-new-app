<script setup lang="ts">
/* global Event, SubmitEvent */
import type { ButtonVariants } from '@/components/ui/button';
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | 'full';

export interface ModalProps {
  /** Controller state v-model:open */
  open?: boolean;
  /** Modal title string (bisa disesuaikan lewat slot #title) */
  title?: string;
  /** Modal description string (bisa disesuaikan lewat slot #description) */
  description?: string;
  /** Ukuran modal preset */
  size?: ModalSize;
  /** Tampilkan pembungkus elemen <form> otomatis */
  asForm?: boolean;
  /** Loading status - mendisable tombol & menampilkan spinner pada tombol Confirm */
  loading?: boolean;
  /** Disable tombol Confirm eksplisit */
  confirmDisabled?: boolean;
  /** Label tombol Confirm */
  confirmText?: string;
  /** Label tombol Cancel */
  cancelText?: string;
  /** Variant style tombol confirm */
  confirmVariant?: NonNullable<ButtonVariants['variant']>;
  /** Sembunyikan bagian footer seluruhnya */
  hideFooter?: boolean;
  /** Sembunyikan tombol Cancel */
  hideCancel?: boolean;
  /** Sembunyikan tombol Confirm */
  hideConfirm?: boolean;
  /** Menyembunyikan tombol close (X) di pojok kanan atas */
  hideCloseButton?: boolean;
  /** Menentukan apakah modal bisa ditutup saat klik area luar backdrop (default: true) */
  closeOnInteractOutside?: boolean;
  /** Custom class untuk DialogContent body container */
  contentClass?: HTMLAttributes['class'];
  /** Custom class untuk DialogBody inner scrollable container */
  bodyClass?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<ModalProps>(), {
  open: undefined,
  title: undefined,
  description: undefined,
  size: 'md',
  asForm: false,
  loading: false,
  confirmDisabled: false,
  confirmText: 'Simpan',
  cancelText: 'Batal',
  confirmVariant: 'default',
  hideFooter: false,
  hideCancel: false,
  hideConfirm: false,
  hideCloseButton: false,
  closeOnInteractOutside: true,
  contentClass: undefined,
  bodyClass: undefined,
});

const emits = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm', event: MouseEvent | SubmitEvent | Event): void;
  (e: 'cancel', event: MouseEvent | Event): void;
  (e: 'close'): void;
}>();

// Handling v-model:open binding
const isOpen = computed({
  get() {
    return props.open;
  },
  set(value: boolean) {
    emits('update:open', value);
    if (!value) {
      emits('close');
    }
  },
});

// Map size preset ke class Tailwind max-width
const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'sm:max-w-sm';
    case 'md':
      return 'sm:max-w-md';
    case 'lg':
      return 'sm:max-w-lg';
    case 'xl':
      return 'sm:max-w-xl';
    case '2xl':
      return 'sm:max-w-2xl';
    case '3xl':
      return 'sm:max-w-3xl';
    case '4xl':
      return 'sm:max-w-4xl';
    case '5xl':
      return 'sm:max-w-5xl';
    case 'full':
      return 'sm:max-w-[calc(100vw-2rem)] sm:h-[calc(100vh-2rem)] max-h-[calc(100vh-2rem)] flex flex-col justify-between';
    default:
      return 'sm:max-w-md';
  }
});

const handleConfirm = (event: MouseEvent | SubmitEvent | Event) => {
  if (props.loading || props.confirmDisabled) return;
  emits('confirm', event);
};

const handleCancel = (event: MouseEvent | Event) => {
  if (props.loading) return;
  emits('cancel', event);
  isOpen.value = false;
};

const handleInteractOutside = (event: Event) => {
  if (!props.closeOnInteractOutside || props.loading) {
    event.preventDefault();
  }
};
</script>

<template>
  <Dialog v-model:open="isOpen">
    <!-- Trigger Slot bila ada -->
    <DialogTrigger v-if="$slots.trigger" as-child>
      <slot name="trigger" />
    </DialogTrigger>

    <DialogContent
      :hide-close-button="hideCloseButton || loading"
      :class="cn('gap-0 p-0 overflow-hidden', sizeClasses, props.contentClass)"
      @interact-outside="handleInteractOutside"
      @escape-key-down="handleInteractOutside"
    >
      <!-- Wrap dengan form jika asForm true -->
      <component
        :is="asForm ? 'form' : 'div'"
        class="flex flex-col flex-1 max-h-[calc(100vh-3rem)] overflow-hidden"
        @submit.prevent="handleConfirm"
      >
        <!-- Header Section -->
        <DialogHeader
          v-if="$slots.header || title || description || $slots.title || $slots.description"
          class="p-5 pb-4 border-b border-border/50 text-left flex gap-0.5"
        >
          <slot name="header">
            <DialogTitle v-if="title || $slots.title" class="font-semibold leading-snug">
              <slot name="title">{{ title }}</slot>
            </DialogTitle>
            <DialogDescription v-if="description || $slots.description" class="text-2sm">
              <slot name="description">{{ description }}</slot>
            </DialogDescription>
          </slot>
        </DialogHeader>

        <!-- Body / Content Section -->
        <ScrollArea :class="cn('flex-1 min-h-0 text-sm text-foreground/90', props.bodyClass)">
          <div class="p-6 space-y-4">
            <slot />
          </div>
        </ScrollArea>

        <!-- Footer Section -->
        <DialogFooter
          v-if="!hideFooter && ($slots.footer || $slots.actions || !hideCancel || !hideConfirm)"
          class="p-4 px-6 bg-muted/30 border-t border-border/50 flex flex-col-reverse sm:flex-row sm:justify-end gap-2"
        >
          <slot name="footer">
            <slot name="actions">
              <!-- Cancel Button -->
              <Button
                v-if="!hideCancel"
                type="button"
                variant="outline"
                :disabled="loading"
                @click="handleCancel"
              >
                {{ cancelText }}
              </Button>

              <!-- Confirm Button -->
              <Button
                v-if="!hideConfirm"
                :type="asForm ? 'submit' : 'button'"
                :variant="confirmVariant"
                :disabled="loading || confirmDisabled"
                @click="!asForm ? handleConfirm($event) : undefined"
              >
                <Spinner v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
                <span>{{ confirmText }}</span>
              </Button>
            </slot>
          </slot>
        </DialogFooter>
      </component>
    </DialogContent>
  </Dialog>
</template>
