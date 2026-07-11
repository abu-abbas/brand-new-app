<script setup lang="ts">
import { CardDescription, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
</script>

<template>
  <div class="flex flex-col justify-between flex-1">
    <CardHeader class="pb-2">
      <CardTitle class="text-base font-bold text-foreground">Traffic channels</CardTitle>
      <CardDescription class="text-muted-foreground leading-normal">
        Monthly desktop and mobile traffic for the last six months—compare volume and mix across
        platform...
      </CardDescription>
    </CardHeader>

    <CardContent class="pb-6 flex flex-col justify-between flex-1">
      <!-- SVG Bar Chart -->
      <div class="w-full flex flex-col justify-end mt-4">
        <svg viewBox="0 0 400 175" class="w-full h-auto overflow-visible select-none">
          <!-- Grid Bars for 6 Months -->
          <!-- Jan -->
          <rect
            x="15"
            y="80"
            width="18"
            height="80"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="36" y="115" width="18" height="45" rx="3" fill="var(--primary)" />

          <!-- Feb -->
          <rect
            x="75"
            y="20"
            width="18"
            height="140"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="96" y="70" width="18" height="90" rx="3" fill="var(--primary)" />

          <!-- Mar -->
          <rect
            x="135"
            y="50"
            width="18"
            height="110"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="156" y="100" width="18" height="60" rx="3" fill="var(--primary)" />

          <!-- Apr -->
          <rect
            x="195"
            y="125"
            width="18"
            height="35"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="216" y="70" width="18" height="90" rx="3" fill="var(--primary)" />

          <!-- May -->
          <rect
            x="255"
            y="60"
            width="18"
            height="100"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="276" y="95" width="18" height="65" rx="3" fill="var(--primary)" />

          <!-- Jun -->
          <rect
            x="315"
            y="55"
            width="18"
            height="105"
            rx="3"
            fill="var(--primary)"
            fill-opacity="0.4"
          />
          <rect x="336" y="88" width="18" height="72" rx="3" fill="var(--primary)" />

          <!-- X-Axis Labels -->
          <text
            x="35"
            y="172"
            class="fill-muted-foreground text-[8px] font-medium"
            text-anchor="middle"
          >
            Jan
          </text>
          <text
            x="335"
            y="172"
            class="fill-muted-foreground text-[8px] font-medium"
            text-anchor="middle"
          >
            Feb
          </text>
        </svg>
      </div>

      <!-- Legend -->
      <div
        class="flex items-center justify-center gap-4 mt-4 text-[11px] font-medium text-foreground"
      >
        <div class="flex items-center gap-1.5">
          <span
            class="size-2.5 rounded-xs"
            style="background-color: var(--primary); opacity: 0.4"
          />
          <span>Desktop</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-xs" style="background-color: var(--primary)" />
          <span>Mobile</span>
        </div>
      </div>

      <!-- Bottom Statistics Section -->
      <div class="grid grid-cols-3 border-t border-border/60 pt-4 mt-6 text-center">
        <div class="flex flex-col items-center">
          <span class="text-[9px] font-semibold text-muted-foreground uppercase tracking-wider">
            Desktop
          </span>
          <span class="text-base font-bold text-foreground mt-0.5">1,224</span>
        </div>
        <div class="flex flex-col items-center border-x border-border/60">
          <span class="text-[9px] font-semibold text-muted-foreground uppercase tracking-wider">
            Mobile
          </span>
          <span class="text-base font-bold text-foreground mt-0.5">860</span>
        </div>
        <div class="flex flex-col items-center">
          <span class="text-[9px] font-semibold text-muted-foreground uppercase tracking-wider">
            Mix Delta
          </span>
          <span class="text-base font-bold text-foreground mt-0.5">+42%</span>
        </div>
      </div>
    </CardContent>
  </div>
</template>
