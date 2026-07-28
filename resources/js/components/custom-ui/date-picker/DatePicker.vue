<script setup lang="ts">
import type { DateRange, DateValue } from 'reka-ui';
import type { HTMLAttributes } from 'vue';
import { computed, ref, watch } from 'vue';
import { CalendarDate, getLocalTimeZone, parseDate, today } from '@internationalized/date';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { cn } from '@/lib/utils';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';
import { getActivePinia } from 'pinia';

export interface DateRangeValue {
  start?: string | null;
  end?: string | null;
}

export interface DatePreset {
  label: string;
  value: string | DateRangeValue | [string, string];
}

defineOptions({
  name: 'DatePicker',
});

const props = withDefaults(
  defineProps<{
    modelValue?: string | DateRangeValue | [string, string] | null;
    mode?: 'single' | 'range' | 'month';
    placeholder?: string;
    locale?: string;
    minDate?: string;
    maxDate?: string;
    presets?: boolean | DatePreset[];
    clearable?: boolean;
    allowManualInput?: boolean;
    displayFormat?: string | Intl.DateTimeFormatOptions | ((isoStr: string) => string);
    numberOfMonths?: number;
    disabled?: boolean;
    size?: 'sm' | 'default' | 'lg';
    class?: HTMLAttributes['class'];
  }>(),
  {
    modelValue: null,
    mode: 'single',
    placeholder: undefined,
    locale: undefined,
    minDate: undefined,
    maxDate: undefined,
    presets: false,
    clearable: false,
    allowManualInput: false,
    displayFormat: undefined,
    numberOfMonths: undefined,
    disabled: false,
    size: 'default',
    class: undefined,
  },
);

let appBootstrap: ReturnType<typeof useAppBootstrapStore> | null = null;
try {
  if (getActivePinia()) {
    appBootstrap = useAppBootstrapStore();
  }
} catch {
  appBootstrap = null;
}

const effectiveLocale = computed(() => {
  const loc = props.locale || appBootstrap?.config?.locale || 'id-ID';
  if (loc === 'id' || loc === 'id_ID') return 'id-ID';
  if (loc === 'en' || loc === 'en_US') return 'en-US';
  return loc.replace('_', '-');
});

const effectiveNumberOfMonths = computed(() => {
  if (props.numberOfMonths !== undefined) return props.numberOfMonths;
  return props.mode === 'range' ? 2 : 1;
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | DateRangeValue | [string, string] | null): void;
  (e: 'change', value: string | DateRangeValue | [string, string] | null): void;
  (e: 'clear'): void;
}>();

const isOpen = ref(false);
const manualInputValue = ref('');

const currentDateStr = computed(
  () => appBootstrap?.config?.current_date || new Date().toISOString().split('T')[0],
);

const currentDateObj = computed(() => {
  try {
    return parseDate(currentDateStr.value.trim().split('T')[0]);
  } catch {
    return today(getLocalTimeZone());
  }
});

const currentViewYear = ref(currentDateObj.value.year);

// Sync currentViewYear from modelValue if available
watch(
  () => props.modelValue,
  (val) => {
    if (typeof val === 'string' && val) {
      const parts = val.split('-');
      if (parts[0] && !isNaN(Number(parts[0]))) {
        currentViewYear.value = Number(parts[0]);
      }
    }
  },
  { immediate: true },
);

// List of 12 months for Month Mode
const monthsList = computed(() => {
  return Array.from({ length: 12 }, (_, i) => {
    const d = new Date(2026, i, 1);
    return {
      monthIndex: i,
      monthNumber: String(i + 1).padStart(2, '0'),
      labelShort: new Intl.DateTimeFormat(effectiveLocale.value, { month: 'short' }).format(d),
      labelLong: new Intl.DateTimeFormat(effectiveLocale.value, { month: 'long' }).format(d),
    };
  });
});

