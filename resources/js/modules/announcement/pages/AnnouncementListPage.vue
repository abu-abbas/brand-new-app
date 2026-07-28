<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  Plus,
  Search,
  Edit3,
  Trash2,
  CheckCircle,
  XCircle,
  Megaphone,
} from '@lucide/vue';
import AdminLayout from '@/components/AdminLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AnnouncementLivePreview, {
  type AnnouncementFormData,
} from '../components/AnnouncementLivePreview.vue';

interface AnnouncementMasterItem extends AnnouncementFormData {
  id: number;
}

const router = useRouter();
const searchQuery = ref('');
const selectedCategory = ref('ALL');

const dummyAnnouncements = ref<AnnouncementMasterItem[]>([
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
    is_active: true,
  },
  {
    id: 2,
    code: '#F-102',
    title: 'Pembaruan Cetak Dokumen Digital',
    category: 'FITUR BARU',
    icon: 'sparkles',
    summary: '',
    content:
      'Fitur cetak skema dokumen kini terintegrasi dengan Stempel Digital QR-Code BSrE untuk memvalidasi keabsahan surat resmi secara instan.',
    highlights: [],
    author_dept: 'TIM IT & INFRASTRUKTUR',
    author_tags: ['IT', 'DEV'],
    published_at: '2026-07-26',
    is_active: true,
  },
  {
    id: 3,
    code: '#U-009',
    title: 'Batas Akhir Pemutakhiran Data Mandiri',
    category: 'PENTING',
    icon: 'alert-triangle',
    summary: '',
    content:
      'Seluruh pegawai diimbau untuk segera memverifikasi kelengkapan berkas kepangkatan sebelum 15 Agustus 2026.',
    highlights: [],
    author_dept: 'BIRO SDM & REGIONAL',
    author_tags: ['SDM'],
    published_at: '2026-07-27',
    is_active: true,
  },
  {
    id: 4,
    code: '#G-441',
    title: 'Pemeliharaan Rutin Sistem',
    category: 'INFORMASI',
    icon: 'bell',
    summary: '',
    content:
      'Maintenance mingguan dijadwalkan setiap Sabtu pukul 22:00 WIB. Selama periode ini, sinkronisasi data mungkin tertunda.',
    highlights: [],
    author_dept: 'BIRO KEPEGAWAIAN',
    author_tags: ['AD', 'HRD'],
    published_at: '2023-10-24',
    is_active: false,
  },
]);

const filteredAnnouncements = computed(() => {
  return dummyAnnouncements.value.filter((item) => {
    const matchQuery =
      item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.code.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchCategory =
      selectedCategory.value === 'ALL' || item.category === selectedCategory.value;
    return matchQuery && matchCategory;
  });
});

function createNew() {
  router.push('/announcements/create');
}

function editItem(id: number) {
  router.push(`/announcements/${id}/edit`);
}

function deleteItem(id: number) {
  dummyAnnouncements.value = dummyAnnouncements.value.filter((a) => a.id !== id);
}

function toggleStatus(id: number) {
  const item = dummyAnnouncements.value.find((a) => a.id === id);
  if (item) {
    item.is_active = !item.is_active;
  }
}
</script>

