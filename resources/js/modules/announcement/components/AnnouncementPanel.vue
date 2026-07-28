<script setup lang="ts">
import { ref, onMounted, onUnmounted, type Component } from 'vue';
import {
  Cog,
  Bell,
  Sparkles,
  AlertTriangle,
  Download,
  ShieldAlert,
  Clock,
  CheckCircle2,
  FileText,
  Info,
  Search,
  SlidersHorizontal,
} from '@lucide/vue';

interface AnnouncementItem {
  id: number;
  code: string;
  title: string;
  category: 'TEKNIS' | 'FITUR BARU' | 'PENTING' | 'INFORMASI';
  icon: string;
  summary?: string;
  content: string;
  highlights?: Array<{ icon: string; text: string }>;
  author_dept: string;
  author_tags?: string[];
  published_at: string;
}

const announcements = ref<AnnouncementItem[]>([
  {
    id: 1,
    code: '#T-2023',
    title: 'Panduan Akses & Sesi',
    category: 'TEKNIS',
    icon: 'cog',
    summary:
      'Penting untuk mengikuti standar operasional penggunaan portal guna menjaga keamanan data personal Anda.',
    content:
      'Browser Chrome/Firefox versi terbaru wajib digunakan. Hindari koneksi Wi-Fi publik saat melakukan otentikasi login.',
    highlights: [
      { icon: 'download', text: 'Browser Chrome/Firefox versi terbaru wajib digunakan.' },
      { icon: 'shield-alert', text: 'Gunakan koneksi privat, hindari Wi-Fi publik saat login.' },
      { icon: 'clock', text: 'Sesi otomatis berakhir setelah 30 menit tidak aktif.' },
    ],
    author_dept: 'BIRO KEPEGAWAIAN',
    author_tags: ['AD', 'HRD'],
    published_at: '2023-10-24',
  },
  {
    id: 2,
    code: '#F-102',
    title: 'Pembaruan Cetak Dokumen Digital',
    category: 'FITUR BARU',
    icon: 'sparkles',
    content:
      'Fitur cetak skema dokumen kini terintegrasi dengan Stempel Digital QR-Code BSrE untuk memvalidasi keabsahan surat resmi secara instan.',
    author_dept: 'TIM IT & INFRASTRUKTUR',
    author_tags: ['IT', 'DEV'],
    published_at: '2026-07-26',
  },
]);

const iconMap: Record<string, Component> = {
  cog: Cog,
  bell: Bell,
  sparkles: Sparkles,
  'alert-triangle': AlertTriangle,
  download: Download,
  'shield-alert': ShieldAlert,
  clock: Clock,
  'check-circle': CheckCircle2,
  'file-text': FileText,
  info: Info,
};

function getIconComponent(iconName: string) {
  return iconMap[iconName] || Info;
}

function getHighlightIcon(iconName: string) {
  return iconMap[iconName] || Info;
}

function getCategoryBadgeClass(category: string) {
  switch (category) {
    case 'TEKNIS':
    case 'FITUR BARU':
    case 'PENTING':
      return 'bg-primary/15 text-primary border-primary/20';
    case 'INFORMASI':
    default:
      return 'bg-muted text-muted-foreground border-border';
  }
}

function getCategoryIconBgClass(category: string) {
  switch (category) {
    case 'TEKNIS':
    case 'FITUR BARU':
    case 'PENTING':
      return 'bg-primary/15 text-primary border-primary/20';
    case 'INFORMASI':
    default:
      return 'bg-muted text-muted-foreground border-border';
  }
}

// Stable Hysteresis Scroll Threshold
const isScrolled = ref(false);
const scrollContainer = ref<HTMLElement | null>(null);

function handleScroll() {
  if (!scrollContainer.value) return;
  const scrollTop = scrollContainer.value.scrollTop;

  if (!isScrolled.value && scrollTop > 35) {
    isScrolled.value = true;
  } else if (isScrolled.value && scrollTop < 5) {
    isScrolled.value = false;
  }
}

onMounted(() => {
  if (scrollContainer.value) {
    scrollContainer.value.addEventListener('scroll', handleScroll, { passive: true });
  }
});

onUnmounted(() => {
  if (scrollContainer.value) {
    scrollContainer.value.removeEventListener('scroll', handleScroll);
  }
});
</script>

