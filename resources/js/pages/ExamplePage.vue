<script setup lang="ts">
import { computed, type Component } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Home, Layers, Table, ShieldAlert, Sliders, Component as ComponentIcon } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
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

// Auto-discover all *Example.vue components inside resources/js/components/custom-ui/
const exampleFiles = (
  import.meta as unknown as {
    glob: <T>(pattern: string, options?: { eager?: boolean }) => Record<string, T>;
  }
).glob<{ default: Component }>('/resources/js/components/custom-ui/**/*Example.vue', {
  eager: true,
});

interface CustomComponentExample {
  dirName: string;
  name: string;
  hash: string;
  description: string;
  component: Component;
  icon: Component;
}

const descriptions: Record<string, string> = {
  'data-table': 'Tabel data reusable mode lokal & server',
  'confirm-dialog': 'Dialog konfirmasi global berbasis shadcn-vue',
  combobox: 'Autocomplete dropdown dengan pencarian & filter',
};

const icons: Record<string, Component> = {
  'data-table': Table,
  'confirm-dialog': ShieldAlert,
  combobox: Sliders,
};

const examples: CustomComponentExample[] = Object.entries(exampleFiles).map(([path, mod]) => {
  const match = path.match(/custom-ui\/([^/]+)\/([^/]+)\.vue$/);
  const dirName = match ? match[1] : 'example';
  const fileName = match ? match[2] : 'Example';
  const name = fileName.replace(/Example$/, '');
  const slug = dirName === 'data-table' ? 'data-tables' : dirName;
  const hash = `#${slug}`;
  const loadedModule = mod as { default: Component };

  return {
    dirName,
    name,
    hash,
    description: descriptions[dirName] || `Komponen kustom ${name}`,
    component: loadedModule.default,
    icon: icons[dirName] || ComponentIcon,
  };
});

const defaultHash = examples[0]?.hash || '#data-tables';

const activeHash = computed(() => {
  if (route.path !== '/example-custom-component') return defaultHash;
  return route.hash || defaultHash;
});

const activeExample = computed(() => {
  return examples.find((ex) => ex.hash === activeHash.value) || examples[0];
});

const activeComponent = computed(() => activeExample.value?.component);
const currentTitle = computed(() => activeExample.value?.name || 'Example');

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
      <!-- Horizontal Navigation Menu (shadcn-vue top navbar style) -->
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
                <ul class="grid w-100 gap-3 p-4 md:w-125 md:grid-cols-2 lg:w-150">
                  <li v-for="ex in examples" :key="ex.hash">
                    <NavigationMenuLink as-child>
                      <a
                        :href="ex.hash"
                        :class="[
                          'block select-none space-y-1 rounded-xl p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground',
                          activeHash === ex.hash && 'bg-accent/80 font-medium',
                        ]"
                        @click.prevent="navigate(ex.hash, false)"
                      >
                        <div class="flex items-center gap-2 text-sm font-semibold leading-none">
                          <component :is="ex.icon" class="size-4 text-primary" />
                          {{ ex.name }}
                        </div>
                        <p class="line-clamp-2 text-xs leading-snug text-muted-foreground">
                          {{ ex.description }}
                        </p>
                      </a>
                    </NavigationMenuLink>
                  </li>
                </ul>
              </NavigationMenuContent>
            </NavigationMenuItem>

            <!-- Direct Quick Tabs (Auto-scanned) -->
            <NavigationMenuItem v-for="ex in examples" :key="ex.hash">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'cursor-pointer gap-2 font-medium',
                  activeHash === ex.hash && 'bg-primary/10 text-primary font-semibold',
                ]"
                @click="navigate(ex.hash, false)"
              >
                <component :is="ex.icon" class="size-4" />
                {{ ex.name }}
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
