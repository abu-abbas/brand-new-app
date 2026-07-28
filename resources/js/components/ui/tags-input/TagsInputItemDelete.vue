<script setup lang="ts">
import type { TagsInputItemDeleteProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { X } from '@lucide/vue';
import { TagsInputItemDelete, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    TagsInputItemDeleteProps & {
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
  <TagsInputItemDelete
    v-bind="forwarded"
    :class="cn('flex rounded-xs opacity-70 transition-opacity hover:opacity-100', props.class)"
  >
    <slot>
      <X class="size-3" />
    </slot>
  </TagsInputItemDelete>
</template>
