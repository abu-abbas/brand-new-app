<script setup lang="ts">
import { computed, type Component } from 'vue';
import {
  Cog,
  Bell,
  Sparkles,
  AlertTriangle,
  Download,
  ShieldAlert,
  Clock,
  BookOpen,
  Mail,
  Megaphone,
  CheckCircle2,
  FileText,
  Info,
} from '@lucide/vue';

export interface HighlightItem {
  icon: string;
  text: string;
}

export interface AnnouncementFormData {
  code: string;
  title: string;
  category: 'TEKNIS' | 'FITUR BARU' | 'PENTING' | 'INFORMASI';
  icon: string;
  summary: string;
  content: string;
  highlights: HighlightItem[];
  author_dept: string;
  author_tags: string[];
  published_at: string;
  is_active: boolean;
}

const props = withDefaults(
  defineProps<{
    data: AnnouncementFormData;
    showLiveBadge?: boolean;
  }>(),
  {
    showLiveBadge: true,
  },
);

// Map Icon string ke Lucide Component
const iconMap: Record<string, Component> = {
  cog: Cog,
  bell: Bell,
  sparkles: Sparkles,
  'alert-triangle': AlertTriangle,
  download: Download,
  'shield-alert': ShieldAlert,
  clock: Clock,
  'book-open': BookOpen,
  mail: Mail,
  megaphone: Megaphone,
  'check-circle': CheckCircle2,
  'file-text': FileText,
  info: Info,
};

const mainIconComponent = computed(() => iconMap[props.data.icon] || Cog);

function getHighlightIcon(iconName: string) {
  return iconMap[iconName] || Info;
}

const categoryStyles = computed(() => {
  switch (props.data.category) {
    case 'TEKNIS':
      return {
        badge: 'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/20',
        iconBg: 'bg-orange-500/15 text-orange-600 dark:text-orange-400 border-orange-500/20',
      };
    case 'FITUR BARU':
      return {
        badge: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20',
        iconBg: 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/20',
      };
    case 'PENTING':
      return {
        badge: 'bg-red-500/15 text-red-600 dark:text-red-400 border-red-500/20',
        iconBg: 'bg-red-500/15 text-red-600 dark:text-red-400 border-red-500/20',
      };
    case 'INFORMASI':
    default:
      return {
        badge: 'bg-muted text-muted-foreground border-border',
        iconBg: 'bg-muted text-muted-foreground border-border',
      };
  }
});
</script>

<template>
  <div class="space-y-3">
    <!-- Live Badge Bar (Only shown if showLiveBadge is true) -->
    <div v-if="showLiveBadge" class="flex items-center justify-between px-1">
      <div class="flex items-center gap-2">
        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
        <span class="text-xs font-bold uppercase tracking-wider text-muted-foreground">
          Live Realtime Preview
        </span>
      </div>
      <span
        class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded-full"
        :class="
          data.is_active
            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400'
            : 'bg-muted text-muted-foreground'
        "
      >
        {{ data.is_active ? 'Status: Aktif' : 'Status: Draft' }}
      </span>
    </div>

    <!-- Announcement Card (Pure Card with leading-snug line height) -->
    <div
      class="bg-card text-card-foreground rounded-2xl p-6 shadow-sm border border-border space-y-4 transition-all duration-300"
    >
      <!-- Card Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3.5">
          <div
            class="h-12 w-12 rounded-2xl flex items-center justify-center shrink-0 border transition-all"
            :class="categoryStyles.iconBg"
          >
            <component :is="mainIconComponent" class="h-6 w-6" />
          </div>
          <div class="space-y-1">
            <div>
              <span
                class="border text-[10px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md inline-block"
                :class="categoryStyles.badge"
              >
                {{ data.category || 'KATEGORI' }}
              </span>
            </div>
            <h3 class="text-base font-bold text-foreground leading-tight">
              {{ data.title || 'Judul Pengumuman...' }}
            </h3>
          </div>
        </div>
        <span class="text-xs font-mono font-medium text-muted-foreground pt-0.5">
          {{ data.code || '#CODE' }}
        </span>
      </div>

      <!-- Quote Summary (Leading Snug Line Height) -->
      <div
        v-if="data.summary"
        class="pl-3 border-l-2 border-orange-500 dark:border-orange-400 py-0.5 text-xs text-muted-foreground italic leading-snug"
      >
        {{ data.summary }}
      </div>

      <!-- Content Text (Leading Snug Line Height) -->
      <div
        v-if="data.content"
        class="text-xs text-muted-foreground leading-snug whitespace-pre-line"
      >
        {{ data.content }}
      </div>

      <!-- Highlights Grid (If 1-3 items exist) -->
      <div
        v-if="data.highlights && data.highlights.length > 0"
        class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-1"
      >
        <div
          v-for="(item, idx) in data.highlights"
          :key="idx"
          class="bg-muted/50 dark:bg-muted/30 rounded-xl p-3.5 border border-border/60 flex flex-col justify-between"
        >
          <component
            :is="getHighlightIcon(item.icon)"
            class="h-4 w-4 text-orange-500 dark:text-orange-400 mb-2"
          />
          <p class="text-xs text-foreground font-medium leading-snug">
            {{ item.text || 'Teks poin info...' }}
          </p>
        </div>
      </div>

      <!-- Card Footer -->
      <div
        class="flex items-center justify-between pt-3 border-t border-border text-[11px] text-muted-foreground"
      >
        <div class="flex items-center gap-2">
          <div
            v-if="data.author_tags && data.author_tags.length > 0"
            class="flex -space-x-1.5 overflow-hidden"
          >
            <span
              v-for="(tag, tIdx) in data.author_tags"
              :key="tIdx"
              class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-600 dark:bg-orange-500 text-[9px] font-bold text-white ring-2 ring-card"
            >
              {{ tag }}
            </span>
          </div>
          <span class="font-semibold text-muted-foreground uppercase tracking-wider text-[10px]">
            OLEH: {{ data.author_dept || 'UNIT KERJA' }}
          </span>
        </div>
        <span class="italic">Update: {{ data.published_at || 'Hari ini' }}</span>
      </div>
    </div>
  </div>
</template>
