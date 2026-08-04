<script setup lang="ts" generic="T extends Record<string, unknown>">
import { computed, getCurrentInstance, nextTick, onMounted, ref, shallowRef, watch } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { ElTable, ElTableColumn } from 'element-plus';
import {
  ChevronLeft,
  ChevronRight,
  CornerDownLeft,
  ListFilter,
  Pencil,
  Plus,
  RefreshCw,
  Search,
  Trash2,
  X,
} from '@lucide/vue';
import { DatePicker } from '@/components/custom-ui/date-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import { normalizeAppError } from '@/lib/axios';
import { Switch } from '@/components/ui/switch';
import type {
  DataTableError,
  DataTableFetcher,
  DataTableField,
  DataTableFilter,
  DataTableMeta,
  DataTableSort,
  DataTableSpanMethod,
  LaravelDataTableResponse,
} from './data-table.types';
import {
  buildParams,
  filterTreeRows,
  filterRows,
  getPath,
  highlightText,
  leafFields,
  searchRows,
  sortRows,
  sortTreeRows,
} from './data-table.utils';
import DataTableColumn from './DataTableColumn.vue';
import DataTableErrorAlert from './DataTableErrorAlert.vue';

const props = withDefaults(
  defineProps<{
    items?: T[];
    fetcher?: DataTableFetcher<T>;
    queryKey?: string | readonly unknown[];
    enabled?: boolean;
    extraParams?: Record<string, unknown>;
    fields: DataTableField<T>[];
    filters?: DataTableFilter[];
    title?: string;
    rowKey?: keyof T | ((row: T) => string | number);
    paginated?: boolean;
    perPage?: number;
    perPageOptions?: number[];
    showPagination?: boolean;
    showPerPage?: boolean;
    showSearch?: boolean;
    showFilter?: boolean;
    showRefresh?: boolean;
    striped?: boolean;
    selection?: boolean | 'multiple';
    selected?: T | T[] | null;
    actions?: boolean;
    actionsWidth?: string | number;
    canEdit?: (row: T) => boolean;
    canDelete?: (row: T) => boolean;
    rowSelectable?: (row: T) => boolean;
    remember?: string;
    rememberScope?: string;
    height?: string | number;
    maxHeight?: string | number;
    rowClass?: (row: T, rowIndex: number) => string;
    cellClass?: (row: T, column: DataTableField<T>, rowIndex: number) => string;
    spanMethod?: DataTableSpanMethod<T>;
    tree?:
      | boolean
      | {
          children?: string;
          hasChildren?: string;
          lazy?: boolean;
          defaultExpandAll?: boolean;
        };
    loadChildren?: (row: T, signal: Parameters<DataTableFetcher<T>>[0]['signal']) => Promise<T[]>;
    expandedKeys?: Array<string | number>;
    emptyTitle?: string;
    emptyDescription?: string;
    emptyButtonText?: string;
    showCreateButton?: boolean;
  }>(),
  {
    enabled: true,
    extraParams: () => ({}),
    filters: () => [],
    paginated: true,
    perPage: 10,
    perPageOptions: () => [5, 10, 15, 25, 50, 100],
    showPagination: true,
    showPerPage: true,
    showSearch: true,
    showFilter: true,
    showRefresh: true,
    showCreateButton: true,
    striped: true,
    actionsWidth: 120,
  },
);

const emit = defineEmits<{
  'update:selected': [value: T | T[] | null];
  'update:filters': [value: Record<string, unknown>];
  'update:expandedKeys': [value: Array<string | number>];
  loading: [value: boolean];
  loaded: [payload: { rows: T[]; meta?: DataTableMeta; message?: string }];
  error: [payload: DataTableError & { error: unknown }];
  'params-change': [params: ReturnType<typeof buildParams>];
  edit: [row: T];
  delete: [row: T];
  create: [];
  add: [];
  'row-click': [row: T, column: unknown, event: unknown];
  'row-dblclick': [row: T, column: unknown, event: unknown];
  'row-contextmenu': [row: T, column: unknown, event: unknown];
}>();

const instanceKey = `datatable-${getCurrentInstance()?.uid ?? Math.random()}`;
const slots = defineSlots<{
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  [name: string]: (props: any) => any;
}>();
const mounted = ref(false);
const page = ref(1);
const perPage = ref(props.perPage);
const searchDraft = ref('');
const search = ref('');
const searchFields = ref(
  leafFields(props.fields)
    .filter((field) => field.filterColumn !== false)
    .map((field) => field.key),
);
const draftSearchFields = ref([...searchFields.value]);
const activeFilters = ref<Record<string, unknown>>({});
const draftFilters = ref<Record<string, unknown>>({});
const sorts = ref<DataTableSort[]>([]);
const filterOpen = ref(false);
const table = ref<{
  clearSelection: () => void;
  scrollTo: (options: { top: number }) => void;
  toggleRowExpansion: (row: T, expanded: boolean) => void;
}>();
const selectedKeys = shallowRef(new Map<string | number, T>());
const internalExpandedKeys = ref<Array<string | number>>([]);
const detailExpandedRow = shallowRef<T>();
const loadControllers = new Map<
  string | number,
  { abort: () => void; signal: Parameters<DataTableFetcher<T>>[0]['signal'] }
>();
const columnWidths = ref<Record<string, number>>({});
const cachedServerResponse = shallowRef<LaravelDataTableResponse<T> | null>(null);

function getRouteScope(): string {
  if (props.rememberScope) return props.rememberScope;
  const instance = getCurrentInstance();
  const route = instance?.appContext.config.globalProperties?.$route as
    { matched?: Array<{ name?: string; path?: string }>; path?: string } | undefined;
  if (route?.matched?.length) {
    return route.matched[0].name || route.matched[0].path || route.path || 'default';
  }
  const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
  return pathname && pathname !== '/' && pathname !== '/blank' ? pathname : 'default';
}

