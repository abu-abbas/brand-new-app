<script setup lang="ts">
import type { ComboboxOption } from './combobox.utils';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Check, ChevronsUpDown, Plus, RotateCcw, X } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Combobox as ComboboxRoot,
  ComboboxAnchor,
  ComboboxGroup,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
} from '@/components/ui/combobox';
import { Spinner } from '@/components/ui/spinner';
import {
  defaultFilterOption,
  getOptionLabel,
  getOptionValue,
  isSameOption,
  mergeOptions,
} from './combobox.utils';

type ComboboxMode = 'local' | 'remote';
type GroupedOptions = { key: string; options: ComboboxOption[] };

interface Props {
  options: ComboboxOption[];
  selectedOptions?: ComboboxOption[];
  mode?: ComboboxMode;
  multiple?: boolean;
  returnObject?: boolean;
  valueKey?: string;
  labelKey?: string;
  groupKey?: string;
  groupBy?: (option: ComboboxOption) => string;
  disabledKey?: string;
  isOptionDisabled?: (option: ComboboxOption) => boolean;
  filterOption?: (option: ComboboxOption, search: string) => boolean;
  placeholder?: string;
  searchPlaceholder?: string;
  loadingText?: string;
  emptyText?: string;
  errorText?: string;
  createText?: string;
  maxDisplayedItems?: number;
  maxSelectedItems?: number;
  searchDebounce?: number;
  loading?: boolean;
  loadingMore?: boolean;
  hasMore?: boolean;
  error?: unknown;
  creatable?: boolean;
  creating?: boolean;
  selectAll?: boolean;
  clearAll?: boolean;
  disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  selectedOptions: () => [],
  mode: 'local',
  multiple: false,
  returnObject: false,
  valueKey: 'value',
  labelKey: 'label',
  groupKey: undefined,
  groupBy: undefined,
  disabledKey: undefined,
  isOptionDisabled: undefined,
  filterOption: undefined,
  placeholder: 'Pilih opsi',
  searchPlaceholder: 'Cari...',
  loadingText: 'Memuat data...',
  emptyText: 'Data tidak ditemukan.',
  errorText: 'Data gagal dimuat.',
  createText: 'Buat',
  maxDisplayedItems: 3,
  maxSelectedItems: undefined,
  searchDebounce: 300,
  loading: false,
  loadingMore: false,
  hasMore: false,
  error: undefined,
  creatable: false,
  creating: false,
  selectAll: false,
  clearAll: false,
  disabled: false,
});

const emit = defineEmits<{
  search: [query: string];
  loadMore: [];
  retry: [];
  create: [query: string];
}>();

const model = defineModel<unknown>();
const open = defineModel<boolean>('open', { default: false });
const search = defineModel<string>('search', { default: '' });

const comboboxRef = ref<{ highlightedElement?: unknown }>();
const optionCache = ref<ComboboxOption[]>([]);
let searchTimer: ReturnType<typeof globalThis.setTimeout> | undefined;

const modelValues = computed(() => {
  if (props.multiple) {
    return Array.isArray(model.value) ? model.value : [];
  }

  return model.value == null ? [] : [model.value];
});

const knownOptions = computed(() =>
  mergeOptions([props.selectedOptions, optionCache.value, props.options], props.valueKey),
);

const selectedItems = computed(() => {
  if (props.returnObject) {
    return modelValues.value.filter(isComboboxOption);
  }

  return modelValues.value
    .map((value) =>
      knownOptions.value.find((option) => Object.is(getOptionValue(option, props.valueKey), value)),
    )
    .filter(isComboboxOption);
});

const visibleSelectedItems = computed(() => selectedItems.value.slice(0, props.maxDisplayedItems));
const hiddenSelectedCount = computed(
  () => selectedItems.value.length - visibleSelectedItems.value.length,
);
const isSelectionAtLimit = computed(
  () =>
    props.multiple &&
    props.maxSelectedItems != null &&
    selectedItems.value.length >= props.maxSelectedItems,
);

const filteredOptions = computed(() => {
  if (props.mode === 'remote' || !search.value.trim()) {
    return props.options;
  }

  const query = search.value.trim();
  return props.options.filter((option) =>
    props.filterOption
      ? props.filterOption(option, query)
      : defaultFilterOption(option, query, props.labelKey),
  );
});

const groupedOptions = computed<GroupedOptions[]>(() => {
  if (!props.groupBy && !props.groupKey) {
    return [{ key: '', options: filteredOptions.value }];
  }

  const groups = new Map<string, ComboboxOption[]>();
  for (const option of filteredOptions.value) {
    const key = props.groupBy
      ? props.groupBy(option)
      : String(option[props.groupKey as string] ?? 'Lainnya');
    groups.set(key, [...(groups.get(key) ?? []), option]);
  }

  return [...groups].map(([key, options]) => ({ key, options }));
});

