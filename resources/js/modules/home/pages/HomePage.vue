<script setup lang="ts">
import {
  Activity,
  Calendar,
  Clock,
  DollarSign,
  Globe,
  Package,
  Server,
  ShieldCheck,
  Users,
} from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import TrafficChannelsChart from '@/components/TrafficChannelsChart.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useServerClock } from '@/composables/useServerClock';
import { useAppBootstrapStore } from '@/stores/app-bootstrap';

const appBootstrap = useAppBootstrapStore();
const { formattedTime } = useServerClock();

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
  <AdminLayout parent-title="Building Your Application" title="Dashboard" hide-header>
    <!-- Backend Shared App Config Card -->
    <Card class="border-primary/20 bg-card shadow-sm">
      <CardHeader class="pb-0">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <div
              class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
            >
              <Server class="size-5" />
            </div>
            <div>
              <CardTitle class="text-lg font-bold">Backend App Configuration</CardTitle>
              <CardDescription>
                Value yang di-share langsung dari Laravel backend via Window Global Script & Pinia
                store
              </CardDescription>
            </div>
          </div>
        </div>
      </CardHeader>

      <CardContent>
        <!-- 6 Grid Items Seimbang (3x2 di medium, 6x1 di large screen) -->
        <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-3">
          <div class="flex items-center gap-3 rounded-lg border border-border/60 bg-muted/30 p-3">
            <Server class="size-4 text-muted-foreground shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-muted-foreground">App Name</p>
              <p class="truncate text-sm font-semibold text-foreground">
                {{ appBootstrap.config.name }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 rounded-lg border border-border/60 bg-muted/30 p-3">
            <ShieldCheck class="size-4 text-muted-foreground shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-muted-foreground">Environment</p>
              <p class="truncate text-sm font-semibold text-foreground capitalize">
                {{ appBootstrap.config.env }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 rounded-lg border border-border/60 bg-muted/30 p-3">
            <Globe class="size-4 text-muted-foreground shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-muted-foreground">App URL</p>
              <p class="truncate text-sm font-semibold text-foreground">
                {{ appBootstrap.config.url }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 rounded-lg border border-border/60 bg-muted/30 p-3">
            <Clock class="size-4 text-muted-foreground shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-muted-foreground">Timezone</p>
              <p class="truncate text-sm font-semibold text-foreground">
                {{ appBootstrap.config.timezone }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-3 rounded-lg border border-border/60 bg-muted/30 p-3">
            <Globe class="size-4 text-muted-foreground shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-muted-foreground">Locale</p>
              <p class="truncate text-sm font-semibold text-foreground uppercase">
                {{ appBootstrap.config.locale }}
              </p>
            </div>
          </div>

          <div
            class="flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 p-3 shadow-2xs"
          >
            <Calendar class="size-4 text-primary shrink-0" />
            <div class="min-w-0">
              <p class="text-xs font-medium text-primary">Server Date & Time</p>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="relative flex size-2 shrink-0">
                  <span
                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"
                  ></span>
                  <span class="relative inline-flex size-2 rounded-full bg-primary"></span>
                </span>
                <p class="truncate text-xs font-bold text-foreground font-mono">
                  {{ appBootstrap.config.current_date }}
                  <span class="text-primary">{{ formattedTime }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

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
      <!-- Left Side Card (Transaction/Analytics) -->
      <Card class="flex flex-col justify-between border-border bg-card lg:col-span-4">
        <TrafficChannelsChart />
      </Card>

      <!-- Right Side Card (Recent Sales) -->
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
  </AdminLayout>
</template>