<template>
  <div
    ref="scrollContainer"
    class="h-full bg-[#f4f4f6] dark:bg-[#09090b] text-foreground transition-colors duration-300 overflow-y-auto"
  >
    <!-- Max-Width Centered Container -->
    <div class="w-full max-w-3xl mx-auto">
      <!-- Shrinking Sticky Header (Extends solid background flush to top-0) -->
      <div
        class="sticky top-0 z-20 bg-[#f4f4f6] dark:bg-[#09090b] transition-all duration-300 px-4 sm:px-6"
        :class="
          isScrolled
            ? 'pt-3 pb-3 shadow-[0_16px_24px_-4px_#f4f4f6] dark:shadow-[0_16px_24px_-4px_#09090b]'
            : 'pt-5 pb-4 sm:pt-6 sm:pb-5'
        "
      >
        <div class="flex items-center justify-between">
          <div>
            <!-- Update Terkini Badge -->
            <div
              class="flex items-center gap-2 transition-all duration-300 overflow-hidden"
              :class="isScrolled ? 'max-h-0 opacity-0 mb-0' : 'max-h-6 opacity-100 mb-1.5'"
            >
              <span class="h-2 w-2 rounded-full bg-primary animate-pulse"></span>
              <span
                class="text-[11px] font-extrabold uppercase tracking-widest text-primary"
              >
                UPDATE TERKINI
              </span>
            </div>

            <!-- Shrinking Title -->
            <h2
              class="font-extrabold text-foreground tracking-tight transition-all duration-300"
              :class="isScrolled ? 'text-lg sm:text-xl' : 'text-2xl sm:text-3xl'"
            >
              Papan Pengumuman
            </h2>
          </div>

          <!-- Right Action Buttons -->
          <div class="flex items-center gap-2 pr-8 sm:pr-0">
            <button
              type="button"
              class="rounded-full border border-border/80 bg-background/80 hover:bg-background text-muted-foreground hover:text-foreground flex items-center justify-center shadow-xs transition-all duration-300 cursor-pointer"
              :class="isScrolled ? 'h-8 w-8' : 'h-9 w-9'"
              title="Cari Pengumuman"
            >
              <Search class="h-4 w-4" />
            </button>
            <button
              type="button"
              class="rounded-full border border-border/80 bg-background/80 hover:bg-background text-muted-foreground hover:text-foreground flex items-center justify-center shadow-xs transition-all duration-300 cursor-pointer"
              :class="isScrolled ? 'h-8 w-8' : 'h-9 w-9'"
              title="Filter Kategori"
            >
              <SlidersHorizontal class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Content Container -->
      <div class="px-4 sm:px-6 pt-3 pb-8 space-y-5">
        <div
          v-for="item in announcements"
          :key="item.id"
          class="bg-card text-card-foreground rounded-2xl p-5 sm:p-6 shadow-sm border border-border space-y-4"
        >
          <!-- Card Header -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3.5">
              <div
                class="h-12 w-12 rounded-2xl flex items-center justify-center shrink-0 border"
                :class="getCategoryIconBgClass(item.category)"
              >
                <component :is="getIconComponent(item.icon)" class="h-6 w-6" />
              </div>
              <div class="space-y-1">
                <div>
                  <span
                    class="border text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md inline-block"
                    :class="getCategoryBadgeClass(item.category)"
                  >
                    {{ item.category }}
                  </span>
                </div>
                <h3 class="text-base font-bold text-foreground leading-tight">
                  {{ item.title }}
                </h3>
              </div>
            </div>
            <span class="text-xs font-mono font-medium text-muted-foreground pt-0.5">
              {{ item.code }}
            </span>
          </div>

          <!-- Quote Summary (Leading Snug) -->
          <div
            v-if="item.summary"
            class="pl-3 border-l-2 border-primary py-0.5 text-xs text-muted-foreground italic leading-snug"
          >
            {{ item.summary }}
          </div>

          <!-- Content Text (Leading Snug) -->
          <div class="text-xs text-muted-foreground leading-snug whitespace-pre-line">
            {{ item.content }}
          </div>

          <!-- Highlights Grid (Adaptive Inline Row on Narrow Media) -->
          <div
            v-if="item.highlights && item.highlights.length > 0"
            class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 pt-1"
          >
            <div
              v-for="(h, hIdx) in item.highlights"
              :key="hIdx"
              class="bg-muted/50 dark:bg-muted/30 rounded-xl p-3 sm:p-3.5 border border-border/60 flex flex-row sm:flex-col items-center sm:items-start gap-2.5 sm:gap-0 justify-start sm:justify-between"
            >
              <component
                :is="getHighlightIcon(h.icon)"
                class="h-4 w-4 text-primary shrink-0 sm:mb-2"
              />
              <p class="text-xs text-foreground font-medium leading-snug">
                {{ h.text }}
              </p>
            </div>
          </div>

          <!-- Card Footer -->
          <div
            class="flex items-center justify-between pt-3 border-t border-border text-[11px] text-muted-foreground"
          >
            <div class="flex items-center gap-2">
              <div v-if="item.author_tags" class="flex -space-x-1.5 overflow-hidden">
                <span
                  v-for="(tag, tIdx) in item.author_tags"
                  :key="tIdx"
                  class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary text-[9px] font-bold text-primary-foreground ring-2 ring-card"
                >
                  {{ tag }}
                </span>
              </div>
              <span
                class="font-semibold text-muted-foreground uppercase tracking-wider text-[10px]"
              >
                OLEH: {{ item.author_dept }}
              </span>
            </div>
            <span class="italic">Update: {{ item.published_at }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