function handleHeaderDragend(
  newWidth: number,
  _oldWidth: number,
  column: { property?: string },
): void {
  if (column?.property) {
    columnWidths.value = {
      ...columnWidths.value,
      [column.property]: newWidth,
    };
  }
}

const memoryKey = computed(() =>
  props.remember ? `datatable:${getRouteScope()}:${props.remember}` : '',
);
const visibleFields = computed(() => props.fields.filter((field) => !field.hidden));
const searchableFields = computed(() => leafFields(props.fields));
const treeConfig = computed(() => (typeof props.tree === 'object' ? props.tree : {}));
const childrenKey = computed(() => treeConfig.value.children ?? 'children');
const activeExpandedKeys = computed(() => props.expandedKeys ?? internalExpandedKeys.value);
const params = computed(() =>
  buildParams(
    {
      page: page.value,
      perPage: perPage.value,
      paginated: props.paginated,
      search: search.value,
      searchFields: searchFields.value,
      sorts: sorts.value.map((sort) => ({
        ...sort,
        key: searchableFields.value.find((field) => field.key === sort.key)?.sortKey ?? sort.key,
      })),
      filters: activeFilters.value,
    },
    props.extraParams,
  ),
);
const finalQueryKey = computed(() => [
  ...(Array.isArray(props.queryKey) ? props.queryKey : [props.queryKey ?? instanceKey]),
  params.value,
]);

const query = useQuery({
  queryKey: finalQueryKey,
  enabled: computed(() => mounted.value && Boolean(props.fetcher) && props.enabled),
  queryFn: ({ signal }) => props.fetcher!({ params: params.value, signal }),
  placeholderData: (previous) => previous ?? cachedServerResponse.value ?? undefined,
  retry: (count, error) => {
    return normalizeAppError(error).retryable && count < 2;
  },
  retryDelay: (attempt, error) =>
    normalizeAppError(error).retryAfterMs ?? Math.min(1000 * 2 ** attempt, 4000),
});

const localTreeResult = computed(() =>
  filterTreeRows(
    props.items ?? [],
    search.value,
    searchableFields.value,
    childrenKey.value,
    searchFields.value,
    props.rowKey,
  ),
);
const localRows = computed(() => {
  const searched = props.tree
    ? localTreeResult.value.rows
    : searchRows(props.items ?? [], search.value, searchableFields.value, searchFields.value);
  const filtered = filterRows(searched, activeFilters.value, props.filters);
  return props.tree
    ? sortTreeRows(filtered, sorts.value, childrenKey.value)
    : sortRows(filtered, sorts.value);
});
const rows = computed<T[]>(() => {
  if (props.fetcher) return query.data.value?.data ?? [];
  if (!props.paginated) return localRows.value;
  const start = (page.value - 1) * perPage.value;
  return localRows.value.slice(start, start + perPage.value);
});
const selectableRows = computed<T[]>(() =>
  rows.value.filter((row: T) => props.rowSelectable?.(row) !== false),
);
const pageSelection = computed(() => {
  const selected = selectableRows.value.filter((row: T) =>
    selectedKeys.value.has(rowIdentity(row)),
  ).length;
  return selected === 0 ? false : selected === selectableRows.value.length ? true : 'indeterminate';
});
const rootKeys = computed(() => new Set(rows.value.map((row: T) => rowIdentity(row))));
const meta = computed<DataTableMeta | undefined>(() => query.data.value?.meta);
const total = computed(() =>
  props.fetcher ? (meta.value?.total ?? rows.value.length) : localRows.value.length,
);
const pageCount = computed(() => Math.max(1, Math.ceil(total.value / perPage.value)));
const initialLoading = computed(
  () => Boolean(props.fetcher) && query.isPending.value && !rows.value.length,
);
const refreshing = computed(() => query.isFetching.value && Boolean(rows.value.length));
const error = computed(() => (query.error.value ? normalizeAppError(query.error.value) : null));
const hasActiveState = computed(() =>
  Boolean(search.value || Object.keys(activeFilters.value).length),
);
const hasAppliedFilters = computed(() =>
  Object.values(activeFilters.value).some((value) => {
    if (value === undefined || value === null || value === '') return false;
    if (Array.isArray(value))
      return value.some((item) => item !== undefined && item !== null && item !== '');
    return true;
  }),
);
const showFilterButton = computed(
  () =>
    props.showFilter &&
    (props.filters.length > 0 ||
      searchableFields.value.filter((field) => field.filterColumn !== false).length > 1),
);

function rowIdentity(row: T): string | number {
  if (typeof props.rowKey === 'function') return props.rowKey(row);
  return (row[props.rowKey ?? ('id' as keyof T)] as string | number) ?? rows.value.indexOf(row);
}

function valueFor(row: T, field: DataTableField<T>, rowIndex: number): unknown {
  if (field.key === 'rownum')
    return props.tree && !rootKeys.value.has(rowIdentity(row))
      ? ''
      : (page.value - 1) * perPage.value + rowIndex + 1;
  const value = getPath(row, field.key);
  if (field.formatter) return field.formatter(value, row, field);
  return value ?? '-';
}

function highlighted(value: unknown): Array<{ text: string; match: boolean }> {
  return highlightText(value, props.tree ? search.value : '');
}

function setExpanded(row: T, expanded: boolean): void {
  const next = new Set(activeExpandedKeys.value);
  const key = rowIdentity(row);
  if (expanded) next.add(key);
  else next.delete(key);
  internalExpandedKeys.value = [...next];
  emit('update:expandedKeys', [...next]);
}

