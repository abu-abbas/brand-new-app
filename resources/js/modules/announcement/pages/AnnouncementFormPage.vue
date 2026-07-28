<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { ArrowLeft, Moon, Sun, CheckCircle2 } from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { Button } from '@/components/ui/button';
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
  icon: 'cog',
  summary:
    'Penting untuk mengikuti standar operasional penggunaan portal guna menjaga keamanan data personal Anda.',
  content:
    'Maintenance mingguan dijadwalkan setiap Sabtu pukul 22:00 WIB. Selama periode ini, sinkronisasi data mungkin tertunda.',
  highlights: [
    { icon: 'download', text: 'Browser Chrome/Firefox versi terbaru wajib digunakan.' },
    { icon: 'shield-alert', text: 'Gunakan koneksi privat, hindari Wi-Fi publik saat login.' },
    { icon: 'clock', text: 'Sesi otomatis berakhir setelah 30 menit tidak aktif.' },
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
    <div class="space-y-4">
      <!-- Top Action Bar -->
      <div class="flex items-center justify-between">
        <Button
          variant="ghost"
          size="sm"
          class="gap-1.5 text-xs font-semibold"
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
            <Sun v-if="isPreviewDark" class="h-3.5 w-3.5 text-amber-500" />
            <Moon v-else class="h-3.5 w-3.5 text-slate-700" />
            <span>Simulasi Mode: {{ isPreviewDark ? 'Dark' : 'Light' }}</span>
          </Button>
        </div>
      </div>

      <!-- Success Notification Toast mockup -->
      <div
        v-if="isSaved"
        class="bg-emerald-500/15 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-2xl flex items-center gap-2 text-xs font-semibold animate-bounce"
      >
        <CheckCircle2 class="h-4 w-4 text-emerald-600" />
        <span>Pengumuman berhasil disimpan ke database! Mengalihkan ke daftar master...</span>
      </div>

      <!-- Main Split View Layout (Form Builder Left, Live Preview Right) -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Form Builder (7 cols) -->
        <div class="lg:col-span-7">
          <AnnouncementFormBuilder v-model:form-data="formData" @submit="handleSubmit" />
        </div>

        <!-- Right Column: Live Preview Sticky Panel (5 cols) -->
        <div class="lg:col-span-5 sticky top-6">
          <div
            class="p-5 rounded-3xl border border-border shadow-xs transition-colors duration-300"
            :class="
              isPreviewDark ? 'dark bg-[#09090b] text-slate-50' : 'bg-[#f4f4f6] text-slate-900'
            "
          >
            <AnnouncementLivePreview :data="formData" />
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
