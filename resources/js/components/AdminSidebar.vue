<script setup lang="ts">
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
import { useTheme, type ThemeName } from '@/composables/useTheme';
import { useDarkMode } from '@/composables/useDarkMode';

import {
  Command,
  SquareTerminal,
  BookOpen,
  Settings,
  Map,
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

const { activeTheme, setTheme } = useTheme();
const { isDark, toggleDarkMode } = useDarkMode();

const themes: { name: ThemeName; label: string; color: string }[] = [
  { name: 'default', label: 'Sky (Default)', color: '#0ea5e9' },
  { name: 'amber', label: 'Amber', color: '#f59e0b' },
  { name: 'blue', label: 'Blue', color: '#3b82f6' },
  { name: 'cyan', label: 'Cyan', color: '#06b6d4' },
  { name: 'emerald', label: 'Emerald', color: '#10b981' },
  { name: 'fuchsia', label: 'Fuchsia', color: '#d946ef' },
  { name: 'green', label: 'Green', color: '#22c55e' },
  { name: 'indigo', label: 'Indigo', color: '#6366f1' },
  { name: 'lime', label: 'Lime', color: '#84cc16' },
  { name: 'neutral', label: 'Neutral', color: '#737373' },
  { name: 'orange', label: 'Orange', color: '#f97316' },
  { name: 'pink', label: 'Pink', color: '#ec4899' },
  { name: 'purple', label: 'Purple', color: '#a855f7' },
  { name: 'red', label: 'Red', color: '#ef4444' },
  { name: 'rose', label: 'Rose', color: '#f43f5e' },
  { name: 'teal', label: 'Teal', color: '#14b8a6' },
  { name: 'violet', label: 'Violet', color: '#8b5cf6' },
  { name: 'yellow', label: 'Yellow', color: '#eab308' },
];

import type { Component } from 'vue';

interface SubMenuItem {
  title: string;
  url: string;
  isSubActive?: boolean;
}

interface PlatformMenuItem {
  title: string;
  icon: Component;
  isActive?: boolean;
  items?: SubMenuItem[];
}

// Mock data untuk menu navigasi utama (Platform - collapsible)
const platformMenu: PlatformMenuItem[] = [
  {
    title: 'Playground',
    icon: SquareTerminal,
    isActive: true,
    items: [
      { title: 'History', url: '#', isSubActive: true },
      { title: 'Starred', url: '#' },
      { title: 'Settings', url: '#' },
    ],
  },
  {
    title: 'Models',
    icon: Command,
    items: [
      { title: 'Genesis', url: '#' },
      { title: 'Explorer', url: '#' },
      { title: 'Quantum', url: '#' },
    ],
  },
  {
    title: 'Documentation',
    icon: BookOpen,
    items: [
      { title: 'Introduction', url: '#' },
      { title: 'Get Started', url: '#' },
      { title: 'Tutorials', url: '#' },
      { title: 'Changelog', url: '#' },
    ],
  },
  {
    title: 'Settings',
    icon: Settings,
    items: [
      { title: 'General', url: '#' },
      { title: 'Team', url: '#' },
      { title: 'Billing', url: '#' },
      { title: 'Limits', url: '#' },
    ],
  },
];

// Mock data untuk menu proyek (Projects - bagian bawah sidebar)
const projectsMenu = [
  { title: 'Design Engineering', icon: SquareTerminal, url: '#' },
  { title: 'Sales & Marketing', icon: SquareTerminal, url: '#' },
  { title: 'Travel', icon: Map, url: '#' },
];
</script>

<template>
  <Sidebar collapsible="icon" class="border-none! bg-card">
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

    <!-- Section 2: Menu Items (Platform & Projects) -->
    <SidebarContent class="p-2 gap-6">
      <!-- Group 1: Platform -->
      <div>
        <span
          class="group-data-[collapsible=icon]:hidden text-[10px] font-bold text-muted-foreground tracking-wider uppercase block px-2 mb-2"
        >
          Platform
        </span>
        <SidebarMenu>
          <div v-for="item in platformMenu" :key="item.title">
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
                    <component :is="item.icon" class="size-4 shrink-0" />
                    <span class="group-data-[collapsible=icon]:hidden font-medium leading-none">
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
                    <SidebarMenuSubItem v-for="sub in item.items" :key="sub.title">
                      <SidebarMenuSubButton size="md" class="text-2sm!" as-child>
                        <a
                          :href="sub.url"
                          :class="sub.isSubActive ? 'text-primary! font-semibold' : ''"
                        >
                          {{ sub.title }}
                        </a>
                      </SidebarMenuSubButton>
                    </SidebarMenuSubItem>
                  </SidebarMenuSub>
                </CollapsibleContent>
              </SidebarMenuItem>
            </Collapsible>
          </div>
        </SidebarMenu>
      </div>

      <!-- Group 2: Projects -->
      <div>
        <span
          class="group-data-[collapsible=icon]:hidden text-[10px] font-bold text-muted-foreground tracking-wider uppercase block px-2 mb-2"
        >
          Projects
        </span>
        <SidebarMenu>
          <SidebarMenuItem v-for="proj in projectsMenu" :key="proj.title">
            <SidebarMenuButton :tooltip="proj.title" as-child>
              <a :href="proj.url" class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2 overflow-hidden">
                  <component :is="proj.icon" class="size-4 shrink-0 text-muted-foreground/70" />
                  <span class="group-data-[collapsible=icon]:hidden text-foreground truncate">
                    {{ proj.title }}
                  </span>
                </div>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
          <!-- More Menu Button -->
          <SidebarMenuItem>
            <SidebarMenuButton tooltip="More" as-child>
              <a href="#" class="flex items-center gap-2">
                <span class="size-4 text-muted-foreground/70 leading-none font-bold select-none">
                  •••
                </span>
                <span class="group-data-[collapsible=icon]:hidden text-muted-foreground">
                  More
                </span>
              </a>
            </SidebarMenuButton>
          </SidebarMenuItem>
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
                    WS
                  </div>
                  <div class="flex flex-col group-data-[collapsible=icon]:hidden min-w-0 text-left">
                    <span class="text-sm font-semibold text-foreground truncate leading-tight">
                      Wibowo Sulistiyo
                    </span>
                    <span class="text-[10px] text-muted-foreground truncate leading-none">
                      wibowo@sulistiyo.com
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
                    WS
                  </div>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold text-xs text-foreground">
                      Wibowo Sulistiyo
                    </span>
                    <span class="truncate text-[10px] text-muted-foreground">
                      wibowo@sulistiyo.com
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

                <!-- Theme Settings & Dark Mode - Clean Native Dropdown Items -->
                <DropdownMenuSeparator />
                <DropdownMenuSub>
                  <DropdownMenuSubTrigger>
                    <Palette class="size-4 mr-2 text-primary" />
                    <span>Accent Color</span>
                  </DropdownMenuSubTrigger>
                  <DropdownMenuSubContent class="w-48 max-h-72 overflow-y-auto">
                    <template v-for="t in themes" :key="t.name">
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
                      <DropdownMenuSeparator v-if="t.name === 'default'" />
                    </template>
                  </DropdownMenuSubContent>
                </DropdownMenuSub>

                <DropdownMenuItem @click="toggleDarkMode($event)">
                  <component :is="isDark ? Sun : Moon" class="size-4 mr-2 text-muted-foreground" />
                  <span>{{ isDark ? 'Light Mode' : 'Dark Mode' }}</span>
                </DropdownMenuItem>
              </DropdownMenuGroup>
              <DropdownMenuSeparator />
              <DropdownMenuItem>
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
