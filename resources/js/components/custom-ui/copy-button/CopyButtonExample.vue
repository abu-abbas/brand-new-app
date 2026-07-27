<script setup lang="ts">
import { ref } from 'vue';
import { Copy, Sparkles, Code, Terminal, Key } from '@lucide/vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import CopyButton from './CopyButton.vue';

const sampleApiKey = ref('sk-proj-982347892374982347923847');
const sampleCommand = ref('npm run dev -- --host');
const sampleCodeSnippet = ref('import { CopyButton } from "@/components/custom-ui/copy-button";');

const lastCopiedText = ref<string>('');

function onCopySuccess(payload: { text: string; success: boolean }) {
  if (payload.success) {
    lastCopiedText.value = payload.text;
  }
}
</script>

<template>
  <div class="flex flex-col gap-8 p-6">
    <!-- Header Title -->
    <div class="flex items-start gap-3.5">
      <div
        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-2xs mt-0.5"
      >
        <Copy class="size-5" />
      </div>
      <div>
        <h1 class="text-xl font-bold text-foreground">CopyButton Component</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Komponen kustom Vue 3 untuk menyalin teks ke clipboard dengan feedback visual bawaan,
          custom label, dan event callback.
        </p>
      </div>
    </div>

    <!-- Live Interactive Sandbox -->
    <Card class="border-primary/20 bg-primary/5 shadow-xs">
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Sparkles class="size-5 text-primary" />
          Live Interactive Sandbox
        </CardTitle>
        <CardDescription>
          Ketik teks di bawah ini dan klik tombol untuk menyalin secara otomatis.
        </CardDescription>
      </CardHeader>
      <CardContent class="flex flex-col gap-4">
        <div class="flex items-center gap-2">
          <Input v-model="sampleApiKey" class="bg-background font-mono text-xs" />
          <CopyButton :text="sampleApiKey" variant="default" @copy="onCopySuccess" />
        </div>
        <p v-if="lastCopiedText" class="text-xs text-emerald-600 font-medium">
          Teks terakhir yang disalin:
          <code class="font-mono bg-emerald-50 px-1 py-0.5 rounded">{{ lastCopiedText }}</code>
        </p>
      </CardContent>
    </Card>

    <!-- Card 1: Penggunaan Variasi -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Code class="size-5 text-primary" />
          1. Variasi Ukuran & Icon Only
        </CardTitle>
        <CardDescription>
          Dapat digunakan dengan label atau icon-only tanpa label.
        </CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border p-4 flex flex-col gap-2">
          <span class="text-xs font-bold uppercase text-muted-foreground">Default Label</span>
          <CopyButton :text="sampleCodeSnippet" variant="outline" />
        </div>
        <div class="rounded-lg border p-4 flex flex-col gap-2">
          <span class="text-xs font-bold uppercase text-muted-foreground">Custom Label</span>
          <CopyButton
            :text="sampleCodeSnippet"
            label="Salin Kode"
            copiedLabel="Kode Tersalin!"
            variant="secondary"
          />
        </div>
        <div class="rounded-lg border p-4 flex flex-col gap-2">
          <span class="text-xs font-bold uppercase text-muted-foreground">Icon Only</span>
          <CopyButton :text="sampleCodeSnippet" :show-label="false" variant="ghost" size="icon" />
        </div>
      </CardContent>
    </Card>

    <!-- Card 2: Penggunaan Terminal Command -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Terminal class="size-5 text-primary" />
          2. Integrasi Terminal Command
        </CardTitle>
        <CardDescription>
          Menampilkan baris perintah terminal dengan tombol salin di sudut kanan.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <div
          class="rounded-xl border border-border bg-muted/60 p-4 flex items-center justify-between font-mono text-xs"
        >
          <span>$ {{ sampleCommand }}</span>
          <CopyButton :text="sampleCommand" size="sm" variant="ghost" />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
