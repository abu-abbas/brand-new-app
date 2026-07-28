<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import type { SplitterPanelEmits, SplitterPanelProps } from 'reka-ui';
import { reactiveOmit } from '@vueuse/core';
import { SplitterPanel, useForwardPropsEmits } from 'reka-ui';

const props = defineProps<SplitterPanelProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<SplitterPanelEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <SplitterPanel v-bind="forwarded" :class="props.class">
    <slot />
  </SplitterPanel>
</template>
