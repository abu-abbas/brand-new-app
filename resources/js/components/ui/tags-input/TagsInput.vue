<script setup lang="ts">
import type { TagsInputRootEmits, TagsInputRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { TagsInputRoot, useForwardPropsEmits } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    TagsInputRootProps & {
      class?: HTMLAttributes['class'];
    }
  >(),
  {
    class: undefined,
  },
);

const emits = defineEmits<TagsInputRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <TagsInputRoot
    v-bind="forwarded"
    :class="
      cn(
        'flex flex-wrap items-center gap-1.5 rounded-md border border-input bg-background px-1.5 py-1.5 text-sm ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
        props.class,
      )
    "
  >
    <slot />
  </TagsInputRoot>
</template>
