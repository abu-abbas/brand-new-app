<script setup lang="ts">
import { computed, type Component } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Home, Table, ShieldAlert, Sliders, Component as ComponentIcon } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
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
  component: Component;
  icon: Component;
}

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
      <!-- Horizontal Navigation Menu (shadcn-vue ScrollArea with horizontal ScrollBar) -->
      <ScrollArea class="w-full pb-2">
        <NavigationMenu class="w-full max-w-full justify-start">
          <NavigationMenuList class="flex w-max flex-nowrap items-center gap-1 pr-4">
            <!-- Home Link -->
            <NavigationMenuItem>
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'shrink-0 cursor-pointer gap-2 font-medium',
                  isItemActive('/', true) && 'bg-primary/10 font-semibold text-primary',
                ]"
                @click="navigate('/', true)"
              >
                <Home class="size-4" />
                Home
              </NavigationMenuLink>
            </NavigationMenuItem>

            <!-- Direct Quick Tabs (Auto-scanned) -->
            <NavigationMenuItem v-for="ex in examples" :key="ex.hash">
              <NavigationMenuLink
                :class="[
                  navigationMenuTriggerStyle(),
                  'shrink-0 cursor-pointer gap-2 font-medium',
                  activeHash === ex.hash && 'bg-primary/10 font-semibold text-primary',
                ]"
                @click="navigate(ex.hash, false)"
              >
                <component :is="ex.icon" class="size-4" />
                {{ ex.name }}
              </NavigationMenuLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        </NavigationMenu>
        <ScrollBar orientation="horizontal" />
      </ScrollArea>

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
