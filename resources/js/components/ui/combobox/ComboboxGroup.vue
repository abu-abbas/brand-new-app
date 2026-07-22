<script setup lang="ts">
import type { ComboboxGroupProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ComboboxGroup, ComboboxLabel } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<
  ComboboxGroupProps & {
    class?: HTMLAttributes['class'];
    heading?: string;
  }
>();

const delegatedProps = reactiveOmit(props, 'class', 'heading');
</script>

<template>
  <ComboboxGroup
    data-slot="combobox-group"
    v-bind="delegatedProps"
    :class="cn('overflow-hidden text-foreground', props.class)"
  >
    <ComboboxLabel
      v-if="heading || $slots.heading"
      class="text-muted-foreground px-2 py-1.5 text-xs"
    >
      <slot name="heading">{{ heading }}</slot>
    </ComboboxLabel>
    <slot />
  </ComboboxGroup>
</template>
