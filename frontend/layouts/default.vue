<script setup lang="ts">
const { collapsed } = useSidebar()
</script>

<template>
  <div class="min-h-screen bg-surface">
    <Sidebar />

    <main
      class="transition-all duration-300 min-h-screen"
      :class="collapsed ? 'pl-[68px]' : 'pl-[240px]'"
    >
      <div class="mx-auto max-w-6xl px-8 py-8">
        <slot />
      </div>
    </main>
  </div>
</template>