function handleExpand(row: T, expanded: boolean | T[]): void {
  const isExpanded = Array.isArray(expanded) ? expanded.includes(row) : expanded;
  if (props.tree) setExpanded(row, isExpanded);
  if (!slots.expand) return;
  if (isExpanded && detailExpandedRow.value && detailExpandedRow.value !== row)
    table.value?.toggleRowExpansion(detailExpandedRow.value, false);
  detailExpandedRow.value = isExpanded ? row : undefined;
}

async function loadTree(row: T, _node: unknown, resolve: (rows: T[]) => void): Promise<void> {
  if (!props.loadChildren) return resolve([]);
  const key = rowIdentity(row);
  loadControllers.get(key)?.abort();
  const controller = new globalThis.AbortController();
  loadControllers.set(key, controller);
  try {
    resolve(await props.loadChildren(row, controller.signal));
  } catch (error) {
    if (!controller.signal.aborted) {
      resolve([]);
      emit('error', { error, ...normalizeAppError(error) });
    }
  } finally {
    loadControllers.delete(key);
  }
}

function syncTreeExpansion(keys?: Array<string | number>): void {
  if (!props.tree || !table.value) return;
  const targetKeys = new Set((keys ?? activeExpandedKeys.value).map(String));

  const visit = (nodes: T[]) => {
    nodes.forEach((node) => {
      const key = String(rowIdentity(node));
      const children = getPath(node, childrenKey.value) as T[] | undefined;
      if (Array.isArray(children) && children.length) {
        const isExpanded = targetKeys.has(key);
        table.value?.toggleRowExpansion(node, isExpanded);
        visit(children);
      }
    });
  };
  visit(rows.value);
}

function expandAll(): void {
  const keys: Array<string | number> = [];
  const lazyMap = (
    table.value as unknown as {
      store?: {
        states?: {
          lazyTreeNodeMap?: Record<string, T[]> | { value: Record<string, T[]> };
        };
      };
    }
  )?.store?.states?.lazyTreeNodeMap;
  const loadedLazyChildren =
    ((lazyMap && 'value' in lazyMap ? lazyMap.value : lazyMap) as Record<string | number, T[]>) ??
    {};

  const visit = (nodes: T[]) =>
    nodes.forEach((row) => {
      const key = rowIdentity(row);
      const children = getPath(row, childrenKey.value);
      const lazyChildren = loadedLazyChildren[String(key)] ?? loadedLazyChildren[key];
      const effectiveChildren = (
        Array.isArray(children) && children.length ? children : lazyChildren
      ) as T[] | undefined;

      if (Array.isArray(effectiveChildren) && effectiveChildren.length) {
        keys.push(key);
        visit(effectiveChildren);
      }
    });
  visit(rows.value);
  internalExpandedKeys.value = keys;
  emit('update:expandedKeys', keys);
  syncTreeExpansion(keys);
}

function collapseAll(): void {
  internalExpandedKeys.value = [];
  emit('update:expandedKeys', []);
  syncTreeExpansion([]);
}

function submitSearch(): void {
  search.value = searchDraft.value.trim();
  searchDraft.value = search.value;
  page.value = 1;
  clearSelection();
}

function clearSearch(event?: unknown): void {
  const inputVal = (event as { currentTarget?: { value?: unknown } })?.currentTarget?.value;
  if (typeof inputVal === 'string' && inputVal.length > 0) return;
  searchDraft.value = '';
  submitSearch();
}

function changeSort(field: DataTableField<T>, event: { shiftKey: boolean }): void {
  if (field.key === 'rownum' || field.sortable === false) return;
  const current = sorts.value.find((sort) => sort.key === field.key);
  const next = !current ? 'asc' : current.direction === 'asc' ? 'desc' : null;
  const retained = event.shiftKey ? sorts.value.filter((sort) => sort.key !== field.key) : [];
  sorts.value = next ? [...retained, { key: field.key, direction: next }] : retained;
  page.value = 1;
}

function isFilterDisabled(filterKey: string): boolean {
  const isColumn = searchableFields.value.some((field) => field.key === filterKey);
  if (!isColumn) return false;
  return !draftSearchFields.value.includes(filterKey);
}

function applyFilters(): void {
  const enabledFields = new Set(draftSearchFields.value);
  activeFilters.value = Object.fromEntries(
    Object.entries(draftFilters.value)
      .filter(([key, value]) => {
        const isColumn = searchableFields.value.some((field) => field.key === key);
        if (isColumn && !enabledFields.has(key)) return false;
        return Array.isArray(value) ? value.length : value !== '' && value != null;
      })
      .map(([key, value]) => {
        const definition = props.filters?.find((filter) => filter.key === key);
        return [key, definition?.serialize ? definition.serialize(value) : value];
      }),
  );
  searchFields.value = [...draftSearchFields.value];
  page.value = 1;
  filterOpen.value = false;
  clearSelection();
  emit('update:filters', activeFilters.value);
}

function resetFilters(): Promise<void> {
  draftFilters.value = {};
  activeFilters.value = {};
  draftSearchFields.value = searchableFields.value
    .filter((field) => field.filterColumn !== false)
    .map((field) => field.key);
  searchFields.value = [...draftSearchFields.value];
  page.value = 1;
  clearSelection();
  emit('update:filters', {});
  return refresh();
}

function toggleMultiple(row: T, selected: boolean): void {
  const key = rowIdentity(row);
  const next = new Map(selectedKeys.value);
  if (selected) next.set(key, row);
  else next.delete(key);
  selectedKeys.value = next;
  emit('update:selected', [...selectedKeys.value.values()]);
}

function togglePage(selected: boolean): void {
  const next = new Map(selectedKeys.value);
  selectableRows.value.forEach((row: T) => {
    const key = rowIdentity(row);
    if (selected) next.set(key, row);
    else next.delete(key);
  });
  selectedKeys.value = next;
  emit('update:selected', [...next.values()]);
}

