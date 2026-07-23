<script setup lang="ts">
import { Activity, DollarSign, Package, Users } from '@lucide/vue';
import TrafficChannelsChart from '@/components/TrafficChannelsChart.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

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
  <div class="flex flex-col gap-6">
    <!-- Cards grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
      <Card v-for="stat in stats" :key="stat.title" class="border-border bg-card shadow-xs">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
          <CardTitle class="text-base font-medium text-muted-foreground">
            {{ stat.title }}
          </CardTitle>
          <component :is="stat.icon" class="text-muted-foreground size-4" />
        </CardHeader>
        <CardContent>
          <div class="text-2xl font-bold tracking-tight text-foreground">
            {{ stat.value }}
          </div>
          <p class="mt-1 text-sm text-muted-foreground">{{ stat.change }}</p>
        </CardContent>
      </Card>
    </div>

    <!-- Main Layout Split (Recent Sales and Chart) -->
    <div class="grid gap-4 md:gap-6 lg:grid-cols-7">
      <Card class="flex flex-col justify-between border-border bg-card lg:col-span-4">
        <TrafficChannelsChart />
      </Card>

      <Card class="border-border bg-card lg:col-span-3">
        <CardHeader>
          <CardTitle class="text-base font-bold">Recent Sales</CardTitle>
          <CardDescription>You made 265 sales this month.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6">
          <div v-for="i in 5" :key="i" class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div
                class="flex size-8 shrink-0 items-center justify-center rounded-full bg-accent text-sm font-semibold text-accent-foreground"
              >
                U{{ i }}
              </div>
              <div class="flex min-w-0 flex-col">
                <span class="truncate font-semibold text-foreground"> User Example {{ i }} </span>
                <span class="truncate text-sm text-muted-foreground">
                  user{{ i }}@example.com
                </span>
              </div>
            </div>
            <span class="font-bold text-foreground">+$1,999.00</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
