<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card';
import ThemeToggle from '@/components/ThemeToggle.vue';
import ThemeSelector from '@/components/ThemeSelector.vue';
import { Hand } from '@lucide/vue';
import { ref } from 'vue';

const count = ref(0);
const increment = () => count.value++;
</script>

<template>
  <div class="min-h-screen bg-background flex flex-col items-center justify-center p-6 gap-6">
    <div class="absolute top-6 right-6 flex items-center gap-3">
      <ThemeSelector />
      <ThemeToggle />
    </div>

    <Card class="w-full max-w-md shadow-lg border border-border">
      <CardHeader>
        <CardTitle class="text-2xl font-bold tracking-tight flex items-center gap-2">
          Halo, shadcn-vue!
          <Hand class="size-6 text-yellow-500 animate-bounce" />
        </CardTitle>
        <CardDescription>
          Integrasi Vue 3, TypeScript, Tailwind CSS v4, dan shadcn-vue di Laravel berhasil.
        </CardDescription>
      </CardHeader>

      <CardContent class="flex flex-col items-center justify-center py-6 gap-4">
        <p class="text-sm text-muted-foreground text-center">
          Klik tombol di bawah ini untuk menguji state reaktif Vue.
        </p>
        <div class="text-4xl font-extrabold tracking-tight text-primary">
          {{ count }}
        </div>
      </CardContent>

      <CardFooter class="flex justify-end gap-2">
        <Button variant="outline" @click="count = 0">Reset</Button>
        <Button @click="increment">Klik Saya</Button>
      </CardFooter>
    </Card>
  </div>
</template>
