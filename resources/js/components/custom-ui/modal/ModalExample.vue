<script setup lang="ts">
import type { ModalSize } from './Modal.vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Modal } from './index';

// State Modal 1: Simple Modal
const isSimpleOpen = ref(false);

// State Modal 2: Form Modal & Loading State
const isFormOpen = ref(false);
const isSubmitting = ref(false);
const formData = ref({
  name: '',
  email: '',
  role: 'Developer',
});
const formErrorMessage = ref('');
const successNotification = ref('');

// State Modal 3: Custom Slot Modal
const isCustomOpen = ref(false);

// State Modal 4: Nested Modal
const isParentOpen = ref(false);
const isChildOpen = ref(false);

// State Modal 5: Preset Sizes
const isSizeOpen = ref(false);
const currentSize = ref<ModalSize>('md');

const openSizeModal = (size: ModalSize) => {
  currentSize.value = size;
  isSizeOpen.value = true;
};

// Form submit handler
const handleFormSubmit = () => {
  if (!formData.value.name.trim()) {
    formErrorMessage.value = 'Nama tidak boleh kosong!';
    return;
  }
  formErrorMessage.value = '';
  isSubmitting.value = true;

  // Simulasi API request 1.5 detik
  window.setTimeout(() => {
    isSubmitting.value = false;
    isFormOpen.value = false;
    successNotification.value = `Data pengguna "${formData.value.name}" (${formData.value.email}) telah berhasil disimpan.`;
    formData.value = { name: '', email: '', role: 'Developer' };
  }, 1500);
};
</script>

