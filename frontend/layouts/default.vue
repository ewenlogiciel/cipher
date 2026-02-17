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
      <div class="mx-auto max-w-6xl py-8">
        <ClientOnly>
          <Breadcrumb class="mb-4" />
        </ClientOnly>
        <div class="rounded-2xl border border-white/[0.06] bg-surface-50 p-6 sm:p-8">
          <slot />
        </div>
      </div>
    </main>
  </div>
</template>
