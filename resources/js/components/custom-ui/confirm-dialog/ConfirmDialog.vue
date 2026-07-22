<script setup lang="ts">
import { computed } from 'vue';
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
  acceptConfirmDialog,
  confirmDialogState,
  dismissConfirmDialog,
  updateConfirmDialogOpen,
} from '@/composables/useConfirmDialog';

function actions(): ('cancel' | 'confirm')[] {
  return confirmDialogState.options.reverseActions ? ['confirm', 'cancel'] : ['cancel', 'confirm'];
}

const title = computed(() => {
  if (confirmDialogState.phase === 'success') return confirmDialogState.options.successTitle;
  if (confirmDialogState.phase === 'error') return confirmDialogState.options.errorTitle;
  return confirmDialogState.options.title;
});

const description = computed(() => {
  if (confirmDialogState.phase === 'success') {
    const countdown =
      confirmDialogState.options.successAutoCloseSeconds === false
        ? ''
        : ` Dialog akan tertutup otomatis dalam ${confirmDialogState.countdown} detik.`;

    return `${confirmDialogState.options.successDescription}${countdown}`;
  }

  if (confirmDialogState.phase === 'error') {
    const errorDescription = confirmDialogState.options.errorDescription;
    if (typeof errorDescription === 'function') return errorDescription(confirmDialogState.error);

    return errorDescription ?? 'Proses tidak dapat diselesaikan. Silakan coba lagi.';
  }

  return confirmDialogState.options.description;
});
</script>

<template>
  <AlertDialog :open="confirmDialogState.open" @update:open="updateConfirmDialogOpen">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ title }}</AlertDialogTitle>
        <AlertDialogDescription v-if="description" aria-live="polite">
          {{ description }}
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter v-if="confirmDialogState.phase === 'confirm'">
        <template v-for="action in actions()" :key="action">
          <AlertDialogCancel
            v-if="action === 'cancel' && confirmDialogState.options.cancelLabel !== false"
            :disabled="confirmDialogState.loading"
            @click="dismissConfirmDialog"
          >
            {{ confirmDialogState.options.cancelLabel }}
          </AlertDialogCancel>

          <Button
            v-if="action === 'confirm' && confirmDialogState.options.confirmLabel !== false"
            type="button"
            :variant="confirmDialogState.options.confirmVariant"
            :disabled="confirmDialogState.loading"
            @click="acceptConfirmDialog"
          >
            <Spinner v-if="confirmDialogState.loading" data-icon="inline-start" />
            {{
              confirmDialogState.loading
                ? confirmDialogState.options.loadingLabel
                : confirmDialogState.options.confirmLabel
            }}
          </Button>
        </template>
      </AlertDialogFooter>

      <AlertDialogFooter v-else>
        <Button
          type="button"
          :variant="confirmDialogState.phase === 'error' ? 'destructive' : 'default'"
          @click="dismissConfirmDialog"
        >
          {{ confirmDialogState.options.resultCloseLabel }}
          <template
            v-if="
              confirmDialogState.phase === 'success' &&
              confirmDialogState.options.successAutoCloseSeconds !== false
            "
          >
            ({{ confirmDialogState.countdown }})
          </template>
        </Button>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
