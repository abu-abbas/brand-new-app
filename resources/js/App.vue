<script setup lang="ts">
import AdminLayout from '@/components/AdminLayout.vue';
import ConfirmDialog from '@/components/custom-ui/confirm-dialog/ConfirmDialog.vue';
import ConfirmDialogExample from '@/components/custom-ui/confirm-dialog/ConfirmDialogExample.vue';
import ComboboxExample from '@/components/custom-ui/combobox/ComboboxExample.vue';
import DataTableExample from '@/components/custom-ui/data-table/DataTableExample.vue';
import TrafficChannelsChart from '@/components/TrafficChannelsChart.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign, Package, Activity, Users } from '@lucide/vue';

// Mock data statistik transaksi bergaya dashboard-01
const stats = [
  {
    title: 'Total Revenue',
    value: '$45,231.89',
    change: '+20.1% from last month',
    icon: DollarSign,
  },
  { title: 'Subscriptions', value: '+2350', change: '+180.1% from last month', icon: Users },
  { title: 'Sales', value: '+12,234', change: '+19% from last month', icon: Package },
  { title: 'Active Now', value: '+573', change: '+201 since last hour', icon: Activity },
];
</script>

<template>
  <ConfirmDialog />

  <!-- Menggunakan AdminLayout global dengan Breadcrumb dinamis "Building Your Application / Data Fetching" -->
  <AdminLayout parent-title="Building Your Application" title="Data Fetching">
    <!-- Cards grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card v-for="stat in stats" :key="stat.title" class="bg-card border-border shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between pb-2 space-y-0">
          <CardTitle class="text-base font-medium text-muted-foreground">
            {{ stat.title }}
          </CardTitle>
          <component :is="stat.icon" class="size-4 text-muted-foreground" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-foreground">
            {{ stat.value }}
          </div>
          <p class="text-sm text-muted-foreground mt-1">{{ stat.change }}</p>
        </CardContent>
      </Card>
    </div>

    <ConfirmDialogExample />

    <ComboboxExample />

    <DataTableExample />

    <!-- Main Layout Split (Recent Sales and Chart Placeholder) -->
    <div class="grid gap-4 md:gap-6 lg:grid-cols-7 flex-1">
      <!-- Left Side Card (Transaction/Analytics) -->
      <Card class="lg:col-span-4 bg-card border-border flex flex-col justify-between">
        <TrafficChannelsChart />
      </Card>

      <!-- Right Side Card (Recent Sales) -->
      <Card class="lg:col-span-3 bg-card border-border">
        <CardHeader>
          <CardTitle class="text-base font-bold">Recent Sales</CardTitle>
          <CardDescription>You made 265 sales this month.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <!-- User row -->
          <div v-for="i in 5" :key="i" class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div
                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground font-semibold text-sm"
              >
                U{{ i }}
              </div>
              <div class="flex flex-col min-w-0">
                <span class="font-semibold text-foreground truncate"> User Example {{ i }} </span>
                <span class="text-sm text-muted-foreground truncate">
                  user{{ i }}@example.com
                </span>
              </div>
            </div>
            <span class="font-bold text-foreground">+$1,999.00</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </AdminLayout>
</template>
