<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { ElTree } from 'element-plus';
import 'element-plus/es/components/tree/style/css';
import { CheckSquare, Square, Link2, Search, X, ShieldAlert } from '@lucide/vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import type { PermissionTreeNode } from '../api/roles.facade';

interface Props {
  modelValue?: string[];
  nodes?: PermissionTreeNode[];
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  nodes: () => [],
  disabled: false,
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string[]): void;
}>();

const treeRef = ref<InstanceType<typeof ElTree> | null>(null);
const treeContainerRef = ref<InstanceType<typeof ScrollArea> | HTMLElement | null>(null);
const searchQuery = ref('');
const realCheckedKeys = defineModel<string[]>('realCheckedKeys', { default: () => [] });

const treeData = computed(() => props.nodes);

const defaultProps = {
  children: 'children',
  label: 'label',
};

function getLeafKeys(items: PermissionTreeNode[]): string[] {
  return items.reduce((acc: string[], item) => {
    if (item.children && item.children.length > 0) {
      acc.push(...getLeafKeys(item.children));
    } else {
      acc.push(item.id);
    }
    return acc;
  }, []);
}

function resolveKeysToNodeIds(keys: string[], items: PermissionTreeNode[]): string[] {
  const nodeIds: string[] = [];
  const keySet = new Set(keys);

  function traverse(list: PermissionTreeNode[]) {
    for (const item of list) {
      if (!item.children || item.children.length === 0) {
        if (keySet.has(item.id) || (item.code && keySet.has(item.code))) {
          nodeIds.push(item.id);
        }
      } else {
        traverse(item.children);
      }
    }
  }

  traverse(items);
  return nodeIds;
}

function updateRealCheckedKeys(): void {
  if (!treeRef.value) return;
  const checkedKeys = treeRef.value.getCheckedKeys(false) as string[];
  const halfCheckedKeys = treeRef.value.getHalfCheckedKeys() as string[];
  realCheckedKeys.value = [...checkedKeys, ...halfCheckedKeys];
}

function handleCheck() {
  if (!treeRef.value) return;
  const checkedKeys = treeRef.value.getCheckedKeys() as string[];
  updateRealCheckedKeys();
  emit('update:modelValue', checkedKeys);
}

function selectAll() {
  if (!treeRef.value) return;
  const leafKeys = getLeafKeys(treeData.value);
  treeRef.value.setCheckedKeys(leafKeys, true);
  handleCheck();
}

function clearAll() {
  if (!treeRef.value) return;
  treeRef.value.setCheckedKeys([], true);
  handleCheck();
}

function getLabelParts(text: string, query: string): Array<{ text: string; isMatch: boolean }> {
  const trimmedQuery = query.trim();
  if (!trimmedQuery) return [{ text, isMatch: false }];

  const q = trimmedQuery.toLowerCase();
  const lowerText = text.toLowerCase();
  if (!lowerText.includes(q)) return [{ text, isMatch: false }];

  const parts: Array<{ text: string; isMatch: boolean }> = [];
  let start = 0;
  let pos = lowerText.indexOf(q, start);

  while (pos !== -1) {
    if (pos > start) {
      parts.push({ text: text.slice(start, pos), isMatch: false });
    }
    parts.push({ text: text.slice(pos, pos + q.length), isMatch: true });
    start = pos + q.length;
    pos = lowerText.indexOf(q, start);
  }

  if (start < text.length) {
    parts.push({ text: text.slice(start), isMatch: false });
  }

  return parts;
}

function isNodeMatched(data: PermissionTreeNode): boolean {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return false;
  const labelMatch = data.label ? data.label.toLowerCase().includes(q) : false;
  const codeMatch = data.code ? data.code.toLowerCase().includes(q) : false;
  return labelMatch || codeMatch;
}

function findFirstMatchingNodeId(nodes: PermissionTreeNode[], query: string): string | null {
  const q = query.trim().toLowerCase();
  if (!q) return null;

  for (const node of nodes) {
    if (
      node.label.toLowerCase().includes(q) ||
      (node.code && node.code.toLowerCase().includes(q))
    ) {
      return node.id;
    }
    if (node.children) {
      const childMatch = findFirstMatchingNodeId(node.children, query);
      if (childMatch) return childMatch;
    }
  }
  return null;
}

