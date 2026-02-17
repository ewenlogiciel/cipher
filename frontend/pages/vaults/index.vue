<script setup lang="ts">
import { Plus, Search, Vault as VaultIcon, Lock, X, Users, KeyRound, Clock } from 'lucide-vue-next'

const api = useApi()

interface VaultItem {
  id: number
  name: string
  description: string | null
  role: string
  secretsCount: number
  membersCount: number
  lastActivityAt: string | null
}

const vaults = ref<VaultItem[]>([])
const searchQuery = ref('')
const loading = ref(true)

const { set: setBreadcrumb } = useBreadcrumb()
setBreadcrumb([{ label: 'Coffres' }])

const showModal = ref(false)
const newName = ref('')
const newDescription = ref('')
const creating = ref(false)
const error = ref('')

async function fetchVaults() {
  loading.value = true
  try {
    vaults.value = await api.get<VaultItem[]>('/api/vaults')
  } catch {
    vaults.value = []
  } finally {
    loading.value = false
  }
}

const filteredVaults = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (!q) return vaults.value
  return vaults.value.filter(
    (v) => v.name.toLowerCase().includes(q) || v.description?.toLowerCase().includes(q),
  )
})

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

async function createVault() {
  error.value = ''
  creating.value = true

  try {
    const vault = await api.post<VaultItem>('/api/vaults', {
      name: newName.value,
      description: newDescription.value || null,
    })
    vaults.value.push(vault)
    showModal.value = false
    newName.value = ''
    newDescription.value = ''
  } catch (e: any) {
    error.value = e.error || 'Impossible de créer le coffre.'
  } finally {
    creating.value = false
  }
}

function openModal() {
  error.value = ''
  newName.value = ''
  newDescription.value = ''
  showModal.value = true
}

onMounted(fetchVaults)
</script>

<template>
  <div>
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Coffres</h1>
        <p class="mt-1 text-sm text-muted">Gérez vos coffres-forts et vos secrets chiffrés.</p>
      </div>
      <button
        @click="openModal"
        class="inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim"
      >
        <Plus class="h-4 w-4" />
        Nouveau coffre
      </button>
    </div>

    <div class="relative mt-6">
      <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Rechercher un coffre..."
        class="w-full rounded-lg border border-white/[0.06] bg-surface-50 py-2.5 pl-10 pr-4 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
      />
    </div>

    <!-- Loading -->
    <div v-if="loading" class="mt-16 flex justify-center">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredVaults.length === 0 && !searchQuery" class="mt-16 flex flex-col items-center text-center">
      <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-50 border border-white/[0.06]">
        <VaultIcon class="h-6 w-6 text-muted" />
      </div>
      <h3 class="mt-4 text-sm font-medium text-zinc-300">Aucun coffre</h3>
      <p class="mt-1 text-sm text-muted">Créez votre premier coffre pour commencer à stocker des secrets.</p>
    </div>

    <!-- No results -->
    <div v-else-if="filteredVaults.length === 0 && searchQuery" class="mt-16 flex flex-col items-center text-center">
      <h3 class="text-sm font-medium text-zinc-300">Aucun résultat</h3>
      <p class="mt-1 text-sm text-muted">Aucun coffre ne correspond à « {{ searchQuery }} ».</p>
    </div>

    <!-- Vault grid -->
    <div v-else class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
      <NuxtLink
        v-for="vault in filteredVaults"
        :key="vault.id"
        :to="`/vaults/${vault.id}`"
        class="group relative rounded-xl border border-white/[0.06] bg-surface-50 p-5 transition-all duration-200 hover:border-white/[0.1] hover:bg-surface-100 cursor-pointer block"
      >
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
              <Lock class="h-5 w-5 text-accent" />
            </div>
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-zinc-100">{{ vault.name }}</h3>
              <p v-if="vault.description" class="truncate text-xs text-muted">{{ vault.description }}</p>
            </div>
          </div>
          <span
            class="shrink-0 rounded px-1.5 py-0.5 text-[10px] font-medium capitalize"
            :class="vault.role === 'propriétaire'
              ? 'bg-accent/10 text-accent'
              : 'bg-surface-200 text-zinc-400'"
          >
            {{ vault.role }}
          </span>
        </div>

        <div class="mt-4 flex items-center gap-4 text-xs text-muted">
          <div class="flex items-center gap-1.5">
            <KeyRound class="h-3 w-3" />
            <span>{{ vault.secretsCount }} {{ vault.secretsCount <= 1 ? 'secret' : 'secrets' }}</span>
          </div>
          <span class="text-white/10">|</span>
          <div class="flex items-center gap-1.5">
            <Users class="h-3 w-3" />
            <span>{{ vault.membersCount }} {{ vault.membersCount <= 1 ? 'membre' : 'membres' }}</span>
          </div>
          <span class="text-white/10">|</span>
          <div class="flex items-center gap-1.5 ml-auto">
            <Clock class="h-3 w-3" />
            <span>{{ formatRelativeDate(vault.lastActivityAt) }}</span>
          </div>
        </div>
      </NuxtLink>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60" @click="showModal = false" />
        <div class="relative w-full max-w-md rounded-xl border border-white/[0.06] bg-surface-50 p-6 shadow-2xl">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-100">Nouveau coffre</h2>
            <button @click="showModal = false" class="rounded-md p-1 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div v-if="error" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
            {{ error }}
          </div>

          <form @submit.prevent="createVault" class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Nom</label>
              <input
                v-model="newName"
                type="text"
                required
                autofocus
                placeholder="Mon coffre"
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Description <span class="text-muted">(optionnel)</span></label>
              <input
                v-model="newDescription"
                type="text"
                placeholder="À quoi sert ce coffre..."
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button
                type="button"
                @click="showModal = false"
                class="rounded-lg px-4 py-2 text-sm font-medium text-muted transition-colors hover:bg-white/[0.04] hover:text-zinc-300"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="creating || !newName.trim()"
                class="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
              >
                {{ creating ? 'Création...' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
