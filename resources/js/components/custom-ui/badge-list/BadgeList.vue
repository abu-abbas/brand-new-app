<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

export type BadgeListItem =
  string | { alias?: string; name?: string; label?: string; key?: string };

const props = withDefaults(
  defineProps<{
    items?: BadgeListItem[] | null;
    search?: string;
    labelMap?: Record<string, string>;
    max?: number;
    emptyText?: string;
  }>(),
  {
    items: () => [],
    search: '',
    labelMap: () => ({}),
    max: 10,
    emptyText: '-',
  },
);

function getItemKey(item: BadgeListItem): string {
  if (typeof item === 'string') return item;
  return item.alias || item.key || item.name || item.label || '';
}

function getLabel(item: BadgeListItem): string {
  if (typeof item === 'string') {
    return props.labelMap?.[item] || item;
  }
  return item.name || item.label || item.alias || item.key || '';
}

function isMatching(item: BadgeListItem): boolean {
  if (!props.search || !props.search.trim()) return false;
  const q = props.search.trim().toLowerCase();
  const label = getLabel(item).toLowerCase();
  const key = getItemKey(item).toLowerCase();
  return label.includes(q) || key.includes(q);
}

const orderedItems = computed(() => {
  const list = props.items || [];
  if (!props.search || !props.search.trim()) return list;

  const matching: BadgeListItem[] = [];
  const nonMatching: BadgeListItem[] = [];

  for (const item of list) {
    if (isMatching(item)) {
      matching.push(item);
    } else {
      nonMatching.push(item);
    }
  }

  return [...matching, ...nonMatching];
});

const visibleItems = computed(() => orderedItems.value.slice(0, props.max));
const remainingCount = computed(() => Math.max(0, (props.items?.length || 0) - props.max));
</script>

<template>
  <div v-if="props.items && props.items.length > 0" class="flex flex-wrap gap-1">
    <Badge
      v-for="item in visibleItems"
      :key="getItemKey(item)"
      :variant="isMatching(item) ? 'default' : 'secondary'"
      :class="
        isMatching(item)
          ? 'bg-primary text-primary-foreground font-medium shadow-2xs'
          : 'text-2sm px-1.5 py-0.5 font-normal'
      "
    >
      {{ getLabel(item) }}
    </Badge>
    <Badge
      v-if="remainingCount > 0"
      variant="outline"
      class="text-xs px-1.5 py-0.5 font-semibold text-muted-foreground"
    >
      +{{ remainingCount }}..
    </Badge>
  </div>
  <span v-else class="text-xs text-muted-foreground/60">{{ props.emptyText }}</span>
</template>
