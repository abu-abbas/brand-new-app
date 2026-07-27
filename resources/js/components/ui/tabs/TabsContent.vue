<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import type { TabsContentProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { TabsContent } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<TabsContentProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = reactiveOmit(props, 'class');
</script>

<template>
  <TabsContent
    v-bind="delegatedProps"
    :class="
      cn(
        'mt-2 ring-offset-background focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
        props.class,
      )
    "
  >
    <slot />
  </TabsContent>
</template>