// Helper: ISO String (YYYY-MM-DD) <-> CalendarDate
function toCalendarDate(isoStr?: string | null): CalendarDate | undefined {
  if (!isoStr || typeof isoStr !== 'string') return undefined;
  try {
    const cleanStr = isoStr.trim().split('T')[0];
    return parseDate(cleanStr);
  } catch {
    return undefined;
  }
}

function toIsoString(dateVal?: DateValue | null): string | null {
  if (!dateVal) return null;
  const y = dateVal.year;
  const m = String(dateVal.month).padStart(2, '0');
  const d = String(dateVal.day).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

// Format display text using Intl.DateTimeFormat (id-ID) or custom displayFormat
function formatDisplayDate(isoStr?: string | null): string {
  if (!isoStr) return '';

  // Handle YYYY-MM month format
  let dateObj: Date;
  if (/^\d{4}-\d{2}$/.test(isoStr)) {
    dateObj = new Date(isoStr + '-01T00:00:00');
  } else {
    dateObj = new Date(isoStr + 'T00:00:00');
  }

  if (isNaN(dateObj.getTime())) return isoStr;

  if (typeof props.displayFormat === 'function') {
    return props.displayFormat(isoStr);
  }

  if (typeof props.displayFormat === 'object' && props.displayFormat !== null) {
    return new Intl.DateTimeFormat(effectiveLocale.value, props.displayFormat).format(dateObj);
  }

  if (typeof props.displayFormat === 'string') {
    const fmt = props.displayFormat.trim();
    const dayNumeric = String(dateObj.getDate());
    const day2Digit = String(dateObj.getDate()).padStart(2, '0');
    const monthNumeric = String(dateObj.getMonth() + 1);
    const month2Digit = String(dateObj.getMonth() + 1).padStart(2, '0');
    const monthShort = new Intl.DateTimeFormat(effectiveLocale.value, { month: 'short' }).format(
      dateObj,
    );
    const monthLong = new Intl.DateTimeFormat(effectiveLocale.value, { month: 'long' }).format(
      dateObj,
    );
    const year4Digit = String(dateObj.getFullYear());

    if (fmt === 'DD/MM/YYYY') return `${day2Digit}/${month2Digit}/${year4Digit}`;
    if (fmt === 'DD-MM-YYYY') return `${day2Digit}-${month2Digit}-${year4Digit}`;
    if (fmt === 'YYYY-MM-DD') return `${year4Digit}-${month2Digit}-${day2Digit}`;
    if (fmt === 'YYYY-MM') return `${year4Digit}-${month2Digit}`;
    if (fmt === 'MM/YYYY') return `${month2Digit}/${year4Digit}`;
    if (fmt === 'MMMM YYYY') return `${monthLong} ${year4Digit}`;
    if (fmt === 'MMM YYYY') return `${monthShort} ${year4Digit}`;
    if (fmt === 'DD MMMM YYYY') return `${day2Digit} ${monthLong} ${year4Digit}`;
    if (fmt === 'D MMMM YYYY') return `${dayNumeric} ${monthLong} ${year4Digit}`;
    if (fmt === 'DD MMM YYYY') return `${day2Digit} ${monthShort} ${year4Digit}`;
    if (fmt === 'D MMM YYYY') return `${dayNumeric} ${monthShort} ${year4Digit}`;
    if (fmt === 'D/M/YYYY') return `${dayNumeric}/${monthNumeric}/${year4Digit}`;
    if (fmt === 'M/D/YYYY') return `${monthNumeric}/${dayNumeric}/${year4Digit}`;
  }

  if (props.mode === 'month') {
    return new Intl.DateTimeFormat(effectiveLocale.value, {
      month: 'long',
      year: 'numeric',
    }).format(dateObj);
  }

  return new Intl.DateTimeFormat(effectiveLocale.value, {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(dateObj);
}

// Convert props.minDate and props.maxDate
const minCalendarDate = computed(() => toCalendarDate(props.minDate));
const maxCalendarDate = computed(() => toCalendarDate(props.maxDate));

// Single Mode Internal State
const singleValue = computed<CalendarDate | undefined>({
  get() {
    if (props.mode !== 'single') return undefined;
    if (typeof props.modelValue === 'string') {
      return toCalendarDate(props.modelValue);
    }
    return undefined;
  },
  set(val: CalendarDate | undefined) {
    const isoVal = val ? toIsoString(val) : null;
    emit('update:modelValue', isoVal);
    emit('change', isoVal);
  },
});

// Range Mode Internal State
const rangeValue = computed<DateRange>({
  get(): DateRange {
    if (props.mode !== 'range' || !props.modelValue) {
      return { start: undefined, end: undefined };
    }

    let startStr: string | undefined;
    let endStr: string | undefined;

    if (Array.isArray(props.modelValue)) {
      startStr = props.modelValue[0];
      endStr = props.modelValue[1];
    } else if (typeof props.modelValue === 'object') {
      startStr = props.modelValue.start || undefined;
      endStr = props.modelValue.end || undefined;
    }

    return {
      start: toCalendarDate(startStr),
      end: toCalendarDate(endStr),
    };
  },
  set(val: DateRange) {
    const startIso = val.start ? toIsoString(val.start) : null;
    const endIso = val.end ? toIsoString(val.end) : null;

    if (Array.isArray(props.modelValue)) {
      const result: [string, string] = [startIso || '', endIso || ''];
      emit('update:modelValue', result);
      emit('change', result);
    } else {
      const result: DateRangeValue = { start: startIso, end: endIso };
      emit('update:modelValue', result);
      emit('change', result);
    }
  },
});

// Sync manual input value text
watch(
  () => props.modelValue,
  (val) => {
    if (props.mode === 'single' || props.mode === 'month') {
      manualInputValue.value = typeof val === 'string' ? val : '';
    } else if (props.mode === 'range') {
      if (Array.isArray(val)) {
        manualInputValue.value = val.filter(Boolean).join(' s/d ');
      } else if (val && typeof val === 'object') {
        const parts = [val.start, val.end].filter(Boolean);
        manualInputValue.value = parts.join(' s/d ');
      } else {
        manualInputValue.value = '';
      }
    }
  },
  { immediate: true },
);

// Display label in button
const displayLabel = computed(() => {
  if (props.mode === 'month') {
    if (typeof props.modelValue === 'string' && props.modelValue) {
      return formatDisplayDate(props.modelValue);
    }
    return props.placeholder || 'Pilih bulan';
  }

  if (props.mode === 'single') {
    if (typeof props.modelValue === 'string' && props.modelValue) {
      return formatDisplayDate(props.modelValue);
    }
    return props.placeholder || 'Pilih tanggal';
  }

  // Range mode
  if (props.modelValue) {
    let startStr: string | null = null;
    let endStr: string | null = null;

    if (Array.isArray(props.modelValue)) {
      startStr = props.modelValue[0] || null;
      endStr = props.modelValue[1] || null;
    } else if (typeof props.modelValue === 'object') {
      startStr = props.modelValue.start || null;
      endStr = props.modelValue.end || null;
    }

    if (startStr && endStr) {
      return `${formatDisplayDate(startStr)} - ${formatDisplayDate(endStr)}`;
    }
    if (startStr) {
      return `${formatDisplayDate(startStr)} - ...`;
    }
  }

  return props.placeholder || 'Pilih rentang tanggal';
});

// Month Mode Selection
function isMonthSelected(monthNumber: string) {
  if (typeof props.modelValue !== 'string') return false;
  return props.modelValue === `${currentViewYear.value}-${monthNumber}`;
}

function selectMonth(monthNumber: string) {
  const formattedMonth = `${currentViewYear.value}-${monthNumber}`;
  emit('update:modelValue', formattedMonth);
  emit('change', formattedMonth);
  isOpen.value = false;
}

// Default Presets
const computedPresets = computed<DatePreset[]>(() => {
  if (!props.presets) return [];

  if (Array.isArray(props.presets)) {
    return props.presets;
  }

  const now = currentDateObj.value;

  if (props.mode === 'month') {
    const curYear = now.year;
    const curMonth = String(now.month).padStart(2, '0');
    const prevMonthDate = now.subtract({ months: 1 });
    const prevMonthStr = `${prevMonthDate.year}-${String(prevMonthDate.month).padStart(2, '0')}`;

    return [
      { label: 'Bulan Ini', value: `${curYear}-${curMonth}` },
      { label: 'Bulan Lalu', value: prevMonthStr },
      { label: 'Awal Tahun Ini', value: `${curYear}-01` },
      { label: 'Akhir Tahun Ini', value: `${curYear}-12` },
    ];
  }

  if (props.mode === 'single') {
    return [
      { label: 'Hari Ini', value: toIsoString(now) || '' },
      { label: 'Besok', value: toIsoString(now.add({ days: 1 })) || '' },
      { label: '7 Hari Lagi', value: toIsoString(now.add({ days: 7 })) || '' },
      {
        label: 'Awal Bulan Ini',
        value: toIsoString(new CalendarDate(now.year, now.month, 1)) || '',
      },
    ];
  }

  // Range Presets
  const formatRange = (
    start: CalendarDate,
    end: CalendarDate,
  ): DateRangeValue | [string, string] => {
    const s = toIsoString(start) || '';
    const e = toIsoString(end) || '';
    return Array.isArray(props.modelValue) ? [s, e] : { start: s, end: e };
  };

  const startOfMonth = new CalendarDate(now.year, now.month, 1);
  const prevMonthDate = now.subtract({ months: 1 });
  const startOfPrevMonth = new CalendarDate(prevMonthDate.year, prevMonthDate.month, 1);
  const endOfPrevMonth = startOfMonth.subtract({ days: 1 });

  return [
    { label: 'Hari Ini', value: formatRange(now, now) },
    { label: '7 Hari Terakhir', value: formatRange(now.subtract({ days: 6 }), now) },
    { label: '30 Hari Terakhir', value: formatRange(now.subtract({ days: 29 }), now) },
    { label: 'Bulan Ini', value: formatRange(startOfMonth, now) },
    { label: 'Bulan Lalu', value: formatRange(startOfPrevMonth, endOfPrevMonth) },
  ];
});

function applyPreset(preset: DatePreset) {
  emit('update:modelValue', preset.value);
  emit('change', preset.value);
  isOpen.value = false;
}

function handleClear(e: MouseEvent) {
  e.stopPropagation();
  const emptyValue: [string, string] | DateRangeValue | null =
    props.mode === 'range' ? (Array.isArray(props.modelValue) ? ['', ''] : null) : null;
  emit('update:modelValue', emptyValue);
  emit('change', emptyValue);
  emit('clear');
}

function handleManualInputBlur() {
  if (!props.allowManualInput || !manualInputValue.value.trim()) return;

  const raw = manualInputValue.value.trim();

  if (props.mode === 'month') {
    if (/^\d{4}-\d{2}$/.test(raw)) {
      emit('update:modelValue', raw);
      emit('change', raw);
    }
  } else if (props.mode === 'single') {
    const parsed = toCalendarDate(raw);
    if (parsed) {
      const iso = toIsoString(parsed);
      emit('update:modelValue', iso);
      emit('change', iso);
    }
  }
}
</script>

<template>
  <div :class="cn('relative inline-block w-full', props.class)">
    <Popover v-model:open="isOpen">
      <PopoverTrigger as-child :disabled="props.disabled">
        <Button
          variant="outline"
          :disabled="props.disabled"
          :class="
            cn(
              'w-full justify-start text-left font-normal transition-colors',
              !props.modelValue && 'text-muted-foreground',
              props.size === 'sm' && 'h-8 px-2 text-xs',
              props.size === 'lg' && 'h-11 px-4 text-base',
            )
          "
        >
          <CalendarIcon class="mr-2 size-4 shrink-0 opacity-60" />

          <template v-if="props.allowManualInput">
            <input
              v-model="manualInputValue"
              type="text"
              :disabled="props.disabled"
              :placeholder="
                props.placeholder ||
                (props.mode === 'month'
                  ? 'YYYY-MM'
                  : props.mode === 'single'
                    ? 'YYYY-MM-DD'
                    : 'YYYY-MM-DD s/d YYYY-MM-DD')
              "
              class="w-full bg-transparent outline-none placeholder:text-muted-foreground"
              @blur="handleManualInputBlur"
              @click.stop
            />
          </template>
          <template v-else>
            <span class="truncate">{{ displayLabel }}</span>
          </template>

          <Button
            v-if="props.clearable && props.modelValue"
            type="button"
            data-slot="clear-button"
            variant="ghost"
            size="icon"
            class="ml-auto size-5 rounded-full p-0 hover:bg-muted"
            @click="handleClear"
          >
            <X class="size-3" />
            <span class="sr-only">Hapus tanggal</span>
          </Button>
        </Button>
      </PopoverTrigger>

      <PopoverContent class="w-auto p-0" align="start">
        <div class="flex flex-col sm:flex-row">
          <!-- Presets Sidebar -->
          <div
            v-if="computedPresets.length > 0"
            class="border-b sm:border-b-0 sm:border-r bg-muted/20 border-border p-2 flex sm:flex-col gap-1 overflow-x-auto min-w-36"
          >
            <span class="text-xs font-semibold text-muted-foreground px-2 py-1 hidden sm:block">
              Preset {{ props.mode === 'month' ? 'Bulan' : 'Tanggal' }}
            </span>
            <Button
              v-for="preset in computedPresets"
              :key="preset.label"
              variant="ghost"
              size="sm"
              class="justify-start text-2sm font-normal h-8"
              @click="applyPreset(preset)"
            >
              {{ preset.label }}
            </Button>
          </div>

          <!-- Calendar / Month Picker Render -->
          <div class="p-1">
            <!-- Month Mode Grid -->
            <div v-if="props.mode === 'month'" class="p-3 w-64 space-y-3">
              <div class="flex items-center justify-between">
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  class="size-7 p-0"
                  @click="currentViewYear--"
                >
                  <ChevronLeft class="size-4" />
                </Button>
                <span class="text-sm font-semibold">{{ currentViewYear }}</span>
                <Button
                  type="button"
                  variant="outline"
                  size="icon"
                  class="size-7 p-0"
                  @click="currentViewYear++"
                >
                  <ChevronRight class="size-4" />
                </Button>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <Button
                  v-for="m in monthsList"
                  :key="m.monthNumber"
                  type="button"
                  :variant="
                    isMonthSelected(m.monthNumber)
                      ? props.disabled
                        ? 'secondary'
                        : 'default'
                      : 'secondary'
                  "
                  :disabled="props.disabled"
                  size="sm"
                  class="h-12 text-sm"
                  @click="selectMonth(m.monthNumber)"
                >
                  {{ m.labelShort }}
                </Button>
              </div>
            </div>

            <!-- Single Date Calendar -->
            <Calendar
              v-else-if="props.mode === 'single'"
              v-model="singleValue"
              :locale="effectiveLocale"
              :disabled="props.disabled"
              :min-value="minCalendarDate"
              :max-value="maxCalendarDate"
            />

            <!-- Range Date Calendar -->
            <RangeCalendar
              v-else-if="props.mode === 'range'"
              v-model="rangeValue"
              :locale="effectiveLocale"
              :disabled="props.disabled"
              :number-of-months="effectiveNumberOfMonths"
              :min-value="minCalendarDate"
              :max-value="maxCalendarDate"
            />
          </div>
        </div>
      </PopoverContent>
    </Popover>
  </div>
</template>
