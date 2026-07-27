<script setup lang="ts">
/* global IntersectionObserver */
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { BookOpen, List, CheckSquare, Square, ChevronRight, Hash } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { MarkdownText } from '@/components/custom-ui/markdown-text';
import { CopyButton } from '@/components/custom-ui/copy-button';

const props = defineProps<{
  content: string;
}>();

interface CodeBlockNode {
  type: 'code';
  lang: string;
  code: string;
}

interface HeadingNode {
  type: 'heading';
  level: 1 | 2 | 3 | 4;
  text: string;
  id: string;
}

interface ListItem {
  text: string;
  checked?: boolean;
  number?: number;
}

interface ListNode {
  type: 'list';
  items: ListItem[];
}

interface ParagraphNode {
  type: 'paragraph';
  text: string;
}

interface QuoteNode {
  type: 'quote';
  text: string;
}

type DocNode = CodeBlockNode | HeadingNode | ListNode | ParagraphNode | QuoteNode;

function slugify(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^\w\s-]/g, '')
    .trim()
    .replace(/\s+/g, '-');
}

const parsedDoc = computed(() => {
  const lines = props.content.split('\n');
  const nodes: DocNode[] = [];
  const toc: Array<{ id: string; title: string; level: number }> = [];

  let inCodeBlock = false;
  let codeLang = '';
  let codeBuffer: string[] = [];

  let currentListItems: ListItem[] = [];

  const flushList = () => {
    if (currentListItems.length > 0) {
      nodes.push({ type: 'list', items: [...currentListItems] });
      currentListItems = [];
    }
  };

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];

    // Code block start/end
    if (line.trim().startsWith('```')) {
      flushList();
      if (inCodeBlock) {
        // Close code block
        nodes.push({
          type: 'code',
          lang: codeLang || 'text',
          code: codeBuffer.join('\n'),
        });
        inCodeBlock = false;
        codeBuffer = [];
        codeLang = '';
      } else {
        inCodeBlock = true;
        codeLang = line.trim().slice(3).trim();
      }
      continue;
    }

    if (inCodeBlock) {
      codeBuffer.push(line);
      continue;
    }

    // Empty line (reset list context)
    if (!line.trim()) {
      flushList();
      continue;
    }

    // Headings
    if (line.startsWith('#')) {
      flushList();
      const headingMatch = line.match(/^(#{1,4})\s+(.*)$/);
      if (headingMatch) {
        const level = headingMatch[1].length as 1 | 2 | 3 | 4;
        const text = headingMatch[2].trim();
        const id = slugify(text);

        if (level === 2 || level === 3) {
          toc.push({ id, title: text, level });
        }

        nodes.push({ type: 'heading', level, text, id });
        continue;
      }
    }

    // Blockquote
    if (line.startsWith('>')) {
      flushList();
      const quoteText = line.replace(/^>\s?/, '').trim();
      const lastNode = nodes[nodes.length - 1];
      if (lastNode && lastNode.type === 'quote') {
        lastNode.text += '\n' + quoteText;
      } else {
        nodes.push({ type: 'quote', text: quoteText });
      }
      continue;
    }

    // List item (bullet list `-`/`*` atau numbered list `1.`/`1)`)
    const listMatch = line.match(/^(\s*)(?:([-*])|(\d+)[.)])\s+(.*)$/);
    if (listMatch) {
      const num = listMatch[3] ? parseInt(listMatch[3], 10) : undefined;
      const content = listMatch[4].trim();
      if (content.startsWith('[x] ') || content.startsWith('[X] ')) {
        currentListItems.push({ text: content.slice(4), checked: true });
      } else if (content.startsWith('[ ] ')) {
        currentListItems.push({ text: content.slice(4), checked: false });
      } else {
        currentListItems.push({ text: content, number: num });
      }
      continue;
    }

    // Continuation of an active list item
    if (currentListItems.length > 0) {
      currentListItems[currentListItems.length - 1].text += ' ' + line.trim();
      continue;
    }

    // Paragraph (append to previous paragraph if non-empty, or push new paragraph)
    const lastNode = nodes[nodes.length - 1];
    if (lastNode && lastNode.type === 'paragraph') {
      lastNode.text += ' ' + line.trim();
    } else {
      nodes.push({ type: 'paragraph', text: line.trim() });
    }
  }

  flushList();

  return { nodes, toc };
});