function selectSingle(row: T): void {
  selectedKeys.value = new Map([[rowIdentity(row), row]]);
  emit('update:selected', row);
}

function clearSelection(): void {
  selectedKeys.value = new Map();
  table.value?.clearSelection();
  emit('update:selected', props.selection === 'multiple' ? [] : null);
}

async function refresh(): Promise<void> {
  if (props.fetcher) await query.refetch();
}

function scrollToTop(): void {
  table.value?.scrollTo({ top: 0 });
}

function toggleSearchField(key: string, selected: boolean): void {
  draftSearchFields.value = selected
    ? [...new Set([...draftSearchFields.value, key])]
    : draftSearchFields.value.filter((field) => field !== key);

  if (!selected) {
    delete draftFilters.value[key];
  }
}

function updateFilterValue(filter: DataTableFilter, value: unknown): void {
  draftFilters.value[filter.key] =
    value === '__all__' ? undefined : filter.type === 'boolean' ? value === 'true' : value;
}

function updateDateRange(key: string, value: unknown): void {
  draftFilters.value[key] = Array.isArray(value) && value.some(Boolean) ? value : undefined;
}

function rowClassName({ row, rowIndex }: { row: T; rowIndex: number }): string {
  return cn(
    selectedKeys.value.has(rowIdentity(row)) && 'datatable-row-selected',
    props.rowSelectable?.(row) === false && 'datatable-row-disabled',
    props.rowClass?.(row, rowIndex),
  );
}

function cellClassName({
  row,
  column,
  rowIndex,
}: {
  row: T;
  column: { property?: string };
  rowIndex: number;
}): string {
  const field = searchableFields.value.find((candidate) => candidate.key === column.property);
  return field
    ? cn(`datatable-cell-${field.wrap ?? 'wrap'}`, props.cellClass?.(row, field, rowIndex))
    : '';
}

function tableSpanMethod({
  row,
  column,
  rowIndex,
  columnIndex,
}: {
  row: T;
  column: { property?: string };
  rowIndex: number;
  columnIndex: number;
}) {
  const field = searchableFields.value.find((candidate) => candidate.key === column.property);
  return field ? props.spanMethod?.({ row, column: field, rowIndex, columnIndex }) : undefined;
}

function loadMemory(): void {
  if (!memoryKey.value) return;
  try {
    const value = JSON.parse(globalThis.sessionStorage.getItem(memoryKey.value) ?? '{}');
    page.value = value.page ?? page.value;
    perPage.value = value.perPage ?? perPage.value;
    search.value = searchDraft.value = value.search ?? '';
    searchFields.value = draftSearchFields.value = value.searchFields ?? searchFields.value;
    activeFilters.value = draftFilters.value = value.filters ?? {};
    sorts.value = value.sorts ?? [];
    columnWidths.value = value.columnWidths ?? {};
    internalExpandedKeys.value = value.expandedKeys ?? [];
    if (value.serverCache) {
      cachedServerResponse.value = value.serverCache;
    }
    const selected = Array.isArray(value.selected)
      ? value.selected
      : value.selected
        ? [value.selected]
        : [];
    selectedKeys.value = new Map(selected.map((row: T) => [rowIdentity(row), row]));
    emit('update:selected', props.selection === 'multiple' ? selected : (selected[0] ?? null));
  } catch {
    globalThis.sessionStorage.removeItem(memoryKey.value);
  }
}

watch(params, () => emit('params-change', params.value), { deep: true });
watch(
  () => props.extraParams,
  () => {
    page.value = 1;
  },
  { deep: true },
);
watch(
  () => props.selected,
  (value) => {
    const selected = Array.isArray(value) ? value : value ? [value] : [];
    selectedKeys.value = new Map(selected.map((row) => [rowIdentity(row), row]));
  },
  { immediate: true, deep: true },
);
watch(
  () =>
    props.remember && [
      page.value,
      perPage.value,
      search.value,
      searchFields.value,
      activeFilters.value,
      sorts.value,
      columnWidths.value,
      internalExpandedKeys.value,
      selectedKeys.value,
      query.data.value,
    ],
  () => {
    if (!memoryKey.value) return;
    globalThis.sessionStorage.setItem(
      memoryKey.value,
      JSON.stringify({
        page: page.value,
        perPage: perPage.value,
        search: search.value,
        searchFields: searchFields.value,
        filters: activeFilters.value,
        sorts: sorts.value,
        columnWidths: columnWidths.value,
        expandedKeys: internalExpandedKeys.value,
        selected:
          props.selection === 'multiple'
            ? [...selectedKeys.value.values()]
            : (selectedKeys.value.values().next().value ?? null),
        serverCache: query.data.value
          ? {
              data: query.data.value.data,
              meta: query.data.value.meta,
              message: query.data.value.message,
            }
          : cachedServerResponse.value,
      }),
    );
  },
  { deep: true },
);
watch(
  () => query.isFetching.value,
  (value) => emit('loading', value),
);
watch(
  () => query.data.value,
  (value) => {
    if (value) emit('loaded', { rows: value.data, meta: value.meta, message: value.message });
  },
);
watch(
  rows,
  (value: T[]) => {
    if (!selectedKeys.value.size) return;
    const next = new Map(selectedKeys.value);
    value.forEach((row: T) => {
      const key = rowIdentity(row);
      if (next.has(key)) next.set(key, row);
    });
    selectedKeys.value = next;
    emit(
      'update:selected',
      props.selection === 'multiple' ? [...next.values()] : (next.values().next().value ?? null),
    );
  },
  { deep: true },
);
watch(
  () => query.error.value,
  (value) => {
    if (value) emit('error', { error: value, ...normalizeAppError(value) });
  },
);
watch(pageCount, (count) => {
  if (page.value > count) page.value = count;
});
watch(
  () => props.expandedKeys,
  (value) => {
    if (value) internalExpandedKeys.value = [...value];
  },
  { immediate: true },
);
watch(
  () => [search.value, rows.value],
  () => {
    detailExpandedRow.value = undefined;
    if (props.tree && search.value && !props.fetcher) {
      internalExpandedKeys.value = localTreeResult.value.expandedKeys;
      emit('update:expandedKeys', localTreeResult.value.expandedKeys);
    } else if (
      props.tree &&
      treeConfig.value.defaultExpandAll &&
      !internalExpandedKeys.value.length &&
      !props.expandedKeys
    ) {
      expandAll();
    }
  },
  { deep: true, immediate: true },
);

