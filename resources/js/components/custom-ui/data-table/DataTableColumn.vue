<script setup lang="ts" generic="T extends Record<string, unknown>">
import { ElTableColumn } from 'element-plus';
import type { DataTableField, DataTableSort } from './data-table.types';
import { getPath } from './data-table.utils';

defineOptions({ name: 'DataTableColumn' });

defineProps<{
  field: DataTableField<T>;
  sorts: DataTableSort[];
  slots: Record<string, ((props: Record<string, unknown>) => unknown) | undefined>;
  search: string;
  tree: boolean;
  valueFor: (row: T, field: DataTableField<T>, rowIndex: number) => unknown;
  highlight: (value: unknown) => Array<{ text: string; match: boolean }>;
  columnWidths?: Record<string, number>;
}>();

const emit = defineEmits<{
  sort: [field: DataTableField<T>, event: { shiftKey: boolean }];
}>();
</script>

<template>
  <ElTableColumn
    v-if="field.children?.length"
    :label="field.label"
    :header-align="field.headerAlign"
    :fixed="field.fixed"
  >
    <DataTableColumn
      v-for="child in field.children.filter((item) => !item.hidden)"
      :key="child.key"
      :field="child"
      :sorts="sorts"
      :slots="slots"
      :search="search"
      :tree="tree"
      :value-for="valueFor"
      :highlight="highlight"
      :column-widths="columnWidths"
      @sort="(childField, event) => emit('sort', childField, event)"
    />
  </ElTableColumn>

  <ElTableColumn
    v-else
    :prop="field.key"
    :label="field.label"
    :width="columnWidths?.[field.key] ?? field.width"
    :min-width="field.minWidth"
    :align="field.align"
    :header-align="field.headerAlign"
    :fixed="field.fixed"
    :resizable="field.resizable !== false"
  >
    <template #header>
      <button
        v-if="field.key !== 'rownum' && field.sortable !== false"
        type="button"
        class="inline-flex items-center gap-1 rounded-xs focus-visible:outline-2 focus-visible:outline-ring"
        :aria-sort="
          sorts.find((sort) => sort.key === field.key)?.direction === 'asc'
            ? 'ascending'
            : sorts.find((sort) => sort.key === field.key)?.direction === 'desc'
              ? 'descending'
              : 'none'
        "
        @click="emit('sort', field, $event)"
        @keydown.enter.prevent="emit('sort', field, $event)"
      >
        <component
          :is="slots[`header(${field.key})`]"
          v-if="slots[`header(${field.key})`]"
          :column="field"
          :sort-direction="sorts.find((sort) => sort.key === field.key)?.direction"
        />
        <template v-else>{{ field.label }}</template>

        <span v-if="sorts.find((sort) => sort.key === field.key)" class="text-xs">
          {{ sorts.find((sort) => sort.key === field.key)?.direction === 'asc' ? '↑' : '↓' }}
          <sup v-if="sorts.length > 1">{{
            sorts.findIndex((sort) => sort.key === field.key) + 1
          }}</sup>
        </span>
      </button>

      <component
        :is="slots[`header(${field.key})`]"
        v-else-if="slots[`header(${field.key})`]"
        :column="field"
      />

      <span v-else>{{ field.label }}</span>
    </template>

    <template #default="{ row, $index }">
      <component
        :is="slots[`cell(${field.key})`]"
        v-if="slots[`cell(${field.key})`]"
        :row="row"
        :value="getPath(row, field.key)"
        :column="field"
        :row-index="$index"
        :search="search"
        :highlight="highlight"
      />
      <template v-else>
        <template v-for="(part, index) in highlight(valueFor(row, field, $index))" :key="index">
          <mark v-if="part.match" class="bg-primary/20 text-foreground rounded-xs px-0.5">{{
            part.text
          }}</mark>
          <template v-else>{{ part.text }}</template>
        </template>
      </template>
    </template>
  </ElTableColumn>
</template>