<template>
  <AdminLayout parent-title="Master Data" title="Papan Pengumuman">
    <div class="space-y-6">
      <!-- Full Soft Primary Tint Integrated Card -->
      <Card
        class="border-primary/20 bg-primary/5 dark:bg-primary/10 shadow-xs rounded-2xl overflow-hidden"
      >
        <CardContent class="p-4 md:p-6 space-y-4">
          <!-- Top Section: Header Title + Action Button (Full Block on Mobile) -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-start gap-3">
              <div
                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-xs mt-0.5"
              >
                <Megaphone class="size-5" />
              </div>
              <div>
                <h2 class="text-base font-bold text-foreground">Master Data Pengumuman</h2>
                <p class="text-sm text-muted-foreground">
                  Kelola pengumuman yang tayang di papan pengumuman portal login publik.
                </p>
              </div>
            </div>

            <!-- Full Width Block Button on Mobile, Auto Width on Desktop -->
            <Button
              class="w-full sm:w-auto justify-center gap-2 font-semibold text-xs tracking-wide uppercase h-10 px-5 shrink-0"
              @click="createNew"
            >
              <Plus class="size-4" />
              <span>Tambah Pengumuman Baru</span>
            </Button>
          </div>

          <!-- Separator (Soft Primary Tinted) -->
          <Separator class="bg-primary/20" />

          <!-- Bottom Section: Search Input + Category Tabs -->
          <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-1">
            <!-- Search Bar -->
            <div class="relative w-full sm:w-80">
              <Search
                class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground pointer-events-none"
              />
              <Input
                v-model="searchQuery"
                placeholder="Cari kode atau judul pengumuman..."
                class="pl-9 h-9 text-sm bg-background/80 dark:bg-background/50 border-primary/20"
              />
            </div>

            <!-- Category Filter Tabs -->
            <div class="flex items-center gap-1.5 overflow-x-auto w-full sm:w-auto pb-1 sm:pb-0">
              <button
                v-for="cat in ['ALL', 'TEKNIS', 'FITUR BARU', 'PENTING', 'INFORMASI']"
                :key="cat"
                type="button"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
                :class="
                  selectedCategory === cat
                    ? 'bg-primary text-primary-foreground shadow-xs'
                    : 'bg-background/60 hover:bg-background dark:bg-background/40 text-muted-foreground'
                "
                @click="selectedCategory = cat"
              >
                {{ cat === 'ALL' ? 'SEMUA' : cat }}
              </button>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Masonry Column Layout -->
      <div class="columns-1 lg:columns-2 gap-5 space-y-5">
        <div
          v-for="item in filteredAnnouncements"
          :key="item.id"
          class="break-inside-avoid relative group transition-all duration-300"
        >
          <!-- Pure Announcement Card View -->
          <AnnouncementLivePreview :data="item" :show-live-badge="false" />

          <!-- Floating Glassmorphic Admin Action Bar -->
          <div
            class="absolute bottom-3 right-3 z-20 flex items-center gap-2 p-1.5 rounded-full bg-background/95 dark:bg-card/95 backdrop-blur-md border border-border shadow-lg transition-all duration-300 group-hover:scale-[1.02]"
          >
            <!-- Toggle Status Tayang Button -->
            <button
              type="button"
              class="inline-flex items-center gap-1.5 text-[11px] font-bold px-3 py-1.5 rounded-full cursor-pointer transition-all active:scale-95"
              :class="
                item.is_active
                  ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/25'
                  : 'bg-muted text-muted-foreground hover:bg-muted/80'
              "
              @click="toggleStatus(item.id)"
            >
              <CheckCircle v-if="item.is_active" class="h-3.5 w-3.5 text-emerald-500" />
              <XCircle v-else class="h-3.5 w-3.5 text-muted-foreground" />
              <span>{{ item.is_active ? 'Tayang' : 'Draft' }}</span>
            </button>

            <div class="h-4 w-px bg-border"></div>

            <!-- Edit Button -->
            <button
              type="button"
              class="h-7 px-3 rounded-full bg-muted/60 hover:bg-primary hover:text-primary-foreground text-foreground text-[11px] font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
              title="Edit Pengumuman"
              @click="editItem(item.id)"
            >
              <Edit3 class="h-3.5 w-3.5" />
              <span>Edit</span>
            </button>

            <!-- Delete Button -->
            <button
              type="button"
              class="h-7 px-3 rounded-full bg-muted/60 hover:bg-red-500 hover:text-white text-muted-foreground text-[11px] font-semibold flex items-center gap-1.5 transition-all cursor-pointer"
              title="Hapus Pengumuman"
              @click="deleteItem(item.id)"
            >
              <Trash2 class="h-3.5 w-3.5" />
              <span>Hapus</span>
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="filteredAnnouncements.length === 0"
        class="text-center py-12 text-muted-foreground space-y-2 bg-card rounded-2xl border border-border"
      >
        <Megaphone class="h-10 w-10 mx-auto opacity-40" />
        <p class="text-sm font-medium">Tidak ada pengumuman yang sesuai kata kunci atau filter.</p>
      </div>
    </div>
  </AdminLayout>
</template>
