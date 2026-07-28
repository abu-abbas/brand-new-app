<script setup lang="ts">
import type { RangeCalendarRootEmits, RangeCalendarRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import {
  RangeCalendarCell,
  RangeCalendarCellTrigger,
  RangeCalendarGrid,
  RangeCalendarGridBody,
  RangeCalendarGridHead,
  RangeCalendarGridRow,
  RangeCalendarHeadCell,
  RangeCalendarNext,
  RangeCalendarPrev,
  RangeCalendarRoot,
  useForwardPropsEmits,
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    RangeCalendarRootProps & {
      class?: HTMLAttributes['class'];
    }
  >(),
  {
    locale: 'id-ID',
    numberOfMonths: 2,
    class: undefined,
  },
);

const emits = defineEmits<RangeCalendarRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);

function formatMonthHeader(dateVal: any): string {
  if (!dateVal) return '';
  const year = dateVal.year;
  const month = dateVal.month;
  if (!year || !month) return '';
  const d = new Date(year, month - 1, 1);
  return new Intl.DateTimeFormat(props.locale || 'id-ID', {
    month: 'long',
    year: 'numeric',
  }).format(d);
}
</script>

<template>
  <RangeCalendarRoot v-slot="{ grid, weekDays }" v-bind="forwarded" :class="cn('p-3', props.class)">
    <div class="flex flex-col sm:flex-row gap-y-4 sm:gap-x-6 sm:gap-y-0">
      <div v-for="(month, index) in grid" :key="month.value.toString()" class="w-full space-y-1">
        <!-- Top Row: Month Header with Navigation Buttons (outside table) -->
        <div class="flex items-center justify-between h-8 mb-2">
          <template v-if="index === 0">
            <RangeCalendarPrev
              class="border-input hover:bg-accent hover:text-accent-foreground flex size-7 items-center justify-center rounded-md border bg-transparent p-0 opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
            >
              <ChevronLeft class="size-4" />
            </RangeCalendarPrev>
          </template>
          <template v-else>
            <div class="size-7" />
          </template>

          <span class="text-sm font-semibold">
            {{ formatMonthHeader(month.value) }}
          </span>

          <template v-if="index === grid.length - 1">
            <RangeCalendarNext
              class="border-input hover:bg-accent hover:text-accent-foreground flex size-7 items-center justify-center rounded-md border bg-transparent p-0 opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
            >
              <ChevronRight class="size-4" />
            </RangeCalendarNext>
          </template>
          <template v-else>
            <div class="size-7" />
          </template>
        </div>

        <!-- Table Grid for Days and Dates -->
        <RangeCalendarGrid class="w-full border-collapse space-y-1">
          <!-- Days of Week (M S S R K J S) -->
          <RangeCalendarGridHead>
            <RangeCalendarGridRow class="flex">
              <RangeCalendarHeadCell
                v-for="day in weekDays"
                :key="day"
                class="text-muted-foreground w-9 rounded-md text-[0.8rem] font-normal"
              >
                {{ day }}
              </RangeCalendarHeadCell>
            </RangeCalendarGridRow>
          </RangeCalendarGridHead>

          <!-- Calendar Dates Grid -->
          <RangeCalendarGridBody>
            <RangeCalendarGridRow
              v-for="(weekDates, weekIdx) in month.rows"
              :key="`weekDate-${weekIdx}`"
              class="mt-2 flex w-full"
            >
              <RangeCalendarCell
                v-for="weekDate in weekDates"
                :key="weekDate.toString()"
                :date="weekDate"
                class="relative size-9 p-0 text-center text-sm focus-within:relative focus-within:z-20 has-data-selected:bg-accent [&:has([data-selected][data-selection-end])]:rounded-r-md [&:has([data-selected][data-selection-start])]:rounded-l-md [&:has([data-selected][data-outside-view])]:bg-accent/50"
              >
                <RangeCalendarCellTrigger
                  :day="weekDate"
                  :month="month.value"
                  class="hover:bg-accent hover:text-accent-foreground data-selection-start:bg-primary data-selection-start:text-primary-foreground data-selection-end:bg-primary data-selection-end:text-primary-foreground data-selected:bg-accent data-selected:text-accent-foreground data-highlighted:bg-accent/50 data-disabled:text-muted-foreground/40 data-today:bg-accent data-today:text-accent-foreground relative flex size-9 items-center justify-center rounded-md p-0 text-center text-sm font-normal data-disabled:pointer-events-none data-unavailable:line-through data-unavailable:opacity-50 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                />
              </RangeCalendarCell>
            </RangeCalendarGridRow>
          </RangeCalendarGridBody>
        </RangeCalendarGrid>
      </div>
    </div>
  </RangeCalendarRoot>
</template>
