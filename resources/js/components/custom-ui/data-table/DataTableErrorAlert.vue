<script setup lang="ts">
import { AlertCircle, RefreshCw, MessageCircle } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CopyButton } from '@/components/custom-ui/copy-button';
import type { DataTableError } from './data-table.types';

defineProps<{
  error: DataTableError;
}>();

defineEmits<{
  retry: [];
}>();
</script>

<template>
  <div class="flex w-full max-w-lg flex-col items-center gap-3 p-4">
    <Alert
      v-if="!error.retryable"
      variant="destructive"
      class="w-full border-destructive/20 bg-destructive/4 text-left"
    >
      <AlertCircle class="size-4" />
      <div class="flex items-start justify-between gap-3">
        <AlertTitle class="mb-0 text-sm leading-snug font-medium text-foreground">
          {{ error.message }}
        </AlertTitle>
        <Badge
          v-if="error.code"
          variant="outline"
          class="shrink-0 font-mono text-[10px] text-muted-foreground"
        >
          {{ error.code }}
        </Badge>
      </div>

      <AlertDescription class="mt-2 space-y-2.5 text-xs">
        <div v-if="error.whatsappUrl" class="flex items-center gap-2 pt-1">
          <a
            :href="error.whatsappUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white transition-colors hover:bg-emerald-700 shadow-sm"
          >
            <MessageCircle class="size-3.5" />
            Hubungi Call Center via WhatsApp
          </a>
        </div>

        <ul
          v-if="error.validationErrors"
          class="divide-y divide-border/60 overflow-hidden rounded-md border border-border/60 bg-background/60"
        >
          <template v-for="(fieldErrors, field) in error.validationErrors" :key="field">
            <li
              v-for="(errItem, idx) in Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors]"
              :key="idx"
              class="flex items-start justify-between gap-2 px-2.5 py-1.5"
            >
              <span class="leading-relaxed">
                <span class="font-semibold text-foreground">{{ field }}</span>
                <span class="text-muted-foreground"> — </span>
                {{
                  typeof errItem === 'object' && errItem && 'message' in errItem
                    ? (errItem as any).message
                    : errItem
                }}
              </span>
              <Badge
                v-if="typeof errItem === 'object' && errItem && 'code' in errItem"
                variant="outline"
                class="shrink-0 font-mono text-[10px] text-muted-foreground"
              >
                {{ (errItem as any).code }}
              </Badge>
            </li>
          </template>
        </ul>

        <div
          v-if="error.supportId"
          class="flex items-center justify-between gap-2 font-mono text-2xs text-muted-foreground"
          :class="{
            'border-t border-border/60 pt-2': !error.validationErrors,
          }"
        >
          <span class="truncate">Support ID: {{ error.supportId }}</span>
          <CopyButton
            :text="error.supportId"
            :show-label="false"
            size="icon-xs"
            variant="ghost"
            class="shrink-0"
          />
        </div>

        <div
          v-if="error.requestId"
          class="flex items-center justify-between gap-2 font-mono text-2xs text-muted-foreground"
          :class="{
            'border-t border-border/60 pt-2': !error.validationErrors && !error.supportId,
          }"
        >
          <span class="truncate">Request ID: {{ error.requestId }}</span>
          <CopyButton
            :text="error.requestId"
            :show-label="false"
            size="icon-xs"
            variant="ghost"
            class="shrink-0"
          />
        </div>
      </AlertDescription>
    </Alert>

    <Alert v-else variant="destructive" class="w-full border-warning/20 bg-warning/4 text-left">
      <div class="flex w-full items-center gap-3">
        <AlertCircle class="size-4 shrink-0 text-destructive" />
        <div class="flex flex-col flex-1 gap-3">
          <AlertTitle class="mb-0 text-sm leading-snug font-medium text-foreground">
            <span
              v-if="error.code"
              class="shrink-0 font-mono font-semibold text-xs text-muted-foreground mr-1"
            >
              {{ error.code }}
            </span>
            {{ error.message }}
          </AlertTitle>
        </div>
        <Button
          variant="outline"
          size="sm"
          class="shrink-0 gap-1.5 border-destructive/30 hover:bg-destructive/10 hover:text-destructive"
          @click="$emit('retry')"
        >
          <RefreshCw class="size-3.5" />
          Coba lagi
        </Button>
      </div>
    </Alert>
  </div>
</template>
