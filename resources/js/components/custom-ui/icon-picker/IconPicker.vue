<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import * as LucideIcons from '@lucide/vue';
import {
  Search,
  X,
  Check,
  HelpCircle,
  ChevronLeft,
  ChevronRight,
  SlidersHorizontal,
  Layers,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import { Modal } from '@/components/custom-ui/modal';

interface Props {
  modelValue?: string;
  placeholder?: string;
  title?: string;
  description?: string;
  disabled?: boolean;
  clearable?: boolean;
  variant?: 'outline' | 'default' | 'secondary' | 'ghost';
  size?: 'default' | 'sm' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Pilih Icon...',
  title: 'Pilih Icon Lucide',
  description: 'Cari dan pilih icon dari 800+ pustaka icon Lucide berdasarkan kategori.',
  disabled: false,
  clearable: true,
  variant: 'outline',
  size: 'default',
});

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void;
  (e: 'change', value: string): void;
}>();

interface Category {
  id: string;
  name: string;
  keywords: string[];
}

const CATEGORIES: Category[] = [
  { id: 'all', name: 'Semua', keywords: [] },
  {
    id: 'general',
    name: 'Umum',
    keywords: [
      'home',
      'star',
      'sparkle',
      'heart',
      'flag',
      'tag',
      'bookmark',
      'sun',
      'moon',
      'globe',
      'compass',
      'search',
      'help',
      'info',
      'circle',
      'square',
      'box',
    ],
  },
  {
    id: 'navigation',
    name: 'Navigasi & Arah',
    keywords: [
      'chevron',
      'arrow',
      'move',
      'rotate',
      'corner',
      'expand',
      'shrink',
      'menu',
      'navigation',
      'map',
      'pin',
      'route',
      'locate',
      'compass',
    ],
  },
  {
    id: 'user',
    name: 'User & Keamanan',
    keywords: [
      'user',
      'users',
      'lock',
      'unlock',
      'shield',
      'key',
      'fingerprint',
      'eye',
      'mail',
      'phone',
      'contact',
      'log',
      'key-round',
    ],
  },
  {
    id: 'media',
    name: 'Media & File',
    keywords: [
      'file',
      'folder',
      'image',
      'video',
      'camera',
      'music',
      'play',
      'pause',
      'mic',
      'volume',
      'film',
      'paperclip',
      'download',
      'upload',
      'file-text',
    ],
  },
  {
    id: 'design',
    name: 'Desain & UI',
    keywords: [
      'palette',
      'wand',
      'brush',
      'pen',
      'edit',
      'crop',
      'layout',
      'grid',
      'layers',
      'sliders',
      'settings',
      'type',
      'bold',
      'italic',
      'sparkles',
      'contrast',
    ],
  },
  {
    id: 'status',
    name: 'Status & Waktu',
    keywords: [
      'calendar',
      'clock',
      'timer',
      'history',
      'bell',
      'alert',
      'check',
      'x',
      'ban',
      'trash',
      'loader',
      'refresh',
      'history',
      'circle-alert',
    ],
  },
  {
    id: 'business',
    name: 'Bisnis & Data',
    keywords: [
      'credit-card',
      'dollar',
      'shopping',
      'cart',
      'bag',
      'briefcase',
      'building',
      'chart',
      'bar-chart',
      'trending',
      'database',
      'table',
      'calculator',
      'store',
    ],
  },
];

const isOpen = ref(false);
const searchQuery = ref('');
const activeCategory = ref<string>('all');
const currentPage = ref(1);
const hoveredIcon = ref<string>('');
const itemsPerPage = 48; // 6x8 grid items per page for clean card layout with text labels

// Extract all valid Lucide icon component names
const allIconNames = computed(() => {
  const keys = Object.keys(LucideIcons);
  const excludedKeys = new Set(['createLucideIcon', 'default', 'Icon', 'LucideIcon']);

  return keys
    .filter((key) => {
      if (excludedKeys.has(key)) return false;
      if (!/^[A-Z]/.test(key)) return false;
      const component = (LucideIcons as Record<string, unknown>)[key];
      return typeof component === 'object' || typeof component === 'function';
    })
    .sort();
});

// Convert PascalCase string into kebab-case
function toKebabCase(str: string): string {
  return str
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
    .toLowerCase();
}

// Filtered icons based on category & search query
const filteredIconNames = computed(() => {
  let list = allIconNames.value;

  // Filter by Category
  if (activeCategory.value !== 'all') {
    const category = CATEGORIES.find((cat) => cat.id === activeCategory.value);
    if (category && category.keywords.length > 0) {
      list = list.filter((name) => {
        const kebabName = toKebabCase(name);
        return category.keywords.some((keyword) => kebabName.includes(keyword));
      });
    }
  }

  // Filter by Search Query
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return list;

  const normalizedQuery = query.replace(/\s+/g, '-');
  return list.filter((name) => {
    const pascalName = name.toLowerCase();
    const kebabName = toKebabCase(name);
    return pascalName.includes(query) || kebabName.includes(normalizedQuery);
  });
});

