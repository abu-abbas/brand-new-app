<script setup lang="ts">
import type { TagsInputInputProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TagsInputInput, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    TagsInputInputProps & {
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
  <TagsInputInput
    v-bind="forwarded"
    :class="
      cn(
        'flex-1 bg-transparent px-1 py-0.5 text-sm outline-none placeholder:text-muted-foreground focus:outline-none min-h-6',
        props.class,
      )
    "
  />
</template>
