<script setup lang="ts">
import { computed, h, type VNode } from 'vue';

const props = withDefaults(
  defineProps<{
    content?: string;
    as?: string;
  }>(),
  {
    content: '',
    as: 'span',
  },
);

const vnodes = computed<Array<VNode | string>>(() => {
  const text = props.content;
  if (!text) return [];

  // Regex tokenizing for inline code, bold, italic, and markdown links
  const regex = /(`[^`]+`|\*\*[^*]+\*\*|\*[^*]+\*|\[[^\]]+\]\([^)]+\))/g;
  const parts = text.split(regex);

  return parts.map((part, index) => {
    // Inline code `code`
    if (part.startsWith('`') && part.endsWith('`') && part.length > 2) {
      return h(
        'code',
        {
          key: index,
          class:
            'relative rounded bg-muted px-1.5 py-0.5 font-mono text-xs font-semibold text-foreground',
        },
        part.slice(1, -1),
      );
    }

    // Bold **bold**
    if (part.startsWith('**') && part.endsWith('**') && part.length > 4) {
      return h('strong', { key: index, class: 'font-semibold text-foreground' }, part.slice(2, -2));
    }

    // Italic *italic*
    if (part.startsWith('*') && part.endsWith('*') && part.length > 2) {
      return h('em', { key: index, class: 'italic' }, part.slice(1, -1));
    }

    // Markdown Link [label](url)
    const linkMatch = /^\[([^\]]+)\]\(([^)]+)\)$/.exec(part);
    if (linkMatch) {
      const [, label, url] = linkMatch;
      return h(
        'a',
        {
          key: index,
          href: url,
          target: '_blank',
          rel: 'noopener noreferrer',
          class: 'font-medium text-primary underline underline-offset-4 hover:text-primary/80',
        },
        label,
      );
    }

    return part;
  });
});
</script>

<template>
  <component :is="as">
    <template v-for="(node, index) in vnodes" :key="index">
      <component :is="node" v-if="typeof node === 'object'" />
      <template v-else>{{ node }}</template>
    </template>
  </component>
</template>
