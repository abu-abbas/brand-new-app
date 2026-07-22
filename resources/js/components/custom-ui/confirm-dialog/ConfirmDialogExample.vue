<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { useConfirmDialog } from '@/composables/useConfirmDialog';

const confirmDialog = useConfirmDialog();
const confirmResult = ref('Belum ada aksi.');

async function showBasicConfirm(): Promise<void> {
  const confirmed = await confirmDialog({
    title: 'Simpan perubahan?',
    description: 'Perubahan akan langsung diterapkan.',
    confirmLabel: 'Simpan',
  });

  confirmResult.value = confirmed ? 'Perubahan disimpan.' : 'Penyimpanan dibatalkan.';
}

async function showDeleteConfirm(): Promise<void> {
  const confirmed = await confirmDialog({
    title: 'Hapus data ini?',
    description: 'Tindakan ini tidak dapat dibatalkan.',
    confirmLabel: 'Hapus',
    loadingLabel: 'Menghapus...',
    confirmVariant: 'destructive',
    reverseActions: true,
    onConfirm: () => new Promise((resolve) => window.setTimeout(resolve, 2000)),
    successTitle: 'Hapus berhasil',
    successDescription: 'Data berhasil dihapus.',
    successAutoCloseSeconds: 3,
  });

  if (confirmed) confirmResult.value = 'Data berhasil dihapus.';
}

async function showErrorConfirm(): Promise<void> {
  try {
    await confirmDialog({
      title: 'Jalankan proses?',
      description: 'Contoh ini akan mensimulasikan request yang gagal.',
      confirmLabel: 'Jalankan',
      loadingLabel: 'Memproses...',
      onConfirm: async () => {
        await new Promise((resolve) => window.setTimeout(resolve, 1000));
        throw new Error('Simulasi request gagal.');
      },
      errorDescription: (error) =>
        error instanceof Error ? error.message : 'Terjadi kesalahan yang tidak diketahui.',
    });
  } catch {
    confirmResult.value = 'Proses gagal dan sudah ditangani.';
  }
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Renderless Confirm Dialog</CardTitle>
      <CardDescription>{{ confirmResult }}</CardDescription>
    </CardHeader>
    <CardContent>
      Dipanggil langsung dari script dengan <code>await confirmDialog(options)</code>.
    </CardContent>
    <CardFooter class="flex flex-wrap gap-2">
      <Button variant="outline" @click="showBasicConfirm">Konfirmasi biasa</Button>
      <Button variant="destructive" @click="showDeleteConfirm">Hapus async</Button>
      <Button variant="secondary" @click="showErrorConfirm">Simulasi error</Button>
    </CardFooter>
  </Card>
</template>
