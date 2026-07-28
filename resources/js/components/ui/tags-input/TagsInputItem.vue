<script setup lang="ts">
import type { TagsInputItemProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TagsInputItem, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    TagsInputItemProps & {
      class?: HTMLAttributes['class'];
    }
  >(),
  {
    class: undefined,
  },
);

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardProps(delegatedProps);
</script>

<template>
  <TagsInputItem
    v-bind="forwarded"
    :class="
      cn(
        'flex h-6 items-center gap-1 rounded-sm bg-secondary px-2 text-xs font-medium text-secondary-foreground data-highlighted:bg-secondary/80',
        props.class,
      )
    "
  >
    <slot />
  </TagsInputItem>
</template>
