<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Table, ShieldAlert, Sliders, Home } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';

const route = useRoute();
const router = useRouter();

const navItems = [
  {
    name: 'DataTable',
    path: '/example-custom-component/data-table',
    icon: Table,
    description: 'Tabel data reusable mode lokal & server',
  },
  {
    name: 'ConfirmDialog',
    path: '/example-custom-component/confirm-dialog',
    icon: ShieldAlert,
    description: 'Dialog konfirmasi global shadcn-vue',
  },
  {
    name: 'Combobox',
    path: '/example-custom-component/combobox',
    icon: Sliders,
    description: 'Autocomplete dropdown dengan filter',
  },
  {
    name: 'Home',
    path: '/',
    icon: Home,
    description: 'Kembali ke Dashboard Utama',
  },
];

const currentTitle = computed(() => {
  const matched = navItems.find((item) => item.path !== '/' && route.path.startsWith(item.path));
  return matched ? matched.name : 'Custom Components';
});

function isItemActive(path: string): boolean {
  if (path === '/') return route.path === '/';
  return route.path.startsWith(path);
}

function navigate(path: string) {
  router.push(path);
}
</script>

<template>
  <AdminLayout parent-title="Examples" :title="currentTitle">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
      <!-- Left Sidebar Navigation Card (Styled matching design screenshot) -->
      <aside class="lg:col-span-3">
        <div class="sticky top-20 rounded-2xl border border-border bg-card p-4 shadow-sm">
          <div class="px-3 pt-2 pb-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              Examples Nav
            </h3>
          </div>

          <nav class="flex flex-col gap-1.5" aria-label="Component Navigation">
            <button
              v-for="item in navItems"
              :key="item.path"
              type="button"
              :class="[
                'flex w-full items-center gap-3.5 rounded-xl px-3.5 py-3 text-left transition-colors duration-150',
                isItemActive(item.path)
                  ? 'bg-primary/10 font-semibold text-primary shadow-xs'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
              ]"
              @click="navigate(item.path)"
            >
              <component
                :is="item.icon"
                :class="[
                  'size-5 shrink-0 transition-colors',
                  isItemActive(item.path) ? 'text-primary' : 'text-muted-foreground',
                ]"
              />
              <span class="text-sm tracking-tight">{{ item.name }}</span>
            </button>
          </nav>
        </div>
      </aside>

      <!-- Right Main Content Area (Dynamic Router View) -->
      <main class="min-w-0 lg:col-span-9">
        <router-view v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </AdminLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
