<script setup lang="ts">
import { ref } from 'vue';
import { FileText, Sparkles, Code, Link as LinkIcon } from '@lucide/vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { MarkdownText } from './index';

const interactiveInput = ref(
  'Halo! Komponen **MarkdownText** ini mendukung *italic*, **bold**, `inline code`, dan link [Laravel Docs](https://laravel.com).',
);
</script>

<template>
  <div class="flex flex-col gap-8 p-6">
    <!-- Header Title -->
    <div class="flex items-start gap-3.5">
      <div
        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary shadow-2xs mt-0.5"
      >
        <FileText class="size-5" />
      </div>
      <div>
        <h1 class="text-xl font-bold text-foreground">MarkdownText Component</h1>
        <p class="mt-1 text-sm text-muted-foreground">
          Komponen kustom Vue 3 untuk mem-parse dan merender formatting markdown (*italic*,
          **bold**, `code`, link) menjadi Virtual DOM Nodes (VNodes) native tanpa menggunakan
          `v-html` murni.
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
          Ketik teks bermarkdown di bawah ini untuk melihat hasil rendering VNode secara real-time.
        </CardDescription>
      </CardHeader>
      <CardContent class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <label class="text-sm font-medium" for="markdown-input">Teks Input (Markdown):</label>
          <Input
            id="markdown-input"
            v-model="interactiveInput"
            placeholder="Ketik teks bermarkdown..."
            class="bg-background"
          />
        </div>
        <div class="rounded-lg border bg-background p-4 shadow-2xs">
          <span
            class="text-xs font-semibold uppercase tracking-wider text-muted-foreground block mb-2"
          >
            Hasil Output (MarkdownText):
          </span>
          <p class="text-sm text-foreground">
            <MarkdownText :content="interactiveInput" />
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Card 1: Contoh Format Teks -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <Code class="size-5 text-primary" />
          1. Format Teks Dasar
        </CardTitle>
        <CardDescription>
          Mendukung penulisan miring (*italic*), tebal (**bold**), dan kode baris (`inline code`).
        </CardDescription>
      </CardHeader>
      <CardContent class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border p-4">
          <span class="text-xs font-bold uppercase text-muted-foreground block mb-1">Italic</span>
          <MarkdownText content="Teks ini menggunakan *format miring* untuk penekanan." />
        </div>
        <div class="rounded-lg border p-4">
          <span class="text-xs font-bold uppercase text-muted-foreground block mb-1">Bold</span>
          <MarkdownText content="Teks ini menggunakan **format tebal** untuk istilah penting." />
        </div>
        <div class="rounded-lg border p-4">
          <span class="text-xs font-bold uppercase text-muted-foreground block mb-1"
            >Inline Code</span
          >
          <MarkdownText content="Properti `fetcher` dan `queryKey` bersifat reaktif." />
        </div>
      </CardContent>
    </Card>

    <!-- Card 2: Link Markdown -->
    <Card>
      <CardHeader>
        <CardTitle class="flex items-center gap-2">
          <LinkIcon class="size-5 text-primary" />
          2. Markdown Links
        </CardTitle>
        <CardDescription>
          Mendukung sintaks link `[label](url)` yang otomatis membuka tab baru dengan aman
          (`target="_blank" rel="noopener noreferrer"`).
        </CardDescription>
      </CardHeader>
      <CardContent class="flex flex-col gap-3">
        <div class="rounded-lg border p-4">
          <MarkdownText
            content="Pelajari lebih lanjut mengenai arsitektur UI di [Dokumentasi Vue 3](https://vuejs.org) atau baca mengenai [Tailwind CSS](https://tailwindcss.com)."
          />
        </div>
      </CardContent>
    </Card>
  </div>
</template>
