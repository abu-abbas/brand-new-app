<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { NavigationMenuList, type NavigationMenuListProps, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps<NavigationMenuListProps & { class?: HTMLAttributes['class'] }>();

const delegatedProps = computed(() => {
  const delegated = { ...props };
  delete delegated.class;
  return delegated;
});

const forwarded = useForwardProps(delegatedProps);
</script>

<template>
  <NavigationMenuList
    v-bind="forwarded"
    :class="cn('group flex flex-1 list-none items-center justify-center space-x-1', props.class)"
  >
    <slot />
  </NavigationMenuList>
</template>
