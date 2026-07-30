<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { getLucideIcon } from './lucide-icon.utils';

interface Props {
  name?: string | null;
  fallback?: string | Component;
  class?: HTMLAttributes['class'];
  fallbackClass?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  name: null,
  fallback: 'CircleDashed',
  class: undefined,
  fallbackClass: undefined,
});

const primaryComponent = computed(() => getLucideIcon(props.name, null));
const isFallback = computed(() => !primaryComponent.value);
const iconComponent = computed(() => primaryComponent.value ?? getLucideIcon(props.fallback, null));

const computedClass = computed(() => cn(props.class, isFallback.value && props.fallbackClass));
</script>

<template>
  <component :is="iconComponent" v-if="iconComponent" :class="computedClass" />
</template>