onMounted(async () => {
  if (props.items && props.fetcher)
    globalThis.console.warn('[DataTable] Berikan items atau fetcher, bukan keduanya.');
  if (props.remember && !props.queryKey)
    globalThis.console.warn(
      '[DataTable] queryKey eksplisit direkomendasikan saat remember digunakan.',
    );
  if (props.fetcher && props.selection === 'multiple' && !props.rowKey)
    globalThis.console.warn('[DataTable] rowKey wajib untuk multiple selection mode server.');
  loadMemory();
  await nextTick();
  if (
    props.tree &&
    treeConfig.value.defaultExpandAll &&
    !internalExpandedKeys.value.length &&
    !props.expandedKeys
  ) {
    expandAll();
  }
  mounted.value = true;
});

defineExpose({
  refresh,
  reload: refresh,
  resetFilters,
  clearSelection,
  scrollToTop,
  expandAll,
  collapseAll,
});
</script>

<template>
  <section class="datatable flex min-w-0 flex-col gap-4">
    <header class="flex flex-wrap items-center gap-3">
      <slot name="title" :title="title" :total="total" :loading="query.isFetching.value">
        <h2 v-if="title" class="text-lg font-semibold">
          {{ title }} ({{ total.toLocaleString() }} baris)
        </h2>
      </slot>

      <div class="min-w-0 flex-1">
        <slot name="toolbar" :params="params" :refresh="refresh" />
      </div>

      <Button
        v-if="showRefresh && fetcher"
        variant="outline"
        size="icon"
        aria-label="Muat ulang data"
        @click="refresh"
      >
        <Spinner v-if="refreshing" class="size-4" />
        <RefreshCw v-else class="size-4" />
      </Button>
      <Button
        v-if="showFilterButton"
        variant="outline"
        size="icon"
        :class="[
          'transition-colors',
          hasAppliedFilters &&
            'border-primary/30 bg-primary/10 font-semibold text-primary hover:bg-primary/20 hover:text-primary',
        ]"
        aria-label="Buka filter"
        @click="filterOpen = true"
      >
        <ListFilter />
      </Button>
      <div v-if="showSearch" class="flex min-w-56 items-center gap-2">
        <div class="relative flex-1">
          <Search
            class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground size-4"
          />
          <Input
            v-model="searchDraft"
            :class="
              cn(
                'pl-9 [&::-webkit-search-cancel-button]:appearance-none [&::-webkit-search-cancel-button]:hidden',
                searchDraft.trim() !== search.trim() ? 'pr-14' : search.trim() !== '' ? 'pr-9' : '',
              )
            "
            type="search"
            placeholder="Pencarian..."
            aria-label="Cari data"
            @keydown.enter="submitSearch()"
            @search="clearSearch"
          />
          <template v-if="searchDraft || search">
            <button
              v-if="searchDraft.trim() !== search.trim()"
              type="button"
              class="absolute top-1/2 right-1.5 -translate-y-1/2 flex items-center justify-center cursor-pointer"
              aria-label="Tekan Enter untuk mencari"
              @click.stop.prevent="submitSearch()"
            >
              <kbd
                class="inline-flex h-5 select-none items-center justify-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium leading-none text-muted-foreground/80 hover:text-foreground transition-colors"
              >
                <CornerDownLeft class="size-2.5" />
                <span>Enter</span>
              </kbd>
            </button>
            <button
              v-else-if="search.trim() !== ''"
              type="button"
              class="absolute top-1/2 right-2.5 -translate-y-1/2 flex items-center justify-center text-muted-foreground/75 hover:text-foreground transition-colors cursor-pointer p-0.5 rounded-xs"
              aria-label="Hapus pencarian"
              @click.stop.prevent="clearSearch()"
            >
              <X class="size-4" />
            </button>
          </template>
        </div>
      </div>
    </header>

    <div class="overflow-hidden rounded-lg border transform-[translateZ(0)]">
      <ElTable
        ref="table"
        :data="rows"
        :stripe="striped"
        :height="height"
        :max-height="maxHeight"
        :row-key="(row: T) => String(rowIdentity(row))"
        :tree-props="{
          children: childrenKey,
          hasChildren: treeConfig.hasChildren ?? 'hasChildren',
        }"
        :lazy="treeConfig.lazy"
        :load="loadTree"
        :default-expand-all="treeConfig.defaultExpandAll"
        :expand-row-keys="!tree && $slots.expand ? activeExpandedKeys.map(String) : undefined"
        :span-method="tableSpanMethod"
        :row-class-name="rowClassName"
        :cell-class-name="cellClassName"
        class="w-full"
        @header-dragend="handleHeaderDragend"
        @row-click="
          (row: T, column: unknown, event: unknown) => emit('row-click', row, column, event)
        "
        @row-dblclick="
          (row: T, column: unknown, event: unknown) => emit('row-dblclick', row, column, event)
        "
        @row-contextmenu="
          (row: T, column: unknown, event: unknown) => emit('row-contextmenu', row, column, event)
        "
        @expand-change="handleExpand"
      >
        <ElTableColumn v-if="$slots.expand" type="expand" width="48" fixed="left">
          <template #default="{ row }">
            <slot name="expand" :row="row" :expanded="detailExpandedRow === row" />
          </template>
        </ElTableColumn>
        <ElTableColumn
          v-if="selection"
          width="48"
          fixed="left"
          align="center"
          header-align="center"
        >
          <template #header>
            <div class="flex items-center justify-center w-full">
              <Checkbox
                v-if="selection === 'multiple'"
                :model-value="pageSelection"
                :disabled="selectableRows.length === 0"
                aria-label="Pilih semua baris di halaman ini"
                @update:model-value="
                  (value: boolean | 'indeterminate') => togglePage(value === true)
                "
              />
              <span v-else class="sr-only">Pilih</span>
            </div>
          </template>
          <template #default="{ row }">
            <div class="flex items-center justify-center w-full">
              <Checkbox
                v-if="selection === 'multiple'"
                :model-value="selectedKeys.has(rowIdentity(row))"
                :disabled="rowSelectable?.(row) === false"
                :aria-label="`Pilih baris ${rowIdentity(row)}`"
                @update:model-value="
                  (value: boolean | 'indeterminate') => toggleMultiple(row, Boolean(value))
                "
              />
              <RadioGroup
                v-else
                :model-value="String([...selectedKeys.keys()][0] ?? '')"
                class="flex items-center justify-center"
                @update:model-value="selectSingle(row)"
              >
                <RadioGroupItem
                  :value="String(rowIdentity(row))"
                  :disabled="rowSelectable?.(row) === false"
                  :aria-label="`Pilih baris ${rowIdentity(row)}`"
                />
              </RadioGroup>
            </div>
          </template>
        </ElTableColumn>

        <ElTableColumn
          v-if="actions"
          :width="actionsWidth"
          fixed="left"
          align="center"
          class-name="!px-1"
        >
          <template #header>Aksi</template>
          <template #default="{ row }">
            <slot name="cell(action)" :row="row">
              <div class="flex justify-center gap-1">
                <slot name="action-extra" :row="row" />
                <Button
                  v-if="canEdit?.(row) !== false"
                  variant="link"
                  size="icon-sm"
                  aria-label="Edit"
                  class="cursor-pointer w-5"
                  @click.stop="emit('edit', row)"
                >
                  <Pencil />
                </Button>
                <Button
                  v-if="canDelete?.(row) !== false"
                  variant="ghost"
                  size="icon-sm"
                  aria-label="Hapus"
                  class="cursor-pointer w-5"
                  @click.stop="emit('delete', row)"
                >
                  <Trash2 />
                </Button>
              </div>
            </slot>
          </template>
        </ElTableColumn>

        <DataTableColumn
          v-for="field in visibleFields"
          :key="field.key"
          :field="field"
          :sorts="sorts"
          :slots="slots"
          :search="search"
          :tree="Boolean(tree)"
          :value-for="valueFor"
          :highlight="highlighted"
          :column-widths="columnWidths"
          @sort="changeSort"
        />

        <template #empty>
          <div
            class="flex min-h-64 flex-col items-center justify-center gap-3 py-10 px-4 text-center"
            aria-live="polite"
          >
            <template v-if="initialLoading">
              <Spinner />
              <span class="text-xs text-muted-foreground">Memuat data…</span>
            </template>
            <template v-else-if="error">
              <DataTableErrorAlert :error="error" @retry="refresh" />
            </template>
            <slot v-else name="empty">
              <div class="flex max-w-sm flex-col items-center justify-center gap-3 py-4">
                <div class="flex flex-col gap-1">
                  <h3 class="text-sm font-semibold text-foreground">
                    {{
                      emptyTitle ||
                      (hasActiveState
                        ? 'Data Tidak Ditemukan'
                        : title
                          ? `Belum Ada ${title.replace(/^Daftar\s+/i, '')}`
                          : 'Belum Ada Data')
                    }}
                  </h3>
                  <p class="text-xs text-muted-foreground leading-relaxed">
                    {{
                      emptyDescription ||
                      (hasActiveState
                        ? 'Coba ubah kata kunci atau filter pencarian Anda.'
                        : 'Belum ada data yang tersimpan. Klik tombol di bawah untuk menambah data baru.')
                    }}
                  </p>
                </div>
                <Button
                  v-if="showCreateButton"
                  variant="default"
                  size="sm"
                  class="mt-1 font-medium cursor-pointer shadow-2xs"
                  @click="
                    emit('create');
                    emit('add');
                  "
                >
                  <Plus class="mr-1.5 size-4" />
                  <span>
                    {{
                      emptyButtonText ||
                      (title ? `Tambah ${title.replace(/^Daftar\s+/i, '')}` : 'Tambah Data')
                    }}
                  </span>
                </Button>
              </div>
            </slot>
          </div>
        </template>
      </ElTable>
    </div>

    <footer
      v-if="paginated && (showPagination || showPerPage)"
      class="flex flex-nowrap items-center justify-between gap-3"
    >
      <Select
        v-if="showPerPage"
        :model-value="perPage"
        @update:model-value="
          (value: unknown) => {
            perPage = Number(value);
            page = 1;
          }
        "
      >
        <SelectTrigger class="w-28 shrink-0" aria-label="Baris per halaman">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectGroup>
            <SelectItem v-for="option in perPageOptions" :key="option" :value="option">
              {{ option }} / hal.
            </SelectItem>
          </SelectGroup>
        </SelectContent>
      </Select>
      <div class="flex-1">
        <Pagination
          v-if="showPagination"
          :page="page"
          :items-per-page="perPage"
          :total="total"
          :sibling-count="1"
          class="mx-0 justify-end"
          show-edges
          @update:page="page = $event"
        >
          <PaginationContent v-slot="{ items: paginationItems }">
            <PaginationPrevious size="icon" class="p-0!" aria-label="Halaman sebelumnya">
              <ChevronLeft data-icon="inline-start" class="cn-rtl-flip" />
            </PaginationPrevious>
            <template v-for="(item, index) in paginationItems" :key="index">
              <PaginationItem
                v-if="item.type === 'page'"
                :value="item.value"
                :is-active="item.value === page"
              >
                {{ item.value }}
              </PaginationItem>
              <PaginationEllipsis v-else :index="index" />
            </template>
            <PaginationNext size="icon" class="p-0!" aria-label="Halaman berikutnya">
              <ChevronRight data-icon="inline-end" class="cn-rtl-flip" />
            </PaginationNext>
          </PaginationContent>
        </Pagination>
      </div>
    </footer>

    <Sheet v-model:open="filterOpen">
      <SheetContent class="flex flex-col gap-0 p-0 h-full max-h-screen">
        <SheetHeader class="p-4 border-b shrink-0">
          <SheetTitle>Filter data</SheetTitle>
          <SheetDescription>Pilih kolom pencarian dan filter, lalu terapkan.</SheetDescription>
        </SheetHeader>

        <div class="flex-1 overflow-y-auto p-4 flex flex-col gap-6 min-h-0">
          <fieldset class="flex flex-col gap-2">
            <legend class="text-sm font-medium">Filter Kolom</legend>
            <div class="flex flex-wrap gap-1.5 pt-1">
              <Badge
                v-for="field in searchableFields.filter((item) => item.filterColumn !== false)"
                :key="field.key"
                :variant="draftSearchFields.includes(field.key) ? 'default' : 'outline'"
                class="cursor-pointer select-none transition-all px-2.5 py-1 text-xs font-normal"
                @click="toggleSearchField(field.key, !draftSearchFields.includes(field.key))"
              >
                {{ field.label }}
              </Badge>
            </div>
          </fieldset>

          <div
            v-for="filter in filters"
            :key="filter.key"
            class="flex flex-col gap-2"
            :class="{ 'opacity-50': isFilterDisabled(filter.key) }"
          >
            <div v-if="filter.type === 'boolean'" class="flex items-center gap-2">
              <Switch
                :id="`datatable-filter-${filter.key}`"
                :model-value="draftFilters[filter.key] === true"
                :disabled="isFilterDisabled(filter.key)"
                @update:model-value="draftFilters[filter.key] = $event || undefined"
              />
              <label class="text-sm font-medium" :for="`datatable-filter-${filter.key}`">
                {{ filter.label }}
              </label>
            </div>
            <slot
              v-if="filter.type === 'custom'"
              :name="`filter(${filter.key})`"
              :value="draftFilters[filter.key]"
              :set-value="(value: unknown) => (draftFilters[filter.key] = value)"
              :filter="filter"
              :disabled="isFilterDisabled(filter.key)"
            />
            <Select
              v-else-if="filter.type === 'select' || filter.type === 'multi-select'"
              :model-value="draftFilters[filter.key] as any"
              :multiple="filter.type === 'multi-select'"
              :disabled="isFilterDisabled(filter.key)"
              @update:model-value="updateFilterValue(filter, $event)"
            >
              <SelectTrigger :id="`datatable-filter-${filter.key}`" class="w-full">
                <SelectValue :placeholder="`Pilih ${filter.label.toLocaleLowerCase()}`" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectItem v-if="filter.type === 'select'" value="__all__">Semua</SelectItem>
                  <SelectItem
                    v-for="option in filter.options"
                    :key="String(option.value)"
                    :value="option.value as any"
                  >
                    {{ option.label }}
                  </SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
            <DatePicker
              v-else-if="filter.type === 'date-range'"
              mode="range"
              popover-side="left"
              popover-align="end"
              clearable
              :model-value="
                Array.isArray(draftFilters[filter.key])
                  ? (draftFilters[filter.key] as [string, string])
                  : ['', '']
              "
              :placeholder="`Pilih ${filter.label.toLocaleLowerCase()}`"
              :disabled="isFilterDisabled(filter.key)"
              @update:model-value="updateDateRange(filter.key, $event)"
            />
            <DatePicker
              v-else-if="filter.type === 'date'"
              popover-side="left"
              popover-align="end"
              clearable
              :disabled="isFilterDisabled(filter.key)"
              :model-value="String(draftFilters[filter.key] ?? '')"
              :placeholder="`Pilih ${filter.label.toLocaleLowerCase()}`"
              @update:model-value="draftFilters[filter.key] = $event || undefined"
            />
            <Input
              v-else-if="filter.type === 'text'"
              :id="`datatable-filter-${filter.key}`"
              :disabled="isFilterDisabled(filter.key)"
              :model-value="String(draftFilters[filter.key] ?? '')"
              @update:model-value="draftFilters[filter.key] = $event"
            />
          </div>
        </div>

        <SheetFooter class="p-4 border-t shrink-0 flex flex-col gap-2">
          <Button variant="outline" @click="resetFilters">Atur Ulang</Button>
          <Button @click="applyFilters">Terapkan</Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  </section>
