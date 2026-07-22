<script setup lang="ts">
import { computed, ref } from 'vue';
import Combobox from './Combobox.vue';
import remoteFixture from './combobox-options.json';
import type { ComboboxOption } from './combobox.utils';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Employee extends ComboboxOption {
  employeeId: number;
  fullName: string;
  email: string;
  team: string;
  inactive?: boolean;
}

interface City extends ComboboxOption {
  id: number;
  name: string;
  region: string;
}

const employees = ref<Employee[]>([
  { employeeId: 1, fullName: 'Alya Putri', email: 'alya@example.com', team: 'Produk' },
  { employeeId: 2, fullName: 'Bima Pratama', email: 'bima@example.com', team: 'Produk' },
  { employeeId: 3, fullName: 'Citra Lestari', email: 'citra@example.com', team: 'Engineering' },
  { employeeId: 4, fullName: 'Dimas Saputra', email: 'dimas@example.com', team: 'Engineering' },
  { employeeId: 5, fullName: 'Eka Wulandari', email: 'eka@example.com', team: 'Operasional' },
  {
    employeeId: 6,
    fullName: 'Farhan Akbar',
    email: 'farhan@example.com',
    team: 'Operasional',
    inactive: true,
  },
]);
const selectedEmployees = ref<number[]>([1, 3]);

const remoteSource = ref<City[]>(remoteFixture);
const remoteOptions = ref<City[]>([]);
const selectedCityIds = ref<number[]>([24]);
const remoteSearch = ref('');
const remoteLoading = ref(false);
const remoteLoadingMore = ref(false);
const remoteCreating = ref(false);
const remoteError = ref<Error>();
const hasMore = ref(false);
const currentPage = ref(1);
const pageSize = 6;
let requestVersion = 0;

const selectedCities = computed(() =>
  remoteSource.value.filter((city) => selectedCityIds.value.includes(city.id)),
);

function delay(duration: number) {
  return new Promise((resolve) => globalThis.setTimeout(resolve, duration));
}

function filteredCities(query: string) {
  const normalizedQuery = query.toLocaleLowerCase();
  return remoteSource.value.filter((city) =>
    `${city.name} ${city.region}`.toLocaleLowerCase().includes(normalizedQuery),
  );
}

async function searchCities(query: string) {
  const version = ++requestVersion;
  remoteLoading.value = true;
  remoteError.value = undefined;
  currentPage.value = 1;

  await delay(700);
  if (version !== requestVersion) return;

  if (query.toLocaleLowerCase() === 'error') {
    remoteOptions.value = [];
    remoteError.value = new Error('Simulasi request gagal.');
    hasMore.value = false;
    remoteLoading.value = false;
    return;
  }

  const result = filteredCities(query);
  remoteOptions.value = result.slice(0, pageSize);
  hasMore.value = remoteOptions.value.length < result.length;
  remoteLoading.value = false;
}

async function loadMoreCities() {
  if (remoteLoadingMore.value || !hasMore.value) return;

  const version = requestVersion;
  remoteLoadingMore.value = true;
  await delay(600);
  if (version !== requestVersion) {
    remoteLoadingMore.value = false;
    return;
  }

  const nextPage = currentPage.value + 1;
  const result = filteredCities(remoteSearch.value);
  remoteOptions.value.push(...result.slice(currentPage.value * pageSize, nextPage * pageSize));
  currentPage.value = nextPage;
  hasMore.value = remoteOptions.value.length < result.length;
  remoteLoadingMore.value = false;
}

async function createEmployee(name: string) {
  const employee: Employee = {
    employeeId: Math.max(...employees.value.map((item) => item.employeeId)) + 1,
    fullName: name,
    email: `${name.toLocaleLowerCase().replaceAll(' ', '.')}@example.com`,
    team: 'Baru',
  };
  employees.value.push(employee);
  selectedEmployees.value = [...selectedEmployees.value, employee.employeeId];
}

async function createCity(name: string) {
  remoteCreating.value = true;
  await delay(700);
  const city: City = {
    id: Math.max(...remoteSource.value.map((item) => item.id)) + 1,
    name,
    region: 'Kustom',
  };
  remoteSource.value.push(city);
  remoteOptions.value = [city, ...remoteOptions.value];
  selectedCityIds.value = [...selectedCityIds.value, city.id];
  remoteCreating.value = false;
}
</script>

<template>
  <div class="grid gap-4 lg:grid-cols-2">
    <Card>
      <CardHeader>
        <CardTitle>Combobox lokal</CardTitle>
        <CardDescription>
          Multiple, grouping, batas pilihan, opsi nonaktif, select all, dan creatable.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <Combobox
          v-model="selectedEmployees"
          :options="employees"
          multiple
          value-key="employeeId"
          label-key="fullName"
          group-key="team"
          disabled-key="inactive"
          :max-displayed-items="10"
          :max-selected-items="4"
          select-all
          clear-all
          creatable
          placeholder="Pilih anggota tim"
          @create="createEmployee"
        >
          <template #option="{ item }">
            <span class="flex min-w-0 flex-col">
              <span class="truncate">{{ item.fullName }}</span>
              <span class="truncate text-xs text-muted-foreground">{{ item.email }}</span>
            </span>
          </template>
          <template #group="{ group }">
            <span class="font-semibold uppercase tracking-wide">Tim {{ group }}</span>
          </template>
        </Combobox>

        <p class="text-xs text-muted-foreground font-mono">v-model: {{ selectedEmployees }}</p>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>Combobox remote</CardTitle>
        <CardDescription>
          Fixture JSON mensimulasikan debounce, initial load, infinite scroll, retry, dan create.
          Cari “error” untuk mencoba state gagal.
        </CardDescription>
      </CardHeader>
      <CardContent class="space-y-4">
        <Combobox
          v-model="selectedCityIds"
          v-model:search="remoteSearch"
          :options="remoteOptions"
          :selected-options="selectedCities"
          mode="remote"
          multiple
          value-key="id"
          label-key="name"
          group-key="region"
          :loading="remoteLoading"
          :loading-more="remoteLoadingMore"
          :has-more="hasMore"
          :error="remoteError"
          :creating="remoteCreating"
          :max-displayed-items="2"
          :show-selected-indicator="false"
          clear-all
          creatable
          placeholder="Pilih kota"
          @search="searchCities"
          @load-more="loadMoreCities"
          @retry="searchCities(remoteSearch)"
          @create="createCity"
        >
          <template #option="{ item }">
            <span class="flex w-full items-center justify-between gap-3">
              <span>{{ item.name }}</span>
              <span class="text-xs text-muted-foreground">{{ item.region }}</span>
            </span>
          </template>
        </Combobox>

        <p class="text-xs text-muted-foreground font-mono">v-model => {{ selectedCityIds }}</p>
      </CardContent>
    </Card>
  </div>
</template>
