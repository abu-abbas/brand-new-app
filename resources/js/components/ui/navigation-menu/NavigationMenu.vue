<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import {
  NavigationMenuRoot,
  type NavigationMenuRootEmits,
  type NavigationMenuRootProps,
  useForwardPropsEmits,
} from 'reka-ui';
import NavigationMenuViewport from './NavigationMenuViewport.vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<NavigationMenuRootProps & { class?: HTMLAttributes['class'] }>(),
  { class: '' },
);
const emits = defineEmits<NavigationMenuRootEmits>();

const delegatedProps = computed(() => {
  const delegated = { ...props };
  delete delegated.class;
  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <NavigationMenuRoot
    v-bind="forwarded"
    :class="cn('relative z-10 flex max-w-max flex-1 items-center justify-center', props.class)"
  >
    <slot />
    <NavigationMenuViewport />
  </NavigationMenuRoot>
</template>