// Category Name computed
const activeCategoryObj = computed(() => {
  return CATEGORIES.find((cat) => cat.id === activeCategory.value) || CATEGORIES[0];
});

// Total pages calculation
const totalPages = computed(() => {
  return Math.ceil(filteredIconNames.value.length / itemsPerPage) || 1;
});

// Paginated icon list for current view
const paginatedIcons = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredIconNames.value.slice(start, start + itemsPerPage);
});

// Reset page when search or category changes
watch([searchQuery, activeCategory], () => {
  currentPage.value = 1;
});

// Get icon component safely
function getIconComponent(iconName: string) {
  if (!iconName) return null;
  const icon = (LucideIcons as Record<string, unknown>)[iconName];
  return icon || null;
}

const currentIconComponent = computed(() => {
  return getIconComponent(props.modelValue);
});

function selectIcon(iconName: string) {
  emit('update:modelValue', iconName);
  emit('change', iconName);
  isOpen.value = false;
}

function handleClear(event: MouseEvent) {
  event.stopPropagation();
  emit('update:modelValue', '');
  emit('change', '');
}

function nextPage() {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
}

function prevPage() {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
}
</script>

<template>
  <div class="inline-block">
    <!-- Trigger Button -->
    <div class="flex items-center gap-1.5">
      <Button
        :variant="variant"
        :size="size"
        :disabled="disabled"
        type="button"
        class="justify-between gap-2.5 min-w-44 px-3 font-normal"
        @click="isOpen = true"
      >
        <div class="flex items-center gap-2.5 truncate">
          <div
            v-if="props.modelValue && currentIconComponent"
            class="flex size-6 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary"
          >
            <component :is="currentIconComponent" class="size-4 text-primary shrink-0" />
          </div>
          <HelpCircle v-else class="size-4 text-muted-foreground/60 shrink-0" />

          <span v-if="props.modelValue" class="truncate font-medium text-foreground text-xs">
            {{ props.modelValue }}
          </span>
          <span v-else class="text-muted-foreground text-xs">
            {{ placeholder }}
          </span>
        </div>

        <div class="flex items-center gap-1 shrink-0 ml-1">
          <button
            v-if="clearable && props.modelValue && !disabled"
            type="button"
            class="rounded-sm p-0.5 text-muted-foreground/60 hover:text-foreground hover:bg-muted transition-colors cursor-pointer"
            title="Hapus pilihan"
            @click="handleClear"
          >
            <X class="size-3.5" />
          </button>
          <SlidersHorizontal class="size-3.5 text-muted-foreground/70" />
        </div>
      </Button>
    </div>

    <!-- Modal Selection Dialog menggunakan Modal dari custom-ui -->
    <Modal
      v-model:open="isOpen"
      size="xl"
      hide-footer
      content-class="p-0 gap-0 overflow-hidden sm:rounded-2xl border-border"
      body-class="p-0 overflow-hidden flex flex-col max-h-[85vh]"
    >
      <template #header>
        <div class="p-0 pb-3 border-b border-border/60 bg-muted/20 space-y-3">
          <!-- Title Row dengan padding kanan khusus tombol close -->
          <div class="pr-8">
            <div class="text-base font-bold flex items-center gap-2 text-foreground">
              <component :is="currentIconComponent || HelpCircle" class="size-5 text-primary" />
              {{ title }}
            </div>
            <p class="text-xs text-muted-foreground mt-0.5">
              {{ description }}
            </p>
          </div>

          <!-- Search Input (Full Width Presisi dengan Content) -->
          <div class="relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground" />
            <Input
              v-model="searchQuery"
              placeholder="Cari icon (misal: abstract, home, user, search)..."
              class="pl-9 pr-9 h-9 bg-background border-border shadow-2xs text-xs w-full"
              auto-focus
            />
            <button
              v-if="searchQuery"
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground p-0.5 rounded-sm cursor-pointer"
              @click="searchQuery = ''"
            >
              <X class="size-3.5" />
            </button>
          </div>

          <!-- Category Filter Pills dengan ScrollArea shadcn-vue -->
          <ScrollArea class="w-full whitespace-nowrap pt-1">
            <div class="flex items-center gap-1.5 pb-2">
              <button
                v-for="cat in CATEGORIES"
                :key="cat.id"
                type="button"
                :class="[
                  'px-3 py-1 rounded-full text-xs font-medium transition-all shrink-0 cursor-pointer',
                  activeCategory === cat.id
                    ? 'bg-primary text-primary-foreground shadow-2xs'
                    : 'bg-background hover:bg-muted text-muted-foreground hover:text-foreground border border-border/60',
                ]"
                @click="activeCategory = cat.id"
              >
                {{ cat.name }}
              </button>
            </div>
            <ScrollBar orientation="horizontal" class="h-1.5" />
          </ScrollArea>
        </div>
      </template>

      <!-- Body Section dengan ScrollArea shadcn-vue -->
      <ScrollArea class="flex-1 min-h-0 h-95 w-full">
        <div class="p-5 space-y-4">
          <!-- Section Title Header -->
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-foreground flex items-center gap-2">
              <Layers class="size-4 text-primary" />
              {{ activeCategoryObj.name }}
            </h3>
            <span class="text-xs text-muted-foreground font-mono">
              {{ filteredIconNames.length }} icons
            </span>
          </div>

          <!-- Empty State -->
          <div
            v-if="filteredIconNames.length === 0"
            class="flex flex-col items-center justify-center py-12 text-center space-y-2"
          >
            <div
              class="size-10 rounded-full bg-muted/60 flex items-center justify-center text-muted-foreground"
            >
              <Search class="size-5" />
            </div>
            <p class="text-xs font-medium text-foreground">
              Icon "{{ searchQuery }}" tidak ditemukan di kategori ini
            </p>
            <p class="text-[11px] text-muted-foreground max-w-xs">
              Coba ganti kategori ke
              <code class="text-primary font-mono font-medium">Semua</code> atau cari dengan kata
              kunci lain.
            </p>
          </div>

          <!-- Icon Cards Grid -->
          <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2.5">
            <button
              v-for="iconName in paginatedIcons"
              :key="iconName"
              type="button"
              :class="[
                'relative flex flex-col items-center justify-center p-3 rounded-xl border transition-all cursor-pointer group hover:scale-102 active:scale-98 min-h-20.5',
                props.modelValue === iconName
                  ? 'border-primary bg-primary/15 text-primary ring-1 ring-primary/40 shadow-2xs'
                  : 'border-border/50 bg-muted/30 hover:bg-card hover:border-primary/40 hover:shadow-2xs text-foreground',
              ]"
              :title="toKebabCase(iconName)"
              @mouseenter="hoveredIcon = iconName"
              @mouseleave="hoveredIcon = ''"
              @click="selectIcon(iconName)"
            >
              <!-- Badge Centang di Pojok Kanan Atas Kartu -->
              <div
                v-if="props.modelValue === iconName"
                class="absolute top-1.5 right-1.5 size-4 flex items-center justify-center rounded-full bg-primary text-primary-foreground shadow-2xs z-10"
              >
                <Check class="size-2.5 stroke-3" />
              </div>

              <div class="flex items-center justify-center size-8 mb-1">
                <component
                  :is="getIconComponent(iconName)"
                  :class="[
                    'size-6 transition-colors',
                    props.modelValue === iconName
                      ? 'text-primary'
                      : 'text-foreground/80 group-hover:text-primary',
                  ]"
                />
              </div>

              <!-- Label text underneath matching screenshot -->
              <span
                class="text-[10px] text-muted-foreground group-hover:text-foreground font-normal truncate w-full text-center tracking-tight"
              >
                {{ toKebabCase(iconName) }}
              </span>
            </button>
          </div>
        </div>

        <ScrollBar orientation="vertical" class="w-2" />
      </ScrollArea>

      <!-- Footer Info & Pagination (Sinkron padding px-5) -->
      <div
        class="p-3 px-5 border-t border-border/60 bg-muted/20 flex flex-row items-center justify-between gap-2 shrink-0"
      >
        <div class="text-xs text-muted-foreground flex items-center gap-2 truncate min-w-0 flex-1">
          <span class="font-medium text-foreground truncate">
            {{
              hoveredIcon
                ? toKebabCase(hoveredIcon)
                : props.modelValue
                  ? toKebabCase(props.modelValue)
                  : 'Pilih icon dari grid'
            }}
          </span>
          <Badge
            v-if="hoveredIcon || props.modelValue"
            variant="secondary"
            class="text-[10px] px-1.5 py-0 shrink-0"
          >
            {{ hoveredIcon || props.modelValue }}
          </Badge>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="currentPage <= 1"
            type="button"
            title="Halaman sebelumnya"
            @click="prevPage"
          >
            <ChevronLeft class="size-3.5" />
          </Button>
          <span class="text-xs font-mono text-muted-foreground px-1">
            {{ currentPage }}/{{ totalPages }}
          </span>
          <Button
            variant="outline"
            size="icon-sm"
            :disabled="currentPage >= totalPages"
            type="button"
            title="Halaman berikutnya"
            @click="nextPage"
          >
            <ChevronRight class="size-3.5" />
          </Button>
        </div>
      </div>
    </Modal>
  </div>
</template>
