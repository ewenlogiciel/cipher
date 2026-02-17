<script setup lang="ts">
import { Activity } from 'lucide-vue-next'

const api = useApi()

interface LogItem {
  id: number
  action: string
  performedBy: string | null
  vaultName: string | null
  secretName: string | null
  ipAddress: string | null
  userAgent: string | null
  createdAt: string
}

const logs = ref<LogItem[]>([])
const loading = ref(true)

const { set: setBreadcrumb } = useBreadcrumb()
setBreadcrumb([{ label: 'Activité' }])

const actionLabels: Record<string, string> = {
  'vault.created': 'a créé le coffre',
  'secret.created': 'a ajouté le secret',
  'secret.accessed': 'a consulté le secret',
  'member.added': 'a ajouté un membre',
}

function formatAction(action: string): string {
  return actionLabels[action] || action
}

function formatLogDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchLogs() {
  loading.value = true
  try {
    logs.value = await api.get<LogItem[]>('/api/vaults/logs/all')
  } catch {
    logs.value = []
  } finally {
    loading.value = false
  }
}

onMounted(fetchLogs)
</script>

<template>
  <div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Activité</h1>
      <p class="mt-1 text-sm text-muted">Journal d'audit de toutes les actions sur vos coffres.</p>
    </div>

    <div v-if="loading" class="mt-16 flex justify-center">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
    </div>

    <div v-else-if="logs.length === 0" class="mt-16 flex flex-col items-center text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-50 border border-white/[0.06]">
        <Activity class="h-6 w-6 text-muted" />
      </div>
      <h3 class="mt-4 text-sm font-medium text-zinc-300">Aucune activité</h3>
      <p class="mt-1 text-sm text-muted">Les actions sur vos coffres apparaîtront ici.</p>
    </div>

    <div v-else class="mt-6 space-y-2">
      <div
        v-for="log in logs"
        :key="log.id"
        class="rounded-xl border border-white/[0.06] bg-surface-50 px-4 py-3"
      >
        <div class="flex items-center justify-between">
          <div class="flex-1 min-w-0">
            <p class="text-sm text-zinc-200">
              <span class="font-medium text-zinc-100">{{ log.performedBy }}</span>
              <span class="text-muted"> — </span>
              <span>{{ formatAction(log.action) }}</span>
              <span v-if="log.secretName" class="text-accent"> {{ log.secretName }}</span>
              <span v-if="log.vaultName" class="text-muted"> dans </span>
              <span v-if="log.vaultName" class="font-medium text-zinc-300">{{ log.vaultName }}</span>
            </p>
          </div>
          <span class="ml-4 shrink-0 text-xs text-muted">{{ formatLogDate(log.createdAt) }}</span>
        </div>
        <div class="mt-1.5 flex items-center gap-3 text-[11px] text-muted">
          <span v-if="log.ipAddress" class="font-mono">{{ log.ipAddress }}</span>
          <span v-if="log.userAgent" class="break-all">{{ log.userAgent }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
