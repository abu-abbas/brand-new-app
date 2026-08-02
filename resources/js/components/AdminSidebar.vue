<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useQuery } from '@tanstack/vue-query';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarRail,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuSub,
  DropdownMenuSubTrigger,
  DropdownMenuSubContent,
} from '@/components/ui/dropdown-menu';
import { useTheme } from '@/composables/useTheme';
import { useDarkMode } from '@/composables/useDarkMode';
import { useAuthStore } from '@/stores/auth';
import { usePermissionStore } from '@/stores/permission';
import { LucideIcon } from '@/components/custom-ui/lucide-icon';
import { FeaturesFacade } from '@/modules/features-management/api/features.facade';

import {
  ChevronRight,
  ChevronsUpDown,
  Sparkles,
  BadgeCheck,
  CreditCard,
  Bell,
  LogOut,
  Palette,
  Sun,
  Moon,
} from '@lucide/vue';

const { activeTheme, otherThemes, setTheme, activeThemeLabel, activeThemeColor } = useTheme();
const { isDark, toggleDarkMode } = useDarkMode();
const auth = useAuthStore();
const permission = usePermissionStore();
const route = useRoute();
const router = useRouter();

const initials = computed(
  () =>
    auth.user?.name
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part[0])
      .join('')
      .toUpperCase() || 'U',
);

const handleLogout = async () => {
  await auth.logout();
  window.location.assign('/login');
};

const { data: rawFeatures } = useQuery({
  queryKey: ['features', 'sidebar-menu'],
  queryFn: async ({ signal }) => {
    const response = await FeaturesFacade.list(
      { type: 'menu', per_page: 100, include_deleted: 'false' },
      signal,
    );
    return response.data;
  },
  staleTime: Infinity,
  gcTime: 1000 * 60 * 60,
});

interface DynamicSubMenuItem {
  alias: string;
  title: string;
  url: string;
  isSubActive: boolean;
}

interface DynamicMenuItem {
  alias: string;
  title: string;
  icon?: string | null;
  url?: string;
  isActive: boolean;
  items: DynamicSubMenuItem[];
}

function resolveUrl(routeName?: string | null): string {
  if (!routeName) return '#';
  if (router.hasRoute(routeName)) {
    return router.resolve({ name: routeName }).href;
  }
  return '#';
}

const dynamicMenu = computed<DynamicMenuItem[]>(() => {
  const list = rawFeatures.value ?? [];
  const sidebarFeatures = list.filter((item) => item.show_on_sidebar && permission.can(item.alias));

  const parentMap = new Map<string, DynamicMenuItem>();
  const childMap = new Map<string, DynamicSubMenuItem[]>();

  sidebarFeatures.forEach((item) => {
    const isRoot = !item.parent;
    const url = resolveUrl(item.route);

    if (isRoot) {
      parentMap.set(item.alias, {
        alias: item.alias,
        title: item.name,
        icon: item.icon,
        url,
        isActive: false,
        items: [],
      });
    } else {
      if (!childMap.has(item.parent!)) {
        childMap.set(item.parent!, []);
      }
      const isSubActive =
        Boolean(item.route && route.name === item.route) || (url !== '#' && route.path === url);

      childMap.get(item.parent!)!.push({
        alias: item.alias,
        title: item.name,
        url,
        isSubActive,
      });
    }
  });

  const result: DynamicMenuItem[] = [];
  parentMap.forEach((parent) => {
    const children = childMap.get(parent.alias) || [];
    parent.items = children;

    const isAnySubActive = children.some((c) => c.isSubActive);
    const isSelfActive =
      Boolean(parent.url && parent.url !== '#' && route.path === parent.url) ||
      (parent.alias === 'beranda' && route.path === '/');

    parent.isActive = isAnySubActive || isSelfActive;

    // Tampilkan jika parent itu sendiri punya URL valid atau mempunyai setidaknya 1 child yang dapat diakses
    if ((parent.url && parent.url !== '#') || children.length > 0) {
      result.push(parent);
    }
  });

  return result;
});
</script>

