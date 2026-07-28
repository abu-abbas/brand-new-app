<script setup lang="ts">
import { ref } from 'vue';
import DatePicker from './DatePicker.vue';
import type { DateRangeValue } from './DatePicker.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const singleDate = ref<string | null>('2026-07-28');
const singleDatePresets = ref<string | null>(null);
const customFormatDate = ref<string | null>('2026-07-28');
const monthDate = ref<string | null>('2026-07');
const monthDatePresets = ref<string | null>(null);
const rangeDate = ref<DateRangeValue | null>({
  start: '2026-07-01',
  end: '2026-07-28',
});
const rangeDateArray = ref<[string, string] | null>(['2026-07-10', '2026-07-20']);
const manualDate = ref<string | null>('2026-08-17');

const minDateLimit = '2026-07-01';
const maxDateLimit = '2026-08-31';
</script>

<template>
  <div class="grid gap-6 md:grid-cols-2">
    <!-- Single Date & Month Picker Examples -->
    <Card>
      <CardHeader>
        <CardTitle>Single Date & Month Picker</CardTitle>
        <CardDescription>
          Pemilih tanggal tunggal dan pemilih bulan (mode="month" YYYY-MM) dengan locale Indonesia.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Month Picker Mode -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Month Picker (mode="month")</label>
          <DatePicker v-model="monthDate" mode="month" placeholder="Pilih bulan & tahun" />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ monthDate }}</p>
        </div>

        <!-- Month Picker with Presets & Clearable -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Month Picker (dengan Presets & Clearable)</label>
          <DatePicker
            v-model="monthDatePresets"
            mode="month"
            presets
            clearable
            placeholder="Pilih periode bulan"
          />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ monthDatePresets }}</p>
        </div>

        <!-- Basic Single Date -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Single Date (Default Format)</label>
          <DatePicker v-model="singleDate" placeholder="Pilih tanggal transaksi" />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ singleDate }}</p>
        </div>

        <!-- Single Date with Presets & Clearable -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Single Date (dengan Presets & Clearable)</label>
          <DatePicker
            v-model="singleDatePresets"
            presets
            clearable
            placeholder="Pilih tanggal jatuh tempo"
          />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ singleDatePresets }}</p>
        </div>

        <!-- Single Date with Min/Max limit -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Pembatasan Tanggal (Juli - Agustus 2026)</label>
          <DatePicker
            v-model="singleDate"
            :min-date="minDateLimit"
            :max-date="maxDateLimit"
            clearable
          />
          <p class="text-xs text-muted-foreground font-mono">
            minDate: {{ minDateLimit }}, maxDate: {{ maxDateLimit }}
          </p>
        </div>

        <!-- Custom Display Format -->
        <div class="space-y-2">
          <label class="text-sm font-medium">
            Custom Display Format (DD/MM/YYYY & DD MMMM YYYY)
          </label>
          <div class="grid grid-cols-2 gap-2">
            <DatePicker
              v-model="customFormatDate"
              display-format="DD/MM/YYYY"
              placeholder="DD/MM/YYYY"
            />
            <DatePicker
              v-model="customFormatDate"
              display-format="DD MMMM YYYY"
              placeholder="DD MMMM YYYY"
            />
          </div>
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ customFormatDate }}</p>
        </div>
      </CardContent>
    </Card>

    <!-- Date Range Picker Examples -->
    <Card>
      <CardHeader>
        <CardTitle>Date Range Picker</CardTitle>
        <CardDescription>
          Pemilih rentang tanggal untuk filter laporan, statistik, dan pencarian berbasis periode.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-6">
        <!-- Range Date Object Model -->
        <div class="space-y-2">
          <label class="text-sm font-medium">
            Range Date ({ start, end } model dengan Presets)
          </label>
          <DatePicker
            v-model="rangeDate"
            mode="range"
            presets
            clearable
            placeholder="Pilih periode laporan"
          />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ rangeDate }}</p>
        </div>

        <!-- Range Date Array Model & Custom Format -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Range Date (Custom Format DD/MM/YYYY)</label>
          <DatePicker
            v-model="rangeDateArray"
            mode="range"
            display-format="DD/MM/YYYY"
            clearable
            placeholder="Pilih rentang filter"
          />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ rangeDateArray }}</p>
        </div>

        <!-- Manual Typing & Allow Manual Input -->
        <div class="space-y-2">
          <label class="text-sm font-medium">Input Manual / Typing Support</label>
          <DatePicker
            v-model="manualDate"
            allow-manual-input
            clearable
            placeholder="Ketik YYYY-MM-DD"
          />
          <p class="text-xs text-muted-foreground font-mono">v-model: {{ manualDate }}</p>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