</template>

<style scoped>
.datatable {
  --el-color-primary: var(--primary);
  --el-border-color: var(--border);
  --el-border-color-lighter: var(--border);
  --el-fill-color-blank: var(--background);
  --el-bg-color: var(--background);
  --el-text-color-primary: var(--foreground);
  --el-text-color-regular: var(--foreground);
  --el-text-color-secondary: var(--muted-foreground);
  --el-fill-color-light: var(--muted);
  --el-fill-color-lighter: color-mix(in oklab, var(--muted) 45%, var(--background));
}

:deep(.el-table) {
  --el-table-header-bg-color: var(--muted);
  --el-table-row-hover-bg-color: var(--accent);
  --el-table-border-color: var(--border);
  --el-table-tr-bg-color: transparent;
  color: var(--foreground);
}

:deep(.el-table__header th.el-table__cell) {
  height: 52px;
  background-color: var(--muted) !important;
  border-bottom: 1px solid var(--border) !important;
}

:deep(.el-table__body td.el-table__cell) {
  vertical-align: top;
}

:deep(.datatable-row-selected),
:deep(.datatable-row-selected td),
:deep(.el-table--striped .el-table__body .datatable-row-selected td.el-table__cell) {
  background: color-mix(in oklab, var(--primary) 12%, transparent);
}

