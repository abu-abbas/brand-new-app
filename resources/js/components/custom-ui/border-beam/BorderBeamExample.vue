<script setup lang="ts">
import { ref } from 'vue';
import { Sparkles, Palette } from '@lucide/vue';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { BorderBeam } from '@/components/custom-ui/border-beam';

const isAnimating = ref(true);
</script>

<template>
  <div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="flex items-center gap-2 text-2xl font-bold tracking-tight text-foreground">
          <Sparkles class="size-6 text-primary" />
          Border Beam Component
        </h2>
        <p class="text-muted-foreground">
          Animasi berkas cahaya melingkar yang tetap presisi mengikuti kelengkapan sudut (rounded
          corner) Card.
        </p>
      </div>
    </div>

    <!-- Grid Showcase Examples -->
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
      <!-- Example 1: Primary Shades Gradient (primary-100 ke primary-900) -->
      <Card class="relative overflow-hidden rounded-2xl border-0 py-5.5">
        <BorderBeam
          v-if="isAnimating"
          :size="220"
          :duration="5"
          :border-width="2"
          color-from="var(--color-primary-100)"
          color-to="var(--color-primary-500)"
        />
        <CardHeader>
          <div class="flex items-center justify-between">
            <Badge variant="secondary">rounded-2xl</Badge>
            <Palette class="size-4 text-primary" />
          </div>
          <CardTitle class="mt-2 text-lg">Primary Shades Gradient</CardTitle>
          <CardDescription class="font-mono">
            Gradasi warna dari
            <code class="text-primary text-2xs">var(--color-primary-100)</code> ke
            <code class="text-primary text-2xs">var(--color-primary-500)</code>.
          </CardDescription>
        </CardHeader>
        <CardContent class="text-sm text-muted-foreground">
          Memberikan kontras gradasi dari warna lembut dari primary muda ke pekat yang elegan.
        </CardContent>
        <CardFooter>
          <Button variant="outline" size="sm" class="w-full">Explore Primary Beam</Button>
        </CardFooter>
      </Card>
    </div>
  </div>
</template>
