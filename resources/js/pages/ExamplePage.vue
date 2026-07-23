<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Home, Layers, ShieldAlert, Sliders, Table } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import DataTableExample from '@/components/custom-ui/data-table/DataTableExample.vue';
import ConfirmDialogExample from '@/components/custom-ui/confirm-dialog/ConfirmDialogExample.vue';
import ComboboxExample from '@/components/custom-ui/combobox/ComboboxExample.vue';
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';

const route = useRoute();
const router = useRouter();

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
    <div class="space-y-6">
      <!-- Horizontal Navigation Menu (shadcn-vue top navbar style matching screenshot) -->
      <div class="rounded-2xl border border-border bg-card p-2 shadow-xs">
        <NavigationMenu class="max-w-full justify-start">
          <NavigationMenuList class="flex items-center gap-1">
            <!-- Home Link -->
            <NavigationMenuItem>
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'cursor-pointer gap-2 font-medium',
                  isItemActive('/', true) && 'bg-primary/10 text-primary font-semibold',
                ]"
                @click="navigate('/', true)"
              >
                <Home class="size-4" />
                Home
              </NavigationMenuLink>
            </NavigationMenuItem>

            <!-- Components Dropdown -->
            <NavigationMenuItem>
              <NavigationMenuTrigger class="cursor-pointer gap-2 font-medium">
                <Layers class="size-4" />
                Components
              </NavigationMenuTrigger>
              <NavigationMenuContent>
                <ul class="grid w-[400px] gap-3 p-4 md:w-[500px] md:grid-cols-2 lg:w-[600px]">
                  <li>
                    <NavigationMenuLink as-child>
                      <a
                        href="#data-tables"
                        :class="[
                          'block select-none space-y-1 rounded-xl p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground',
                          activeHash === '#data-tables' && 'bg-accent/80 font-medium',
                        ]"
                        @click.prevent="navigate('#data-tables', false)"
                      >
                        <div class="flex items-center gap-2 text-sm font-semibold leading-none">
                          <Table class="size-4 text-primary" />
                          DataTable
                        </div>
                        <p class="line-clamp-2 text-xs leading-snug text-muted-foreground">
                          Tabel data reusable mode lokal & server
                        </p>
                      </a>
                    </NavigationMenuLink>
                  </li>

                  <li>
                    <NavigationMenuLink as-child>
                      <a
                        href="#confirm-dialog"
                        :class="[
                          'block select-none space-y-1 rounded-xl p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground',
                          activeHash === '#confirm-dialog' && 'bg-accent/80 font-medium',
                        ]"
                        @click.prevent="navigate('#confirm-dialog', false)"
                      >
                        <div class="flex items-center gap-2 text-sm font-semibold leading-none">
                          <ShieldAlert class="size-4 text-primary" />
                          ConfirmDialog
                        </div>
                        <p class="line-clamp-2 text-xs leading-snug text-muted-foreground">
                          Dialog konfirmasi global shadcn-vue
                        </p>
                      </a>
                    </NavigationMenuLink>
                  </li>

                  <li>
                    <NavigationMenuLink as-child>
                      <a
                        href="#combobox"
                        :class="[
                          'block select-none space-y-1 rounded-xl p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground',
                          activeHash === '#combobox' && 'bg-accent/80 font-medium',
                        ]"
                        @click.prevent="navigate('#combobox', false)"
                      >
                        <div class="flex items-center gap-2 text-sm font-semibold leading-none">
                          <Sliders class="size-4 text-primary" />
                          Combobox
                        </div>
                        <p class="line-clamp-2 text-xs leading-snug text-muted-foreground">
                          Autocomplete dropdown dengan filter
                        </p>
                      </a>
                    </NavigationMenuLink>
                  </li>
                </ul>
              </NavigationMenuContent>
            </NavigationMenuItem>

            <!-- Direct Quick Tabs (DataTable, ConfirmDialog, Combobox) -->
            <NavigationMenuItem>
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'cursor-pointer gap-2 font-medium',
                  activeHash === '#data-tables' && 'bg-primary/10 text-primary font-semibold',
                ]"
                @click="navigate('#data-tables', false)"
              >
                <Table class="size-4" />
                DataTable
              </NavigationMenuLink>
            </NavigationMenuItem>

            <NavigationMenuItem>
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'cursor-pointer gap-2 font-medium',
                  activeHash === '#confirm-dialog' && 'bg-primary/10 text-primary font-semibold',
                ]"
                @click="navigate('#confirm-dialog', false)"
              >
                <ShieldAlert class="size-4" />
                ConfirmDialog
              </NavigationMenuLink>
            </NavigationMenuItem>

            <NavigationMenuItem>
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'cursor-pointer gap-2 font-medium',
                  activeHash === '#combobox' && 'bg-primary/10 text-primary font-semibold',
                ]"
                @click="navigate('#combobox', false)"
              >
                <Sliders class="size-4" />
                Combobox
              </NavigationMenuLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        </NavigationMenu>
      </div>

      <!-- Full-Width Main Content Area -->
      <main class="min-w-0">
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