const hasExactMatch = computed(() =>
  props.options.some(
    (option) =>
      getOptionLabel(option, props.labelKey).toLocaleLowerCase() ===
      search.value.trim().toLocaleLowerCase(),
  ),
);
const canCreate = computed(
  () => props.creatable && search.value.trim().length > 0 && !hasExactMatch.value,
);
const canSelectAll = computed(() => props.selectAll && props.multiple && props.mode === 'local');

watch(
  () => props.selectedOptions,
  (options) => {
    optionCache.value = mergeOptions([optionCache.value, options], props.valueKey);
  },
  { immediate: true, deep: true },
);

watch(open, (isOpen) => {
  if (isOpen && props.mode === 'remote') {
    emitSearchImmediately();
  }
});

onBeforeUnmount(() => globalThis.clearTimeout(searchTimer));

function isComboboxOption(value: unknown): value is ComboboxOption {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function isSelected(option: ComboboxOption): boolean {
  return selectedItems.value.some((selected) => isSameOption(selected, option, props.valueKey));
}

function isDisabled(option: ComboboxOption): boolean {
  const explicitlyDisabled = props.isOptionDisabled
    ? props.isOptionDisabled(option)
    : props.disabledKey
      ? Boolean(option[props.disabledKey])
      : false;

  return explicitlyDisabled || (isSelectionAtLimit.value && !isSelected(option));
}

function updateSearch(value: string | number) {
  search.value = String(value);

  if (props.mode !== 'remote') return;

  globalThis.clearTimeout(searchTimer);
  searchTimer = globalThis.setTimeout(
    () => emit('search', search.value.trim()),
    props.searchDebounce,
  );
}

function emitSearchImmediately() {
  globalThis.clearTimeout(searchTimer);
  emit('search', search.value.trim());
}

function updateSelection(value: unknown) {
  const items = (Array.isArray(value) ? value : value == null ? [] : [value]).filter(
    isComboboxOption,
  );
  optionCache.value = mergeOptions([optionCache.value, items], props.valueKey);
  model.value = props.multiple
    ? items.map((item) => (props.returnObject ? item : getOptionValue(item, props.valueKey)))
    : items[0]
      ? props.returnObject
        ? items[0]
        : getOptionValue(items[0], props.valueKey)
      : null;
  search.value = '';

  if (!props.multiple) {
    open.value = false;
  } else if (props.mode === 'remote') {
    emitSearchImmediately();
  }
}

function removeOption(option: ComboboxOption) {
  updateSelection(
    selectedItems.value.filter((selected) => !isSameOption(selected, option, props.valueKey)),
  );
}

function clearSelection() {
  updateSelection([]);
}

function selectEveryOption() {
  const selectable = filteredOptions.value.filter((option) => !isDisabled(option));
  const limit = props.maxSelectedItems ?? selectable.length;
  updateSelection(mergeOptions([selectedItems.value, selectable], props.valueKey).slice(0, limit));
}

function handleEnter(event: { preventDefault: () => void; stopPropagation: () => void }) {
  if (hasHighlightedOption()) return;

  event.preventDefault();
  event.stopPropagation();

  if (canCreate.value) {
    emit('create', search.value.trim());
  } else if (props.mode === 'remote') {
    emitSearchImmediately();
  }
}

function hasHighlightedOption(): boolean {
  const highlighted = comboboxRef.value?.highlightedElement;
  if (typeof HTMLElement !== 'undefined' && highlighted instanceof HTMLElement) return true;

  return isComboboxOption(highlighted) && highlighted.value instanceof HTMLElement;
}

function handleViewportScroll(event: { currentTarget: HTMLElement | null }) {
  if (
    props.mode !== 'remote' ||
    !props.hasMore ||
    props.loadingMore ||
    props.loading ||
    props.error
  ) {
    return;
  }

  const viewport = event.currentTarget;
  if (!viewport) return;
  if (viewport.scrollHeight - viewport.scrollTop - viewport.clientHeight <= 32) {
    emit('loadMore');
  }
}
</script>

<template>
  <ComboboxRoot
    ref="comboboxRef"
    :model-value="multiple ? selectedItems : selectedItems[0]"
    :open="open"
    :multiple="multiple"
    :disabled="disabled"
    :by="valueKey"
    :ignore-filter="true"
    :reset-search-term-on-blur="false"
    :reset-search-term-on-select="false"
    @update:model-value="updateSelection"
    @update:open="open = $event"
  >
    <ComboboxAnchor as-child>
      <ComboboxTrigger as-child>
        <Button
          type="button"
          variant="outline"
          role="combobox"
          :aria-expanded="open"
          :disabled="disabled"
          class="h-auto min-h-9 w-full justify-between px-3 py-1.5 font-normal"
        >
          <span v-if="selectedItems.length === 0" class="truncate text-muted-foreground">
            {{ placeholder }}
          </span>

          <span v-else-if="!multiple" class="min-w-0 truncate text-left">
            <slot name="selected" :item="selectedItems[0]" :remove="removeOption">
              {{ getOptionLabel(selectedItems[0], labelKey) }}
            </slot>
          </span>

          <span v-else class="flex min-w-0 flex-1 flex-wrap gap-1">
            <Badge
              v-for="item in visibleSelectedItems"
              :key="String(getOptionValue(item, valueKey))"
              variant="secondary"
              class="max-w-full gap-1"
            >
              <slot name="selected" :item="item" :remove="removeOption">
                <span class="truncate">{{ getOptionLabel(item, labelKey) }}</span>
              </slot>
              <span
                role="button"
                tabindex="0"
                :aria-label="`Hapus ${getOptionLabel(item, labelKey)}`"
                class="rounded-sm opacity-60 hover:opacity-100 focus-visible:outline-none focus-visible:ring-2"
                @click.stop="removeOption(item)"
                @keydown.enter.stop.prevent="removeOption(item)"
                @keydown.space.stop.prevent="removeOption(item)"
              >
                <X class="size-3" />
              </span>
            </Badge>
            <Badge v-if="hiddenSelectedCount > 0" variant="outline"
              >+{{ hiddenSelectedCount }}</Badge
            >
          </span>

          <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
        </Button>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxList align="start">
      <ComboboxInput
        :model-value="search"
        :placeholder="searchPlaceholder"
        @update:model-value="updateSearch"
        @keydown.enter="handleEnter"
      />

      <div
        v-if="canSelectAll || (clearAll && selectedItems.length)"
        class="flex gap-1 border-b p-1"
      >
        <Button
          v-if="canSelectAll"
          type="button"
          size="sm"
          variant="ghost"
          @click="selectEveryOption"
        >
          Pilih semua
        </Button>
        <Button
          v-if="clearAll && selectedItems.length"
          type="button"
          size="sm"
          variant="ghost"
          @click="clearSelection"
        >
          Bersihkan
        </Button>
      </div>

      <div
        v-if="loading"
        class="flex items-center justify-center gap-2 px-3 py-6 text-sm text-muted-foreground"
      >
        <slot name="loading">
          <Spinner class="size-4" />
          {{ loadingText }}
        </slot>
      </div>

      <div
        v-else-if="error"
        class="flex flex-col items-center gap-2 px-3 py-6 text-center text-sm text-muted-foreground"
      >
        <slot name="error" :error="error" :retry="() => emit('retry')">
          <span>{{ errorText }}</span>
          <Button type="button" size="sm" variant="outline" @click="emit('retry')">
            <RotateCcw class="size-4" />
            Coba lagi
          </Button>
        </slot>
      </div>

      <ComboboxViewport v-else @scroll="handleViewportScroll">
        <template v-for="group in groupedOptions" :key="group.key">
          <ComboboxGroup :heading="group.key || undefined">
            <template v-if="group.key" #heading>
              <slot name="group" :group="group.key" :options="group.options">
                {{ group.key }}
              </slot>
            </template>
            <ComboboxItem
              v-for="option in group.options"
              :key="String(getOptionValue(option, valueKey))"
              :value="option"
              :text-value="getOptionLabel(option, labelKey)"
              :disabled="isDisabled(option)"
            >
              <slot
                name="option"
                :item="option"
                :selected="isSelected(option)"
                :disabled="isDisabled(option)"
              >
                {{ getOptionLabel(option, labelKey) }}
              </slot>
              <ComboboxItemIndicator>
                <Check class="size-4" />
              </ComboboxItemIndicator>
            </ComboboxItem>
          </ComboboxGroup>
        </template>

        <button
          v-if="canCreate"
          type="button"
          class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent"
          :disabled="creating"
          @click="emit('create', search.trim())"
        >
          <Spinner v-if="creating" class="size-4" />
          <Plus v-else class="size-4" />
          <slot name="create" :search="search">{{ createText }} “{{ search.trim() }}”</slot>
        </button>

        <div
          v-if="groupedOptions.every((group) => group.options.length === 0) && !canCreate"
          class="px-3 py-6 text-center text-sm text-muted-foreground"
        >
          <slot name="empty">{{ emptyText }}</slot>
        </div>

        <div
          v-if="loadingMore"
          class="flex items-center justify-center gap-2 px-3 py-3 text-sm text-muted-foreground"
        >
          <Spinner class="size-4" />
          {{ loadingText }}
        </div>
      </ComboboxViewport>
    </ComboboxList>
  </ComboboxRoot>
</template>