:deep(.datatable-row-disabled) {
  opacity: 0.55;
}

/* Complete reset for all Element Plus table wrapper borders and pseudo-elements */
:deep(.el-table),
:deep(.el-table--border),
:deep(.el-table--group),
:deep(.el-table__inner-wrapper),
:deep(.el-table__header-wrapper),
:deep(.el-table__body-wrapper),
:deep(.el-table__footer-wrapper),
:deep(.el-table__fixed),
:deep(.el-table__fixed-left),
:deep(.el-table__fixed-right),
:deep(.el-table__fixed-right-patch) {
  border: none !important;
  outline: none !important;
  box-shadow: none !important;
}

:deep(.el-table::before),
:deep(.el-table::after),
:deep(.el-table--border::before),
:deep(.el-table--border::after),
:deep(.el-table--group::before),
:deep(.el-table--group::after),
:deep(.el-table__inner-wrapper::before),
:deep(.el-table__inner-wrapper::after),
:deep(.el-table__header-wrapper::before),
:deep(.el-table__header-wrapper::after),
:deep(.el-table__body-wrapper::before),
:deep(.el-table__body-wrapper::after),
:deep(.el-table__fixed::before),
:deep(.el-table__fixed::after),
:deep(.el-table__fixed-left::before),
:deep(.el-table__fixed-left::after),
:deep(.el-table__fixed-right::before),
:deep(.el-table__fixed-right::after) {
  display: none !important;
  content: none !important;
  border: none !important;
  width: 0 !important;
  height: 0 !important;
}

