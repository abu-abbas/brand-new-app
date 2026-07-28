<script setup lang="ts">
import { ref, computed } from 'vue';
import { Plus, Trash2, Sparkles, Tag, Layers, AlignLeft, ShieldCheck } from '@lucide/vue';
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import type { AnnouncementFormData } from './AnnouncementLivePreview.vue';

const props = defineProps<{
  formData: AnnouncementFormData;
}>();

const emit = defineEmits<{
  (e: 'update:formData', val: AnnouncementFormData): void;
  (e: 'submit'): void;
}>();

// Proxy computed object untuk menghindari warning vue/no-mutating-props
const localForm = computed({
  get: () => props.formData,
  set: (val) => emit('update:formData', val),
});

const availableIcons = [
  { label: 'Gear / Teknis', value: 'cog' },
  { label: 'Bell / Informasi', value: 'bell' },
  { label: 'Sparkles / Fitur Baru', value: 'sparkles' },
  { label: 'Alert / Penting', value: 'alert-triangle' },
  { label: 'Download', value: 'download' },
  { label: 'Shield Alert', value: 'shield-alert' },
  { label: 'Clock / Waktu', value: 'clock' },
  { label: 'Book / Buku Panduan', value: 'book-open' },
  { label: 'Mail / Support', value: 'mail' },
  { label: 'Megaphone / Berita', value: 'megaphone' },
];

const availableHighlightIcons = [
  { label: 'Download', value: 'download' },
  { label: 'Shield / Keamanan', value: 'shield-alert' },
  { label: 'Clock / Waktu', value: 'clock' },
  { label: 'Check Circle', value: 'check-circle' },
  { label: 'File Text', value: 'file-text' },
  { label: 'Info', value: 'info' },
];

const tagInput = ref(props.formData.author_tags ? props.formData.author_tags.join(', ') : '');

function updateTags() {
  const tags = tagInput.value
    .split(',')
    .map((t) => t.trim().toUpperCase())
    .filter((t) => t.length > 0);
  localForm.value.author_tags = tags;
}

function addHighlightItem() {
  if (!localForm.value.highlights) {
    localForm.value.highlights = [];
  }
  if (localForm.value.highlights.length < 3) {
    localForm.value.highlights.push({
      icon: 'info',
      text: '',
    });
  }
}

function removeHighlightItem(index: number) {
  if (localForm.value.highlights) {
    localForm.value.highlights.splice(index, 1);
  }
}

function handleSubmit() {
  emit('submit');
}
</script>