watch(searchQuery, (newQuery) => {
  const q = newQuery.trim();
  if (!q || !treeRef.value) return;

  const firstMatchId = findFirstMatchingNodeId(treeData.value, q);
  if (!firstMatchId) return;

  // Pastikan parent node ter-expand jika terlipat
  let treeNode = treeRef.value.getNode(firstMatchId);
  while (treeNode && treeNode.parent) {
    treeNode.parent.expanded = true;
    treeNode = treeNode.parent;
  }

  void nextTick(() => {
    const rootEl =
      treeContainerRef.value && '$el' in treeContainerRef.value
        ? (treeContainerRef.value.$el as HTMLElement)
        : treeContainerRef.value;
    const targetEl = rootEl?.querySelector(`[data-node-id="${firstMatchId}"]`);
    if (targetEl) {
      targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });
});

function syncCheckedKeys(): void {
  if (!treeRef.value || !props.nodes || props.nodes.length === 0) return;

  const resolvedLeafIds = resolveKeysToNodeIds(props.modelValue || [], props.nodes);
  const currentCheckedLeaves = treeRef.value.getCheckedKeys(true) as string[];

  const isSame =
    currentCheckedLeaves.length === resolvedLeafIds.length &&
    currentCheckedLeaves.every((val) => resolvedLeafIds.includes(val));

  if (!isSame) {
    treeRef.value.setCheckedKeys(resolvedLeafIds, true);
  }
  updateRealCheckedKeys();
}

// Sinkronkan nilai modelValue dan nodes ke ElTree saat diinisialisasi atau berubah dari luar
watch(
  [() => props.modelValue, () => props.nodes],
  async () => {
    await nextTick();
    syncCheckedKeys();
  },
  { immediate: true, deep: true },
);
</script>

<template>
  <div
    class="permission-tree-container flex flex-col overflow-hidden rounded-xl border border-muted/60 bg-muted/50"
    :class="{ 'pointer-events-none opacity-60': disabled }"
  >
    <!-- Header Section dengan Input Search & Quick Actions (Non-scrolling Header) -->
    <div
      class="flex items-center justify-between gap-2 border-b border-border/50 bg-muted/80 px-3 py-2"
    >
      <div class="flex min-w-0 items-center gap-1.5">
        <!-- Input Search disamping kiri Pilih Semua -->
        <div class="relative min-w-35 max-w-50 sm:min-w-45">
          <Search
            class="pointer-events-none absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-muted-foreground"
          />
          <input
            v-model="searchQuery"
            type="text"
            :disabled="disabled"
            placeholder="Cari hak akses..."
            class="h-7 w-full rounded-md border border-input bg-background pl-8 pr-6 text-xs shadow-2xs transition-colors placeholder:text-muted-foreground focus-visible:outline-hidden focus-visible:ring-1 focus-visible:ring-ring"
          />
          <button
            v-if="searchQuery"
            type="button"
            :disabled="disabled"
            class="absolute right-1.5 top-1/2 -translate-y-1/2 cursor-pointer rounded-full p-0.5 text-muted-foreground hover:text-foreground"
            title="Bersihkan pencarian"
            @click="searchQuery = ''"
          >
            <X class="size-3" />
          </button>
        </div>

        <button
          type="button"
          :disabled="disabled"
          class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-md bg-muted/60 px-2 py-1 text-[11px] font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
          title="Pilih semua hak akses"
          @click="selectAll"
        >
          <CheckSquare class="size-3 text-primary" />
          <span>Pilih Semua</span>
        </button>
        <button
          type="button"
          :disabled="disabled"
          class="inline-flex shrink-0 cursor-pointer items-center gap-1 rounded-md bg-muted/60 px-2 py-1 text-[11px] font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
          title="Bersihkan semua pilihan"
          @click="clearAll"
        >
          <Square class="size-3 text-muted-foreground" />
          <span>Bersihkan</span>
        </button>
      </div>

      <span
        class="inline-flex shrink-0 items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-medium text-primary"
      >
        {{ modelValue.length }} Terpilih
      </span>
    </div>

    <!-- Empty State jika data dari backend kosong -->
    <div
      v-if="treeData.length === 0"
      class="flex flex-col items-center justify-center gap-2 py-8 px-4 text-center"
    >
      <div
        class="flex size-10 items-center justify-center rounded-full bg-muted text-muted-foreground/70"
      >
        <ShieldAlert class="size-5" />
      </div>
      <div class="flex flex-col gap-0.5">
        <p class="text-xs font-medium text-foreground">Tidak Ada Hak Akses</p>
        <p class="text-2xs text-muted-foreground">
          Belum ada data fitur atau hak akses yang terdaftar dari backend.
        </p>
      </div>
    </div>

    <!-- Scrollable Tree Content dengan ScrollArea shadcn-vue -->
    <ScrollArea v-else ref="treeContainerRef" class="max-h-72 min-h-28 p-3">
      <ElTree
        ref="treeRef"
        :data="treeData"
        :props="defaultProps"
        show-checkbox
        check-on-click-node
        node-key="id"
        default-expand-all
        empty-text="Tidak ada hak akses"
        :check-strictly="false"
        class="custom-el-tree cursor-pointer"
        @check="handleCheck"
      >
        <template #default="{ node, data }">
          <div
            :data-node-id="data.id"
            class="flex items-center gap-0.5 rounded-xs px-1 py-0.5 text-xs transition-colors"
            :class="{ 'bg-amber-500/15 dark:bg-amber-400/20': isNodeMatched(data) }"
          >
            <span
              class="font-medium leading-snug transition-colors"
              :class="data.children ? 'font-semibold text-foreground' : 'text-foreground/90'"
            >
              <template v-for="(part, idx) in getLabelParts(node.label, searchQuery)" :key="idx">
                <mark
                  v-if="part.isMatch"
                  class="rounded-xs bg-amber-300/80 px-0.5 font-bold text-foreground dark:bg-amber-500/60"
                >
                  {{ part.text }}
                </mark>
                <span v-else>{{ part.text }}</span>
              </template>
            </span>
            <span
              v-if="data.code"
              class="flex items-center gap-1 rounded bg-muted/70 px-1.5 py-0.2 font-mono text-2xs text-muted-foreground/80"
            >
              <Link2 class="size-3" />
              <template v-for="(part, idx) in getLabelParts(data.code, searchQuery)" :key="idx">
                <mark
                  v-if="part.isMatch"
                  class="rounded-xs bg-amber-300/80 px-0.5 font-bold text-foreground dark:bg-amber-500/60"
                >
                  {{ part.text }}
                </mark>
                <span v-else>{{ part.text }}</span>
              </template>
            </span>
          </div>
        </template>
      </ElTree>
    </ScrollArea>
  </div>
</template>

<style scoped>
/* Styling adaptif Element Plus Tree */
.custom-el-tree {
  --el-tree-node-hover-bg-color: color-mix(in oklch, var(--accent) 60%, transparent);
  --el-tree-text-color: var(--foreground);
  --el-tree-expand-icon-color: var(--muted-foreground);
  --el-checkbox-checked-bg-color: var(--primary);
  --el-checkbox-checked-border-color: var(--primary);
  --el-checkbox-border-radius: 4px;
  background: transparent;
}

:deep(.el-tree-node__content) {
  height: 26px;
  border-radius: 6px;
  padding-right: 8px;
  transition: background-color 0.15s ease;
}

:deep(.el-tree-node:focus > .el-tree-node__content) {
  background-color: color-mix(in oklch, var(--accent) 60%, transparent);
}

:deep(.el-checkbox__inner) {
  border-radius: 4px;
  border-color: var(--border);
  background-color: var(--background);
  transition: all 0.15s ease;
}

:deep(.el-checkbox__input.is-checked .el-checkbox__inner),
:deep(.el-checkbox__input.is-indeterminate .el-checkbox__inner) {
  background-color: var(--primary) !important;
  border-color: var(--primary) !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

:deep(.el-checkbox__input.is-checked .el-checkbox__inner::after) {
  border-color: var(--primary-foreground) !important;
}

:deep(.el-checkbox__input.is-indeterminate .el-checkbox__inner::before) {
  background-color: var(--primary-foreground) !important;
}
</style>
