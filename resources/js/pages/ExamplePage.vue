<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Home, ShieldAlert, Sliders, Table } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import DataTableExample from '@/components/custom-ui/data-table/DataTableExample.vue';
import ConfirmDialogExample from '@/components/custom-ui/confirm-dialog/ConfirmDialogExample.vue';
import ComboboxExample from '@/components/custom-ui/combobox/ComboboxExample.vue';
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from '@/components/ui/navigation-menu';

const route = useRoute();
const router = useRouter();

const navItems = [
  {
    name: 'Home',
    target: '/',
    isExternal: true,
    icon: Home,
  },
  {
    name: 'DataTable',
    target: '#data-tables',
    isExternal: false,
    icon: Table,
  },
  {
    name: 'ConfirmDialog',
    target: '#confirm-dialog',
    isExternal: false,
    icon: ShieldAlert,
  },
  {
    name: 'Combobox',
    target: '#combobox',
    isExternal: false,
    icon: Sliders,
  },
];

const activeHash = computed(() => {
  if (route.path !== '/example-custom-component') return '#data-tables';
  return route.hash || '#data-tables';
});

const activeComponent = computed(() => {
  switch (activeHash.value) {
    case '#confirm-dialog':
      return ConfirmDialogExample;
    case '#combobox':
      return ComboboxExample;
    case '#data-tables':
    default:
      return DataTableExample;
  }
});

const currentTitle = computed(() => {
  switch (activeHash.value) {
    case '#confirm-dialog':
      return 'ConfirmDialog';
    case '#combobox':
      return 'Combobox';
    case '#data-tables':
    default:
      return 'DataTable';
  }
});

function isItemActive(target: string, isExternal: boolean): boolean {
  if (isExternal) return route.path === target;
  return route.path === '/example-custom-component' && activeHash.value === target;
}

function navigate(target: string, isExternal: boolean) {
  if (isExternal) {
    router.push(target);
  } else {
    router.push({ path: '/example-custom-component', hash: target });
  }
}
</script>

<template>
  <AdminLayout parent-title="Examples" :title="currentTitle">
    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
      <!-- Left Sidebar Navigation Card (Using shadcn-vue NavigationMenu) -->
      <aside class="lg:col-span-3">
        <div class="sticky top-0 rounded-2xl border border-border bg-card p-4 shadow-xs">
          <div class="px-3 pt-2 pb-3">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
              Examples Nav
            </h3>
          </div>

          <NavigationMenu class="max-w-full justify-start">
            <NavigationMenuList class="w-full flex-col items-stretch space-x-0 space-y-1.5">
              <NavigationMenuItem v-for="item in navItems" :key="item.target">
                <NavigationMenuLink as-child>
                  <button
                    type="button"
                    :class="[
                      'flex w-full items-center gap-3.5 rounded-xl px-3.5 py-3 text-left transition-colors duration-150',
                      isItemActive(item.target, item.isExternal)
                        ? 'bg-primary/10 font-semibold text-primary shadow-xs'
                        : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    ]"
                    @click="navigate(item.target, item.isExternal)"
                  >
                    <component
                      :is="item.icon"
                      :class="[
                        'size-5 shrink-0 transition-colors',
                        isItemActive(item.target, item.isExternal)
                          ? 'text-primary'
                          : 'text-muted-foreground',
                      ]"
                    />
                    <span class="text-sm tracking-tight">{{ item.name }}</span>
                  </button>
                </NavigationMenuLink>
              </NavigationMenuItem>
            </NavigationMenuList>
          </NavigationMenu>
        </div>
      </aside>

      <!-- Right Main Content Area -->
      <main class="min-w-0 lg:col-span-9">
        <transition name="fade" mode="out-in">
          <component :is="activeComponent" :key="activeHash" />
        </transition>
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
