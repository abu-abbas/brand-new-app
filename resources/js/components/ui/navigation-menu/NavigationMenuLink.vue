<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import {
  NavigationMenuLink,
  type NavigationMenuLinkEmits,
  type NavigationMenuLinkProps,
  useForwardPropsEmits,
} from 'reka-ui';

const props = defineProps<NavigationMenuLinkProps & { class?: HTMLAttributes['class'] }>();
const emits = defineEmits<NavigationMenuLinkEmits>();

const delegatedProps = computed(() => {
  const { class: _class, ...delegated } = props;
  void _class;
  return delegated;
});

const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <NavigationMenuLink v-bind="forwarded" :class="props.class">
    <slot />
  </NavigationMenuLink>
</template>