<template>
  <div class="mx-auto max-w-5xl space-y-8 p-8">
    <!-- Header Page -->
    <div class="border-b border-border pb-5">
      <h1 class="text-2xl font-bold tracking-tight text-foreground">
        Reusable Modal Component Showcase
      </h1>
      <p class="mt-1 text-sm text-muted-foreground">
        Komponen modal yang fleksibel, berbasis shadcn-vue dengan dukungan Hybrid API (Props +
        Slots), Preset Size, Form Integration, dan State Loading bawaan.
      </p>
    </div>

    <!-- Alert Notifikasi Berhasil -->
    <div
      v-if="successNotification"
      class="flex items-center justify-between rounded-lg bg-emerald-500/10 p-4 text-sm font-medium text-emerald-600 dark:text-emerald-400"
    >
      <span>{{ successNotification }}</span>
      <Button variant="ghost" size="sm" @click="successNotification = ''">Tutup</Button>
    </div>

    <!-- Grid Demo Cards -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- 1. Simple Modal -->
      <div class="space-y-4 rounded-xl border border-border bg-card p-6">
        <h2 class="text-lg font-semibold">1. Prop-driven Simple Modal</h2>
        <p class="text-sm text-muted-foreground">
          Penggunaan paling simpel tanpa ribet, cukup passing prop <code>title</code>,
          <code>description</code>, dan <code>v-model:open</code>.
        </p>
        <Button @click="isSimpleOpen = true">Buka Simple Modal</Button>

        <Modal
          v-model:open="isSimpleOpen"
          title="Konfirmasi Tindakan"
          description="Apakah Anda yakin ingin melanjutkan proses ini?"
          @confirm="isSimpleOpen = false"
        >
          <p class="text-muted-foreground">
            Ini adalah isi konten body dari modal. Semua styling disesuaikan secara otomatis dengan
            tema shadcn-vue.
          </p>
        </Modal>
      </div>

      <!-- 2. Form Wrapper & Loading State -->
      <div class="space-y-4 rounded-xl border border-border bg-card p-6">
        <h2 class="text-lg font-semibold">2. Form Integration & Loading State</h2>
        <p class="text-sm text-muted-foreground">
          Otomatis membungkus body dan tombol dengan tag <code>&lt;form&gt;</code>, serta menangani
          submit saat ditekan Enter & spinner loading saat submit.
        </p>
        <Button variant="secondary" @click="isFormOpen = true">Buka Form Modal</Button>

        <Modal
          v-model:open="isFormOpen"
          title="Tambah Pengguna Baru"
          description="Masukkan informasi pengguna baru ke dalam sistem."
          as-form
          :loading="isSubmitting"
          confirm-text="Simpan Data"
          confirm-variant="default"
          @confirm="handleFormSubmit"
        >
          <div class="space-y-4">
            <div
              v-if="formErrorMessage"
              class="rounded-lg bg-destructive/10 p-3 text-xs font-medium text-destructive"
            >
              {{ formErrorMessage }}
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-medium">Nama Lengkap</label>
              <Input
                v-model="formData.name"
                placeholder="Contoh: Budi Santoso"
                :disabled="isSubmitting"
              />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-medium">Email</label>
              <Input
                v-model="formData.email"
                type="email"
                placeholder="budi@example.com"
                :disabled="isSubmitting"
              />
            </div>
          </div>
        </Modal>
      </div>

      <!-- 3. Custom Slot Modal -->
      <div class="space-y-4 rounded-xl border border-border bg-card p-6">
        <h2 class="text-lg font-semibold">3. Custom Slots (Header, Footer, Actions)</h2>
        <p class="text-sm text-muted-foreground">
          Mendukung kustomisasi penuh melalui Slot <code>#header</code>, <code>#footer</code>, atau
          <code>#actions</code> bila membutuhkan layout khusus.
        </p>
        <Button variant="outline" @click="isCustomOpen = true">Buka Custom Slot Modal</Button>

        <Modal v-model:open="isCustomOpen" size="lg">
          <template #header>
            <div class="flex items-center gap-3">
              <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-bold text-primary"
              >
                UI
              </div>
              <div>
                <h3 class="text-lg font-semibold">Custom Header Slot</h3>
                <p class="text-xs text-muted-foreground">
                  Desain header yang sepenuhnya disesuaikan
                </p>
              </div>
            </div>
          </template>

          <div
            class="rounded-lg border border-dashed bg-muted/20 p-4 text-center text-sm text-muted-foreground"
          >
            Anda dapat memasukkan komponen apapun ke dalam slot body default.
          </div>

          <template #actions>
            <Button variant="ghost" @click="isCustomOpen = false">Batal</Button>
            <Button variant="destructive" @click="isCustomOpen = false">Hapus Permanent</Button>
            <Button variant="default" @click="isCustomOpen = false">Arsipkan</Button>
          </template>
        </Modal>
      </div>

      <!-- 4. Nested Modal -->
      <div class="space-y-4 rounded-xl border border-border bg-card p-6">
        <h2 class="text-lg font-semibold">4. Nested Modal Support</h2>
        <p class="text-sm text-muted-foreground">
          Dukungan aman untuk membuka modal sekunder di atas modal utama tanpa kendala z-index atau
          focus lock.
        </p>
        <Button variant="outline" @click="isParentOpen = true">Buka Parent Modal</Button>

        <!-- Parent Modal -->
        <Modal
          v-model:open="isParentOpen"
          title="Modal Utama (Parent)"
          description="Ini adalah modal tingkat pertama."
          size="lg"
          @confirm="isParentOpen = false"
        >
          <div class="space-y-4">
            <p>
              Klik tombol di bawah untuk membuka modal sekunder (Child Modal) di atas modal ini.
            </p>
            <Button variant="secondary" @click="isChildOpen = true">Buka Child Modal</Button>
          </div>

          <!-- Child Modal -->
          <Modal
            v-model:open="isChildOpen"
            title="Modal Sekunder (Child)"
            description="Ini adalah modal bertingkat di atas Parent Modal."
            size="sm"
            confirm-text="Mengerti"
            @confirm="isChildOpen = false"
          >
            <p class="text-sm text-muted-foreground">
              Modal bertingkat tetap aman digunakan tanpa merusak backdrop overlay atau scroll-lock.
            </p>
          </Modal>
        </Modal>
      </div>
    </div>

    <!-- 5. Preset Sizes Section -->
    <div class="space-y-4 rounded-xl border border-border bg-card p-6">
      <h2 class="text-lg font-semibold">5. Preset Ukuran Modal (prop `size`)</h2>
      <p class="text-sm text-muted-foreground">
        Tersedia berbagai preset ukuran: <code>sm</code>, <code>md</code>, <code>lg</code>,
        <code>xl</code>, <code>2xl</code>, <code>3xl</code>, <code>4xl</code>, <code>5xl</code>, dan
        <code>full</code>.
      </p>

      <div class="flex flex-wrap gap-2">
        <Button
          v-for="s in ['sm', 'md', 'lg', 'xl', '2xl', '3xl', '4xl', '5xl', 'full'] as ModalSize[]"
          :key="s"
          variant="outline"
          size="sm"
          @click="openSizeModal(s)"
        >
          Size: {{ s }}
        </Button>
      </div>

      <!-- Size Preview Modal -->
      <Modal
        v-model:open="isSizeOpen"
        :title="`Preview Modal Ukuran: ${currentSize}`"
        :description="`Prop size='${currentSize}' diterapkan pada komponen.`"
        :size="currentSize"
        @confirm="isSizeOpen = false"
      >
        <div class="rounded-lg border border-dashed bg-muted/20 py-8 text-center">
          <p class="font-mono text-sm">Ukuran terpilih: {{ currentSize }}</p>
        </div>
      </Modal>
    </div>
  </div>
</template>
