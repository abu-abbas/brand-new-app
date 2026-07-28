<script setup lang="ts">
import type { TagsInputItemTextProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TagsInputItemText, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    TagsInputItemTextProps & {
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
  <TagsInputItemText v-bind="forwarded" :class="cn('py-0.5 text-xs', props.class)">
    <slot />
  </TagsInputItemText>
</template>
