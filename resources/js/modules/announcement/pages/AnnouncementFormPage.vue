<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Moon, Sun, CheckCircle2 } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { ResizablePanelGroup, ResizablePanel, ResizableHandle } from '@/components/ui/resizable';
import AnnouncementFormBuilder from '../components/AnnouncementFormBuilder.vue';
import AnnouncementLivePreview, {
  type AnnouncementFormData,
} from '../components/AnnouncementLivePreview.vue';

const router = useRouter();
const isPreviewDark = ref(false);

const formData = reactive<AnnouncementFormData>({
  code: '#T-2026',
  title: 'Panduan Akses & Sesi Sistem Portal',
  category: 'TEKNIS',
  icon: 'Cog',
  summary:
    'Penting untuk mengikuti standar operasional penggunaan portal guna menjaga keamanan data personal Anda.',
  content:
    'Maintenance mingguan dijadwalkan setiap Sabtu pukul 22:00 WIB. Selama periode ini, sinkronisasi data mungkin tertunda.',
  highlights: [
    { icon: 'Download', text: 'Browser Chrome/Firefox versi terbaru wajib digunakan.' },
    { icon: 'ShieldAlert', text: 'Gunakan koneksi privat, hindari Wi-Fi publik saat login.' },
    { icon: 'Clock', text: 'Sesi otomatis berakhir setelah 30 menit tidak aktif.' },
  ],
  author_dept: 'BIRO KEPEGAWAIAN',
  author_tags: ['AD', 'HRD'],
  published_at: new Date().toISOString().split('T')[0],
  is_active: true,
});

const isSaved = ref(false);

function handleSubmit() {
  isSaved.value = true;
  window.setTimeout(() => {
    isSaved.value = false;
    router.push('/announcements');
  }, 1200);
}
</script>

<template>
  <AdminLayout parent-title="Master Data" title="Form Pengumuman">
    <!-- Top Action Bar (shrink-0 = tidak meregang) -->
    <div class="flex items-center justify-between shrink-0">
      <Button
        variant="secondary"
        size="sm"
        class="gap-1.5 text-xs font-medium"
        @click="router.back()"
      >
        <ArrowLeft class="h-4 w-4" />
        <span>Kembali ke Daftar Master</span>
      </Button>

      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          class="h-8 gap-1.5 text-xs font-medium"
          @click="isPreviewDark = !isPreviewDark"
        >
          <Moon v-if="isPreviewDark" class="h-3.5 w-3.5 text-blue-400" />
          <Sun v-else class="h-3.5 w-3.5 text-amber-500" />
          <span>Simulasi Mode: {{ isPreviewDark ? 'Dark' : 'Light' }}</span>
        </Button>
      </div>
    </div>

    <!-- Success Notification Toast mockup -->
    <div
      v-if="isSaved"
      class="shrink-0 bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-2xl flex items-center gap-2 text-xs font-semibold animate-bounce"
    >
      <CheckCircle2 class="h-4 w-4 text-emerald-600" />
      <span>Pengumuman berhasil disimpan ke database! Mengalihkan ke daftar master...</span>
    </div>

    <!-- Main Split View Layout: flex-1 min-h-0 agar mengisi sisa tinggi yang tersedia -->
    <ResizablePanelGroup direction="horizontal" class="flex-1 min-h-0 w-full">
      <!-- Left Panel: Form Builder — ScrollArea shadcn-vue untuk scrollbar konsisten lintas browser -->
      <ResizablePanel :default-size="58" :min-size="30" :max-size="75">
        <div class="p-0.5 pr-3 md:pr-4">
          <AnnouncementFormBuilder v-model:form-data="formData" @submit="handleSubmit" />
        </div>
      </ResizablePanel>

      <!-- Resizable Handle bawaan shadcn-vue -->
      <ResizableHandle with-handle class="hover:bg-primary/20 transition-colors" />

      <!-- Right Panel: Live Preview — TIDAK scroll, selalu terlihat -->
      <ResizablePanel :default-size="42" :min-size="25" :max-size="70">
        <div class="pl-3 md:pl-4">
          <div :class="isPreviewDark ? 'dark' : 'light'">
            <AnnouncementLivePreview :data="formData" />
          </div>
        </div>
      </ResizablePanel>
    </ResizablePanelGroup>
  </AdminLayout>
</template>