const tocIdSet = computed(() => new Set(parsedDoc.value.toc.map((item) => item.id)));

const activeHeadingId = ref<string>('');

function scrollToSection(id: string) {
  if (typeof document !== 'undefined') {
    const el = document.getElementById(id);
    if (el) {
      activeHeadingId.value = id;
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

let observer: IntersectionObserver | null = null;

onMounted(() => {
  if (typeof window === 'undefined' || !window.IntersectionObserver) return;

  observer = new window.IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          activeHeadingId.value = entry.target.id;
        }
      });
    },
    {
      rootMargin: '-80px 0px -70% 0px',
      threshold: 0.1,
    },
  );

  parsedDoc.value.toc.forEach((item) => {
    const el = document.getElementById(item.id);
    if (el) observer?.observe(el);
  });
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
    observer = null;
  }
});
</script>

<template>
  <div class="flex flex-col gap-6">
    <!-- Header Banner -->
    <Card class="border-primary/20 bg-card shadow-xs">
      <CardHeader>
        <CardTitle class="flex items-center gap-2 text-lg">
          <BookOpen class="size-5 text-primary" />
          Dokumentasi & Spesifikasi DataTable Reusable
        </CardTitle>
        <CardDescription>
          Source of truth lengkap mengenai kontrak API, props, events, mode data (lokal & server),
          handling error EDF, serta fitur tree & selection.
        </CardDescription>
      </CardHeader>
    </Card>

    <!-- Main Content Layout with Sticky Sidebar -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12 items-start">
      <!-- Main Document Area -->
      <main class="min-w-0 lg:col-span-8 xl:col-span-9">
        <Card class="border-border bg-card shadow-xs pt-0">
          <CardContent class="p-6 md:p-8 space-y-4">
            <template v-for="(node, index) in parsedDoc.nodes" :key="index">
              <!-- Headings -->
              <div v-if="node.type === 'heading'" :id="node.id" class="scroll-mt-20">
                <h1
                  v-if="node.level === 1"
                  class="text-2xl font-bold tracking-tight text-foreground pb-2 border-b border-border flex items-center gap-2.5"
                >
                  <Hash v-if="tocIdSet.has(node.id)" class="size-6 text-primary shrink-0" />
                  <div class="flex-1 min-w-0">
                    <MarkdownText :content="node.text" />
                  </div>
                </h1>
                <h2
                  v-else-if="node.level === 2"
                  class="text-xl font-semibold tracking-tight text-foreground mt-8 mb-3 pt-4 border-t border-border/60 flex items-center gap-2"
                >
                  <Hash v-if="tocIdSet.has(node.id)" class="size-5 text-primary shrink-0" />
                  <div class="flex-1 min-w-0">
                    <MarkdownText :content="node.text" />
                  </div>
                </h2>
                <h3
                  v-else-if="node.level === 3"
                  class="text-base font-semibold text-foreground mt-6 mb-2 flex items-center gap-2"
                >
                  <Hash v-if="tocIdSet.has(node.id)" class="size-4 text-primary shrink-0" />
                  <div class="flex-1 min-w-0">
                    <MarkdownText :content="node.text" />
                  </div>
                </h3>
                <h4
                  v-else
                  class="text-sm font-semibold text-foreground/90 mt-4 mb-1 flex items-center gap-2"
                >
                  <Hash v-if="tocIdSet.has(node.id)" class="size-3.5 text-primary shrink-0" />
                  <div class="flex-1 min-w-0">
                    <MarkdownText :content="node.text" />
                  </div>
                </h4>
              </div>

              <!-- Code Blocks -->
              <div
                v-else-if="node.type === 'code'"
                class="relative group rounded-xl border border-border bg-muted/60 overflow-hidden my-4"
              >
                <div
                  class="flex items-center justify-between px-4 py-1.5 bg-muted/80 border-b border-border text-xs font-mono text-muted-foreground"
                >
                  <span>{{ node.lang || 'code' }}</span>
                  <CopyButton :text="node.code" class="h-6 px-2 text-[11px]" />
                </div>
                <pre
                  class="p-4 font-mono text-xs leading-relaxed overflow-x-auto text-foreground select-text"
                ><code>{{ node.code }}</code></pre>
              </div>

              <!-- Lists -->
              <ul v-else-if="node.type === 'list'" class="space-y-1.5 my-2 pl-2">
                <li
                  v-for="(item, itemIdx) in node.items"
                  :key="itemIdx"
                  class="text-sm text-foreground/90 flex items-start gap-2"
                >
                  <template v-if="item.checked !== undefined">
                    <CheckSquare v-if="item.checked" class="size-4 text-primary shrink-0 mt-0.5" />
                    <Square v-else class="size-4 text-muted-foreground/60 shrink-0 mt-0.5" />
                  </template>
                  <span
                    v-else-if="item.number !== undefined"
                    class="font-mono text-xs text-muted-foreground shrink-0 mt-0.5 min-w-4 text-right"
                  >
                    {{ item.number }}.
                  </span>
                  <span v-else class="size-1.5 rounded-full bg-primary/70 shrink-0 mt-2"></span>
                  <div class="flex-1 min-w-0">
                    <MarkdownText :content="item.text" />
                  </div>
                </li>
              </ul>

              <!-- Blockquote -->
              <div
                v-else-if="node.type === 'quote'"
                class="border-l-4 px-4 py-3 rounded-r-md text-sm my-3 whitespace-pre-line"
                :class="
                  node.text.includes('[!WARNING]')
                    ? 'border-amber-500 bg-amber-500/10 text-amber-950 dark:text-amber-200 font-medium'
                    : 'border-primary/50 bg-primary/5 italic text-foreground/90'
                "
              >
                <MarkdownText :content="node.text.replace(/\[!WARNING\]\s*/gi, '')" />
              </div>

              <!-- Paragraph -->
              <p
                v-else-if="node.type === 'paragraph'"
                class="text-sm leading-relaxed text-foreground/90 my-2"
              >
                <MarkdownText :content="node.text" />
              </p>
            </template>
          </CardContent>
        </Card>
      </main>

      <!-- Sticky Table of Contents Sidebar (Right Side) -->
      <aside
        v-if="parsedDoc.toc.length > 0"
        class="sticky top-6 hidden lg:block lg:col-span-4 xl:col-span-3 space-y-3"
      >
        <Card class="border-border bg-card shadow-xs">
          <CardHeader class="px-4 py-0">
            <CardTitle
              class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
            >
              <List class="size-3.5 text-primary" />
              Daftar Isi
            </CardTitle>
          </CardHeader>
          <CardContent class="p-0 pb-3">
            <ScrollArea class="h-[calc(100vh-14rem)] px-3">
              <nav class="flex flex-col space-y-0.5 text-xs pr-2">
                <button
                  v-for="item in parsedDoc.toc"
                  :key="item.id"
                  type="button"
                  :class="[
                    'text-left w-full py-1.5 px-2.5 rounded-md transition-colors truncate flex items-center justify-between group cursor-pointer',
                    item.level === 3 ? 'pl-5 text-[11px]' : 'font-medium',
                    activeHeadingId === item.id
                      ? 'bg-primary/10 text-primary font-semibold'
                      : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                  ]"
                  @click="scrollToSection(item.id)"
                >
                  <span class="truncate">{{ item.title }}</span>
                  <ChevronRight
                    v-if="activeHeadingId === item.id"
                    class="size-3 text-primary shrink-0 ml-1"
                  />
                </button>
              </nav>
            </ScrollArea>
          </CardContent>
        </Card>
      </aside>
    </div>
  </div>
</template>