<template>
  <Sidebar collapsible="icon" class="border-r-0! border-none! bg-sidebar">
    <!-- Section 1: Brand / Workspace Switcher -->
    <SidebarHeader class="p-2 h-14 justify-center">
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" class="w-full justify-between hover:bg-accent/50">
            <div class="flex items-center gap-2 overflow-hidden">
              <div
                class="hidden group-data-[collapsible=icon]:flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-primary text-primary-foreground font-semibold text-sm"
              >
                AI
              </div>
              <div class="flex flex-col group-data-[collapsible=icon]:hidden min-w-0 text-left">
                <span class="text-sm font-semibold text-foreground truncate leading-tight">
                  Acme Inc
                </span>
                <span class="text-[10px] text-muted-foreground truncate leading-none">
                  Enterprise
                </span>
              </div>
            </div>
            <ChevronsUpDown
              class="size-4 text-muted-foreground shrink-0 group-data-[collapsible=icon]:hidden"
            />
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <!-- Section 2: Menu Items (Platform / Dynamic Features) -->
    <SidebarContent class="p-2 gap-6">
      <div>
        <!-- <span
          class="group-data-[collapsible=icon]:hidden text-[10px] font-bold text-muted-foreground tracking-wider uppercase block px-2 mb-2"
        >
          Platform
        </span> -->
        <SidebarMenu>
          <template v-for="item in dynamicMenu" :key="item.alias">
            <!-- Collapsible Parent Menu if it has children -->
            <Collapsible
              v-if="item.items && item.items.length > 0"
              v-slot="{ open }"
              :default-open="item.isActive"
              class="group/collapsible"
            >
              <SidebarMenuItem>
                <CollapsibleTrigger as-child>
                  <SidebarMenuButton
                    :tooltip="item.title"
                    class="w-full transition-colors"
                    :class="
                      item.isActive
                        ? 'bg-primary/75 dark:bg-primary/15 text-primary-foreground dark:text-primary! hover:bg-primary/85! dark:hover:bg-primary/25! hover:text-primary-foreground! dark:hover:text-primary!'
                        : ''
                    "
                  >
                    <LucideIcon
                      :name="item.icon"
                      fallback="SquareTerminal"
                      class="size-4 shrink-0"
                    />
                    <span class="group-data-[collapsible=icon]:hidden font-medium leading-normal">
                      {{ item.title }}
                    </span>
                    <ChevronRight
                      class="ml-auto size-3 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                      :class="[
                        open ? 'rotate-90' : '',
                        item.isActive
                          ? 'text-primary-foreground dark:text-primary!'
                          : 'text-muted-foreground/70',
                      ]"
                    />
                  </SidebarMenuButton>
                </CollapsibleTrigger>
                <CollapsibleContent class="group-data-[collapsible=icon]:hidden">
                  <SidebarMenuSub
                    class="mt-1 border-l border-border/80 pl-3 ml-3.5 flex flex-col gap-1"
                  >
                    <SidebarMenuSubItem v-for="sub in item.items" :key="sub.alias">
                      <SidebarMenuSubButton size="md" class="text-2sm!" as-child>
                        <RouterLink
                          :to="sub.url"
                          :class="sub.isSubActive ? 'text-primary! font-semibold' : ''"
                        >
                          {{ sub.title }}
                        </RouterLink>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  </SidebarMenuSub>
                </CollapsibleContent>
              </SidebarMenuItem>
            </Collapsible>

            <!-- Single Direct Link Menu if no children -->
            <SidebarMenuItem v-else>
              <SidebarMenuButton
                :tooltip="item.title"
                as-child
                :class="
                  item.isActive
                    ? 'bg-primary/75 dark:bg-primary/15 text-primary-foreground dark:text-primary! hover:bg-primary/85! dark:hover:bg-primary/25! hover:text-primary-foreground! dark:hover:text-primary!'
                    : ''
                "
              >
                <RouterLink :to="item.url || '#'" class="flex items-center gap-2 w-full">
                  <LucideIcon :name="item.icon" fallback="SquareTerminal" class="size-4 shrink-0" />
                  <span class="group-data-[collapsible=icon]:hidden font-medium leading-normal">
                    {{ item.title }}
                  </span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </template>
        </SidebarMenu>
      </div>
    </SidebarContent>

    <!-- Section 3: Menu Footer (Profile & Dropdown) -->
    <SidebarFooter class="p-2 mt-auto">
      <SidebarMenu>
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton
                size="lg"
                class="w-full justify-between hover:bg-accent/50 data-[state=open]:bg-sidebar-accent"
              >
                <div class="flex items-center gap-2 overflow-hidden">
                  <div
                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold text-xs"
                  >
                    {{ initials }}
                  </div>
                  <div class="flex flex-col group-data-[collapsible=icon]:hidden min-w-0 text-left">
                    <span class="text-sm font-semibold text-foreground truncate leading-tight">
                      {{ auth.user?.name }}
                    </span>
                    <span class="text-[10px] text-muted-foreground truncate leading-none">
                      {{ auth.user?.email }}
                    </span>
                  </div>
                </div>
                <ChevronsUpDown
                  class="size-4 text-muted-foreground shrink-0 group-data-[collapsible=icon]:hidden"
                />
              </SidebarMenuButton>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-56 rounded-lg" side="right" align="end" :side-offset="8">
              <DropdownMenuLabel class="p-0 font-normal">
                <div class="flex items-center gap-2 px-1.5 py-1.5 text-left text-sm">
                  <div
                    class="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground font-semibold text-xs"
                  >
                    {{ initials }}
                  </div>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold text-xs text-foreground">
                      {{ auth.user?.name }}
                    </span>
                    <span class="truncate text-[10px] text-muted-foreground">
                      {{ auth.user?.email }}
                    </span>
                  </div>
                </div>
              </DropdownMenuLabel>
              <DropdownMenuSeparator />
              <DropdownMenuGroup>
                <DropdownMenuItem>
                  <Sparkles class="size-4 mr-2 text-muted-foreground" />
                  <span>Upgrade to Pro</span>
                </DropdownMenuItem>
              </DropdownMenuGroup>
              <DropdownMenuSeparator />
              <DropdownMenuGroup>
                <DropdownMenuItem>
                  <BadgeCheck class="size-4 mr-2 text-muted-foreground" />
                  <span>Account</span>
                </DropdownMenuItem>
                <DropdownMenuItem>
                  <CreditCard class="size-4 mr-2 text-muted-foreground" />
                  <span>Billing</span>
                </DropdownMenuItem>
                <DropdownMenuItem>
                  <Bell class="size-4 mr-2 text-muted-foreground" />
                  <span>Notifications</span>
                </DropdownMenuItem>

                <!-- Theme Settings & Dark Mode -->
                <DropdownMenuSeparator />
                <DropdownMenuSub>
                  <DropdownMenuSubTrigger>
                    <Palette class="size-4 mr-2 text-primary" />
                    <span>Accent Color</span>
                  </DropdownMenuSubTrigger>
                  <DropdownMenuSubContent class="w-48 max-h-72 overflow-y-auto">
                    <DropdownMenuItem
                      class="flex items-center justify-between bg-accent/40 font-semibold"
                      @click="setTheme(activeTheme)"
                    >
                      <span>{{ activeThemeLabel }}</span>
                      <span
                        class="size-2.5 rounded-full border border-black/10 dark:border-white/10 shrink-0"
                        :style="{ backgroundColor: activeThemeColor }"
                      />
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <template v-for="t in otherThemes" :key="t.name">
                      <DropdownMenuItem
                        class="flex items-center justify-between"
                        :class="activeTheme === t.name ? 'bg-accent/40 font-semibold' : ''"
                        @click="setTheme(t.name)"
                      >
                        <span>{{ t.label }}</span>
                        <span
                          class="size-2.5 rounded-full border border-black/10 dark:border-white/10 shrink-0"
                          :style="{ backgroundColor: t.color }"
                        />
                      </DropdownMenuItem>
                    </template>
                  </DropdownMenuSubContent>
                </DropdownMenuSub>

                <DropdownMenuItem @click="toggleDarkMode($event)">
                  <component :is="isDark ? Sun : Moon" class="size-4 mr-2 text-muted-foreground" />
                  <span>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</span>
                </DropdownMenuItem>
              </DropdownMenuGroup>
              <DropdownMenuSeparator />
              <DropdownMenuItem @click="handleLogout">
                <LogOut class="size-4 mr-2 text-destructive" />
                <span class="text-destructive">Log out</span>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>
</template>