<template>
  <Card class="border-border bg-card shadow-xs">
    <CardHeader>
      <div class="flex items-center gap-2">
        <div class="h-8 w-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
          <Sparkles class="h-4 w-4" />
        </div>
        <div>
          <CardTitle class="text-lg font-bold">Form Master Pengumuman</CardTitle>
          <CardDescription class="text-xs">
            Isi data pengumuman secara visual. Hasil tampilan langsung ter-update di panel preview.
          </CardDescription>
        </div>
      </div>
    </CardHeader>

    <CardContent class="space-y-5">
      <!-- Section 1: Header & Identitas -->
      <div class="space-y-3 pt-1">
        <div
          class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
        >
          <Layers class="h-3.5 w-3.5" />
          <span>1. Informasi Utama</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <!-- Kode Pengumuman -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">Kode Pengumuman</label>
            <Input
              v-model="localForm.code"
              placeholder="Contoh: #T-2023"
              class="h-9 font-mono text-sm w-full"
              maxlength="20"
            />
          </div>

          <!-- Kategori Select (shadcn-vue text-sm matching Input) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">Kategori</label>
            <Select v-model="localForm.category">
              <SelectTrigger class="w-full h-9 text-sm px-3">
                <SelectValue placeholder="Pilih Kategori" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="TEKNIS">TEKNIS</SelectItem>
                <SelectItem value="FITUR BARU">FITUR BARU</SelectItem>
                <SelectItem value="PENTING">PENTING</SelectItem>
                <SelectItem value="INFORMASI">INFORMASI</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        <!-- Judul -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-foreground">Judul Pengumuman</label>
          <Input
            v-model="localForm.title"
            placeholder="Masukkan judul pengumuman..."
            class="h-9 text-sm w-full"
            maxlength="100"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <!-- Main Icon Select (shadcn-vue text-sm matching Input) -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">Icon Header</label>
            <Select v-model="localForm.icon">
              <SelectTrigger class="w-full h-9 text-sm px-3">
                <SelectValue placeholder="Pilih Icon Header" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="ic in availableIcons" :key="ic.value" :value="ic.value">
                  {{ ic.label }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <!-- Unit Penerbit -->
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">Unit Penerbit / Biro</label>
            <Input
              v-model="localForm.author_dept"
              placeholder="Contoh: BIRO KEPEGAWAIAN"
              class="h-9 text-sm w-full"
              maxlength="50"
            />
          </div>
        </div>

        <!-- Tag Avatar Inisial -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-foreground">
            Inisial Avatar Tag (Pisahkan dengan Koma)
          </label>
          <Input
            v-model="tagInput"
            placeholder="Contoh: AD, HRD"
            class="h-9 text-sm w-full"
            maxlength="30"
            @input="updateTags"
          />
        </div>
      </div>

      <!-- Section 2: Ringkasan & Isi Deskripsi -->
      <div class="space-y-3 border-t border-border pt-4">
        <div
          class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
        >
          <AlignLeft class="h-3.5 w-3.5" />
          <span>2. Konten Pengumuman</span>
        </div>

        <!-- Ringkasan / Quote Textarea -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-foreground">
            Kutipan Ringkasan (Optional - Tampil Bergaris Oranye)
          </label>
          <Textarea
            v-model="localForm.summary"
            rows="2"
            placeholder="Kutipan singkat penekanan info (maks. 180 karakter)..."
            class="w-full text-sm bg-background border-input focus-visible:ring-1 focus-visible:ring-primary min-h-15"
            maxlength="180"
          />
        </div>

        <!-- Deskripsi Utama Textarea -->
        <div class="space-y-1.5">
          <label class="text-xs font-semibold text-foreground">Isi Deskripsi Pengumuman</label>
          <Textarea
            v-model="localForm.content"
            rows="3"
            placeholder="Tuliskan detail pengumuman di sini (maks. 500 karakter)..."
            class="w-full text-sm bg-background border-input focus-visible:ring-1 focus-visible:ring-primary min-h-20"
            maxlength="500"
          />
        </div>
      </div>

      <!-- Section 3: Sub Cards Highlights (Dynamic 1-3 List) -->
      <div class="space-y-3 border-t border-border pt-4">
        <div class="space-y-1">
          <div class="flex items-center justify-between">
            <div
              class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
            >
              <Tag class="h-3.5 w-3.5" />
              <span>3. Kartu Poin Informasi</span>
            </div>
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="h-7 text-[11px] gap-1"
              :disabled="localForm.highlights && localForm.highlights.length >= 3"
              @click="addHighlightItem"
            >
              <Plus class="h-3 w-3" />
              <span>Tambah Poin</span>
            </Button>
          </div>
          <p class="text-[11px] text-muted-foreground">
            Maksimal 3 poin informasi. Tiap poin dibatasi maksimal 75 karakter.
          </p>
        </div>

        <div v-if="localForm.highlights && localForm.highlights.length > 0" class="space-y-2.5">
          <div
            v-for="(item, idx) in localForm.highlights"
            :key="idx"
            class="flex items-center gap-2 p-2.5 bg-muted/30 border border-border rounded-lg"
          >
            <div class="w-40 shrink-0">
              <Select v-model="item.icon">
                <SelectTrigger class="w-full h-8">
                  <SelectValue placeholder="Icon" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem
                    v-for="hIc in availableHighlightIcons"
                    :key="hIc.value"
                    :value="hIc.value"
                  >
                    {{ hIc.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <Input
              v-model="item.text"
              placeholder="Isi singkat poin (maks. 75 karakter)..."
              class="h-8 text-xs flex-1"
              maxlength="75"
            />
            <Button
              type="button"
              variant="ghost"
              size="icon"
              class="h-8 w-8 text-muted-foreground hover:text-red-500 shrink-0"
              @click="removeHighlightItem(idx)"
            >
              <Trash2 class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
        <div v-else class="text-xs text-muted-foreground italic py-1">
          Belum ada poin kartu informasi. Klik "Tambah Poin" jika ingin menambahkan poin khusus.
        </div>
      </div>

      <!-- Section 4: Status & Publish Date -->
      <div class="space-y-3 border-t border-border pt-4">
        <div
          class="text-xs font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5"
        >
          <ShieldCheck class="h-3.5 w-3.5" />
          <span>4. Status & Publikasi</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center">
          <div class="space-y-1.5">
            <label class="text-xs font-semibold text-foreground">Tanggal Publikasi</label>
            <Input v-model="localForm.published_at" type="date" class="h-9 text-sm w-full" />
          </div>

          <div class="flex items-center gap-3 pt-5">
            <label class="relative inline-flex items-center cursor-pointer">
              <input v-model="localForm.is_active" type="checkbox" class="sr-only peer" />
              <div
                class="w-9 h-5 bg-muted peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary"
              ></div>
              <span class="ml-2.5 text-xs font-semibold text-foreground">
                {{ localForm.is_active ? 'Tayangkan di Portal' : 'Simpan sebagai Draft' }}
              </span>
            </label>
          </div>
        </div>
      </div>

      <!-- Submit Action -->
      <div class="pt-3 border-t border-border flex justify-end">
        <Button
          type="button"
          class="h-10 px-6 font-semibold text-xs tracking-wider uppercase gap-2"
          @click="handleSubmit"
        >
          <span>Simpan Pengumuman</span>
        </Button>
      </div>
    </CardContent>
  </Card>
</template>
