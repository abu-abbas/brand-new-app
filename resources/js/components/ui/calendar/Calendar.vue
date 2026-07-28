<script setup lang="ts">
import type { CalendarRootEmits, CalendarRootProps } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { reactiveOmit } from '@vueuse/core';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import {
  CalendarCell,
  CalendarCellTrigger,
  CalendarGrid,
  CalendarGridBody,
  CalendarGridHead,
  CalendarGridRow,
  CalendarHeadCell,
  CalendarHeader,
  CalendarHeading,
  CalendarNext,
  CalendarPrev,
  CalendarRoot,
  useForwardPropsEmits,
} from 'reka-ui';
import { cn } from '@/lib/utils';

const props = withDefaults(
  defineProps<
    CalendarRootProps & {
      class?: HTMLAttributes['class'];
    }
  >(),
  {
    locale: 'id-ID',
    class: undefined,
  },
);

const emits = defineEmits<CalendarRootEmits>();

const delegatedProps = reactiveOmit(props, 'class');
const forwarded = useForwardPropsEmits(delegatedProps, emits);
</script>

<template>
  <CalendarRoot v-slot="{ grid, weekDays }" v-bind="forwarded" :class="cn('p-3', props.class)">
    <CalendarHeader class="relative flex items-center justify-between pt-1">
      <CalendarPrev
        class="border-input hover:bg-accent hover:text-accent-foreground flex size-7 items-center justify-center rounded-md border bg-transparent p-0 opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
      >
        <ChevronLeft class="size-4" />
      </CalendarPrev>
      <CalendarHeading class="text-sm font-medium" />
      <CalendarNext
        class="border-input hover:bg-accent hover:text-accent-foreground flex size-7 items-center justify-center rounded-md border bg-transparent p-0 opacity-70 transition-opacity hover:opacity-100 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-30"
      >
        <ChevronRight class="size-4" />
      </CalendarNext>
    </CalendarHeader>

    <div class="mt-4 flex flex-col gap-y-4 sm:flex-row sm:gap-x-4 sm:gap-y-0">
      <CalendarGrid
        v-for="month in grid"
        :key="month.value.toString()"
        class="w-full border-collapse space-y-1"
      >
        <CalendarGridHead>
          <CalendarGridRow class="flex">
            <CalendarHeadCell
              v-for="day in weekDays"
              :key="day"
              class="text-muted-foreground w-9 rounded-md text-[0.8rem] font-normal"
            >
              {{ day }}
            </CalendarHeadCell>
          </CalendarGridRow>
        </CalendarGridHead>
        <CalendarGridBody>
          <CalendarGridRow
            v-for="(weekDates, index) in month.rows"
            :key="`weekDate-${index}`"
            class="mt-2 flex w-full"
          >
            <CalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
              class="relative size-9 p-0 text-center text-sm focus-within:relative focus-within:z-20 has-data-selected:bg-accent [&:has([data-selected][data-outside-view])]:bg-accent/50 rounded-md"
            >
              <CalendarCellTrigger
                :day="weekDate"
                :month="month.value"
                class="hover:bg-accent hover:text-accent-foreground data-selected:bg-primary data-selected:text-primary-foreground data-selected:hover:bg-primary data-selected:hover:text-primary-foreground data-selected:focus:bg-primary data-selected:focus:text-primary-foreground data-disabled:text-muted-foreground/40 data-disabled:data-selected:bg-muted/80 data-disabled:data-selected:text-muted-foreground/70 data-disabled:data-selected:opacity-60 data-today:bg-accent data-today:text-accent-foreground relative flex size-9 items-center justify-center rounded-md p-0 text-center text-sm font-normal data-disabled:pointer-events-none data-unavailable:line-through data-unavailable:opacity-50 transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
              />
            </CalendarCell>
          </CalendarGridRow>
        </CalendarGridBody>
      </CalendarGrid>
    </div>
  </CalendarRoot>
</template>
