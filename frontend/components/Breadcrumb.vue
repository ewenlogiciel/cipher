<script setup lang="ts">
import { ChevronRight } from 'lucide-vue-next'

const { items } = useBreadcrumb()
</script>

<template>
  <nav v-if="items.length > 0" class="flex items-center gap-1 text-sm">
    <template v-for="(crumb, i) in items" :key="i">
      <ChevronRight v-if="i > 0" class="h-3.5 w-3.5 text-white/20 shrink-0" />
      <NuxtLink
        v-if="crumb.to && i < items.length - 1"
        :to="crumb.to"
        class="text-muted transition-colors hover:text-zinc-300"
      >
        {{ crumb.label }}
      </NuxtLink>
      <span v-else class="text-zinc-300 font-medium">{{ crumb.label }}</span>
    </template>
  </nav>
</template>
