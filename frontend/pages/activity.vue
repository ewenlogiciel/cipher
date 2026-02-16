<script setup lang="ts">
import { Activity } from 'lucide-vue-next'

// TODO: fetch from GET /api/audit-logs once the API resource exists
const logs = ref<any[]>([])
const loading = ref(false)
</script>

<template>
  <div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Activité</h1>
      <p class="mt-1 text-sm text-muted">Journal d'audit de toutes les actions sur vos coffres.</p>
    </div>

    <div v-if="!loading && logs.length === 0" class="mt-16 flex flex-col items-center text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-50 border border-white/[0.06]">
        <Activity class="h-6 w-6 text-muted" />
      </div>
      <h3 class="mt-4 text-sm font-medium text-zinc-300">Aucune activité</h3>
      <p class="mt-1 text-sm text-muted">Les actions sur vos coffres apparaîtront ici.</p>
    </div>

    <div v-else class="mt-6 space-y-1">
      <div
        v-for="log in logs"
        :key="log.id"
        class="flex items-center gap-4 rounded-lg border border-transparent px-4 py-3 transition-colors hover:border-white/[0.04] hover:bg-surface-50"
      >
        <div class="flex-1 min-w-0">
          <p class="text-sm text-zinc-200">
            <span class="font-medium">{{ log.action }}</span>
          </p>
        </div>
        <span class="text-xs text-muted font-mono">{{ log.ipAddress }}</span>
      </div>
    </div>
  </div>
</template>