/* Inner cell column dividers & row borders */
:deep(.el-table .el-table__cell) {
  border-right: 1px solid var(--border) !important;
  border-bottom: 1px solid var(--border) !important;
  border-left: 0 !important;
}

/* Explicit high-contrast Dark Mode border & header styling */
:global(.dark) :deep(.el-table) {
  --el-table-header-bg-color: rgba(255, 255, 255, 0.07);
  --el-table-border-color: rgba(255, 255, 255, 0.18);
  --el-table-tr-bg-color: transparent;
}

:global(.dark) :deep(.el-table__header th.el-table__cell) {
  background-color: rgba(255, 255, 255, 0.08) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.22) !important;
  border-right: 1px solid rgba(255, 255, 255, 0.18) !important;
}

:global(.dark) :deep(.el-table .el-table__cell) {
  border-right: 1px solid rgba(255, 255, 255, 0.18) !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.18) !important;
}

/* Remove left border and inset shadows on first-column cells (including sticky fixed columns) */
:deep(.el-table .el-table__cell:first-child),
:deep(.el-table .el-table-fixed-column--left),
:deep(.el-table .el-table-fixed-column--left.is-first-column),
:deep(.el-table__cell.el-table-fixed-column--left),
:deep(.el-table__cell.el-table-fixed-column--left.is-first-column) {
  border-left: 0 !important;
  box-shadow: none !important;
}

:deep(.el-table .el-table-fixed-column--left.is-first-column::before),
:deep(.el-table .el-table-fixed-column--left.is-first-column::after),
:deep(.el-table__cell.el-table-fixed-column--left.is-first-column::before),
:deep(.el-table__cell.el-table-fixed-column--left.is-first-column::after) {
  display: none !important;
}

/* Remove right border on the rightmost column */
:deep(.el-table tbody tr td.el-table__cell:last-child),
:deep(.el-table thead tr:first-child th.el-table__cell:last-child) {
  border-right: 0 !important;
}

/* Rounded corner cell clipping for sticky fixed columns */
:deep(.el-table thead tr:first-child th:first-child),
:deep(.el-table thead tr:first-child th.el-table-fixed-column--left.is-first-column) {
  border-top-left-radius: calc(var(--radius) - 2px) !important;
}

:deep(.el-table tbody tr:last-child td:first-child),
:deep(.el-table tbody tr:last-child td.el-table-fixed-column--left.is-first-column) {
  border-bottom-left-radius: calc(var(--radius) - 2px) !important;
}

:deep(.el-table thead tr:first-child th:last-child) {
  border-top-right-radius: calc(var(--radius) - 2px) !important;
}

:deep(.el-table tbody tr:last-child td:last-child) {
  border-bottom-right-radius: calc(var(--radius) - 2px) !important;
}

/* Remove bottom border on the last row */
:deep(.el-table__body tr:last-child td.el-table__cell) {
  border-bottom: 0 !important;
}

:deep(.el-table__cell .cell) {
  display: flex;
  align-items: flex-start;
}

:deep(.el-table__cell.is-center .cell) {
  justify-content: center;
  align-items: center;
  text-align: center;
}

:deep(.el-table__cell.is-right .cell) {
  justify-content: flex-end;
  align-items: center;
  text-align: right;
}

:deep(.el-table__indent) {
  flex-shrink: 0;
}

:deep(.el-table__expand-icon) {
  flex-shrink: 0;
  margin-right: 4px;
  margin-top: 2px;
}

:deep(.datatable-cell-nowrap .cell) {
  white-space: nowrap;
}

:deep(.datatable-cell-ellipsis .cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
