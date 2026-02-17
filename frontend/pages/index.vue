<script setup lang="ts">
import { Vault as VaultIcon, KeyRound, Eye, Lock, Clock, Users, ArrowRight, Activity } from 'lucide-vue-next'

const api = useApi()

interface DashboardData {
  stats: {
    vaultsCount: number
    secretsCount: number
    accessCount: number
  }
  recentVaults: {
    id: number
    name: string
    description: string | null
    secretsCount: number
    membersCount: number
    lastActivityAt: string | null
  }[]
  recentLogs: {
    id: number
    action: string
    performedBy: string | null
    vaultName: string | null
    secretName: string | null
    createdAt: string
  }[]
}

const data = ref<DashboardData | null>(null)
const loading = ref(true)

const { set: setBreadcrumb } = useBreadcrumb()
setBreadcrumb([{ label: 'Accueil' }])

const actionLabels: Record<string, string> = {
  'vault.created': 'a créé le coffre',
  'secret.created': 'a ajouté le secret',
  'secret.accessed': 'a consulté le secret',
  'member.added': 'a ajouté un membre',
}

function formatAction(action: string): string {
  return actionLabels[action] || action
}

function formatRelativeDate(iso: string | null): string {
  if (!iso) return 'Aucune'
  const diff = Date.now() - new Date(iso).getTime()
  const minutes = Math.floor(diff / 60000)
  if (minutes < 1) return "À l'instant"
  if (minutes < 60) return `Il y a ${minutes} min`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `Il y a ${hours} h`
  const days = Math.floor(hours / 24)
  if (days < 30) return `Il y a ${days} j`
  const months = Math.floor(days / 30)
  return `Il y a ${months} mois`
}

function formatLogDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

async function fetchDashboard() {
  loading.value = true
  try {
    data.value = await api.get<DashboardData>('/api/dashboard')
  } catch {
    data.value = null
  } finally {
    loading.value = false
  }
}

onMounted(fetchDashboard)
</script>

<template>
  <div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Tableau de bord</h1>
      <p class="mt-1 text-sm text-muted">Vue d'ensemble de vos coffres et de votre activité.</p>
    </div>

    <div v-if="loading" class="mt-16 flex justify-center">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
    </div>

    <template v-else-if="data">
      <!-- Stat cards -->
      <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-white/[0.06] bg-surface-50 p-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <VaultIcon class="h-5 w-5 text-accent" />
            </div>
            <div>
              <p class="text-2xl font-semibold text-zinc-100">{{ data.stats.vaultsCount }}</p>
              <p class="text-xs text-muted">Coffres</p>
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-surface-50 p-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-500/10">
              <KeyRound class="h-5 w-5 text-emerald-400" />
            </div>
            <div>
              <p class="text-2xl font-semibold text-zinc-100">{{ data.stats.secretsCount }}</p>
              <p class="text-xs text-muted">Secrets</p>
            </div>
          </div>
        </div>
        <div class="rounded-xl border border-white/[0.06] bg-surface-50 p-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-500/10">
              <Eye class="h-5 w-5 text-violet-400" />
            </div>
            <div>
              <p class="text-2xl font-semibold text-zinc-100">{{ data.stats.accessCount }}</p>
              <p class="text-xs text-muted">Consultations</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent vaults -->
      <div class="mt-8">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-zinc-100">Coffres récents</h2>
          <NuxtLink to="/vaults" class="flex items-center gap-1 text-sm text-accent transition-colors hover:text-accent-dim">
            Voir tout
            <ArrowRight class="h-3.5 w-3.5" />
          </NuxtLink>
        </div>

        <div v-if="data.recentVaults.length === 0" class="mt-4 rounded-xl border border-white/[0.06] bg-surface-50 p-8 text-center">
          <p class="text-sm text-muted">Aucun coffre pour le moment.</p>
          <NuxtLink to="/vaults" class="mt-2 inline-block text-sm text-accent hover:text-accent-dim">Créer un coffre</NuxtLink>
        </div>

        <div v-else class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
          <NuxtLink
            v-for="vault in data.recentVaults"
            :key="vault.id"
            :to="`/vaults/${vault.id}`"
            class="group rounded-xl border border-white/[0.06] bg-surface-50 p-5 transition-all duration-200 hover:border-white/[0.1] hover:bg-surface-100 cursor-pointer block"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
                <Lock class="h-5 w-5 text-accent" />
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="text-sm font-semibold text-zinc-100">{{ vault.name }}</h3>
                <p v-if="vault.description" class="truncate text-xs text-muted">{{ vault.description }}</p>
              </div>
            </div>
            <div class="mt-4 flex items-center gap-4 text-xs text-muted">
              <div class="flex items-center gap-1.5">
                <KeyRound class="h-3 w-3" />
                <span>{{ vault.secretsCount }} {{ vault.secretsCount <= 1 ? 'secret' : 'secrets' }}</span>
              </div>
              <span class="text-white/10">|</span>
              <div class="flex items-center gap-1.5">
                <Users class="h-3 w-3" />
                <span>{{ vault.membersCount }}</span>
              </div>
              <div class="flex items-center gap-1.5 ml-auto">
                <Clock class="h-3 w-3" />
                <span>{{ formatRelativeDate(vault.lastActivityAt) }}</span>
              </div>
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="mt-8">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold text-zinc-100">Activité récente</h2>
          <NuxtLink to="/activity" class="flex items-center gap-1 text-sm text-accent transition-colors hover:text-accent-dim">
            Voir tout
            <ArrowRight class="h-3.5 w-3.5" />
          </NuxtLink>
        </div>

        <div v-if="data.recentLogs.length === 0" class="mt-4 rounded-xl border border-white/[0.06] bg-surface-50 p-8 text-center">
          <p class="text-sm text-muted">Aucune activité récente.</p>
        </div>

        <div v-else class="mt-4 space-y-2">
          <div
            v-for="log in data.recentLogs"
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
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
