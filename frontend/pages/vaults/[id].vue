<script setup lang="ts">
import {
  KeyRound, Users, Plus, X, Eye, EyeOff, Copy, Check, UserPlus, Activity,
} from 'lucide-vue-next'

const route = useRoute()
const api = useApi()
const vaultId = route.params.id as string

interface VaultDetail {
  id: number
  name: string
  description: string | null
  role: string
  secretsCount: number
  membersCount: number
}

interface SecretItem {
  id: number
  name: string
  description: string | null
  createdAt: string
}

interface MemberItem {
  id: number
  email: string
  role: string
  createdAt: string
}

interface LogItem {
  id: number
  action: string
  performedBy: string | null
  secretName: string | null
  ipAddress: string | null
  userAgent: string | null
  createdAt: string
}

const vault = ref<VaultDetail | null>(null)
const secrets = ref<SecretItem[]>([])
const members = ref<MemberItem[]>([])
const logs = ref<LogItem[]>([])
const loading = ref(true)
const activeTab = ref<'secrets' | 'members' | 'activity'>('secrets')

const { set: setBreadcrumb } = useBreadcrumb()

const tabLabels: Record<string, string> = {
  secrets: 'Secrets',
  members: 'Membres',
  activity: 'Activité',
}

watch([vault, activeTab], () => {
  if (vault.value) {
    setBreadcrumb([
      { label: 'Coffres', to: '/vaults' },
      { label: vault.value.name, to: `/vaults/${vaultId}` },
      { label: tabLabels[activeTab.value] },
    ])
  }
}, { immediate: true })

// Secret modal
const showSecretModal = ref(false)
const newSecretName = ref('')
const newSecretValue = ref('')
const newSecretDescription = ref('')
const creatingSecret = ref(false)
const secretError = ref('')

// Member modal
const showMemberModal = ref(false)
const newMemberEmail = ref('')
const newMemberRole = ref('member')
const addingMember = ref(false)
const memberError = ref('')

// Reveal secret
const revealedSecrets = ref<Record<number, string>>({})
const loadingSecret = ref<number | null>(null)
const copiedId = ref<number | null>(null)

async function fetchAll() {
  loading.value = true
  try {
    const [v, s, m, l] = await Promise.all([
      api.get<VaultDetail>(`/api/vaults/${vaultId}`),
      api.get<SecretItem[]>(`/api/vaults/${vaultId}/secrets`),
      api.get<MemberItem[]>(`/api/vaults/${vaultId}/members`),
      api.get<LogItem[]>(`/api/vaults/${vaultId}/logs`),
    ])
    vault.value = v
    secrets.value = s
    members.value = m
    logs.value = l
  } catch {
    navigateTo('/vaults')
  } finally {
    loading.value = false
  }
}

async function createSecret() {
  secretError.value = ''
  creatingSecret.value = true
  try {
    const s = await api.post<SecretItem>(`/api/vaults/${vaultId}/secrets`, {
      name: newSecretName.value,
      value: newSecretValue.value,
      description: newSecretDescription.value || null,
    })
    secrets.value.unshift(s)
    showSecretModal.value = false
    newSecretName.value = ''
    newSecretValue.value = ''
    newSecretDescription.value = ''
  } catch (e: any) {
    secretError.value = e.error || 'Impossible de créer le secret.'
  } finally {
    creatingSecret.value = false
  }
}

async function toggleReveal(secretId: number) {
  if (revealedSecrets.value[secretId]) {
    delete revealedSecrets.value[secretId]
    return
  }

  loadingSecret.value = secretId
  try {
    const res = await api.get<{ value: string }>(`/api/vaults/${vaultId}/secrets/${secretId}`)
    revealedSecrets.value[secretId] = res.value
  } catch {
    // ignore
  } finally {
    loadingSecret.value = null
  }
}

async function copySecret(secretId: number) {
  let value = revealedSecrets.value[secretId]
  if (!value) {
    const res = await api.get<{ value: string }>(`/api/vaults/${vaultId}/secrets/${secretId}`)
    value = res.value
  }
  await navigator.clipboard.writeText(value)
  copiedId.value = secretId
  setTimeout(() => { copiedId.value = null }, 2000)
}

async function addMember() {
  memberError.value = ''
  addingMember.value = true
  try {
    const m = await api.post<MemberItem>(`/api/vaults/${vaultId}/members`, {
      email: newMemberEmail.value,
      role: newMemberRole.value,
    })
    members.value.push(m)
    showMemberModal.value = false
    newMemberEmail.value = ''
    newMemberRole.value = 'member'
  } catch (e: any) {
    memberError.value = e.error || 'Impossible d\'ajouter le membre.'
  } finally {
    addingMember.value = false
  }
}

const isOwner = computed(() => vault.value?.role === 'propriétaire')

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

onMounted(fetchAll)
</script>

<template>
  <div>
    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-20">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
    </div>

    <template v-else-if="vault">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-semibold tracking-tight">{{ vault.name }}</h1>
            <span
              class="rounded px-1.5 py-0.5 text-[10px] font-medium capitalize"
              :class="isOwner ? 'bg-accent/10 text-accent' : 'bg-surface-200 text-zinc-400'"
            >
              {{ vault.role }}
            </span>
          </div>
          <p v-if="vault.description" class="mt-1 text-sm text-muted">{{ vault.description }}</p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mt-6 flex gap-1 border-b border-white/[0.06]">
        <button
          @click="activeTab = 'secrets'"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
          :class="activeTab === 'secrets'
            ? 'border-accent text-zinc-100'
            : 'border-transparent text-muted hover:text-zinc-300'"
        >
          <KeyRound class="h-4 w-4" />
          Secrets
          <span class="rounded-full bg-surface-200 px-1.5 py-0.5 text-[10px] font-medium text-zinc-400">{{ secrets.length }}</span>
        </button>
        <button
          @click="activeTab = 'members'"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
          :class="activeTab === 'members'
            ? 'border-accent text-zinc-100'
            : 'border-transparent text-muted hover:text-zinc-300'"
        >
          <Users class="h-4 w-4" />
          Membres
          <span class="rounded-full bg-surface-200 px-1.5 py-0.5 text-[10px] font-medium text-zinc-400">{{ members.length }}</span>
        </button>
        <button
          @click="activeTab = 'activity'"
          class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-colors"
          :class="activeTab === 'activity'
            ? 'border-accent text-zinc-100'
            : 'border-transparent text-muted hover:text-zinc-300'"
        >
          <Activity class="h-4 w-4" />
          Activité
          <span class="rounded-full bg-surface-200 px-1.5 py-0.5 text-[10px] font-medium text-zinc-400">{{ logs.length }}</span>
        </button>
      </div>

      <!-- Secrets tab -->
      <div v-if="activeTab === 'secrets'" class="mt-6">
        <div class="flex justify-end">
          <button
            @click="secretError = ''; newSecretName = ''; newSecretValue = ''; newSecretDescription = ''; showSecretModal = true"
            class="inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim"
          >
            <Plus class="h-4 w-4" />
            Nouveau secret
          </button>
        </div>

        <!-- Empty -->
        <div v-if="secrets.length === 0" class="mt-12 flex flex-col items-center text-center">
          <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-50 border border-white/[0.06]">
            <KeyRound class="h-6 w-6 text-muted" />
          </div>
          <h3 class="mt-4 text-sm font-medium text-zinc-300">Aucun secret</h3>
          <p class="mt-1 text-sm text-muted">Ajoutez votre premier secret dans ce coffre.</p>
        </div>

        <!-- Secret list -->
        <div v-else class="mt-4 space-y-2">
          <div
            v-for="secret in secrets"
            :key="secret.id"
            class="rounded-xl border border-white/[0.06] bg-surface-50 p-4 transition-colors hover:border-white/[0.1]"
          >
            <div class="flex items-center justify-between">
              <div class="min-w-0 flex-1">
                <h4 class="text-sm font-medium text-zinc-100">{{ secret.name }}</h4>
                <p v-if="secret.description" class="mt-0.5 truncate text-xs text-muted">{{ secret.description }}</p>
              </div>
              <div class="flex items-center gap-3 shrink-0">
                <button
                  @click="toggleReveal(secret.id)"
                  class="rounded-md p-1.5 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300"
                  :title="revealedSecrets[secret.id] ? 'Masquer' : 'Révéler'"
                >
                  <EyeOff v-if="revealedSecrets[secret.id]" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
                <button
                  @click="copySecret(secret.id)"
                  class="rounded-md p-1.5 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300"
                  title="Copier"
                >
                  <Check v-if="copiedId === secret.id" class="h-4 w-4 text-emerald-400" />
                  <Copy v-else class="h-4 w-4" />
                </button>
              </div>
            </div>
            <div
              v-if="revealedSecrets[secret.id]"
              class="mt-3 rounded-lg bg-surface px-3 py-2"
            >
              <p class="font-mono text-sm text-zinc-200 break-all select-all">{{ revealedSecrets[secret.id] }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Members tab -->
      <div v-if="activeTab === 'members'" class="mt-6">
        <div v-if="isOwner" class="flex justify-end">
          <button
            @click="memberError = ''; newMemberEmail = ''; newMemberRole = 'member'; showMemberModal = true"
            class="inline-flex items-center gap-2 rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim"
          >
            <UserPlus class="h-4 w-4" />
            Ajouter un membre
          </button>
        </div>

        <div class="mt-4 space-y-2">
          <div
            v-for="member in members"
            :key="member.id"
            class="flex items-center justify-between rounded-xl border border-white/[0.06] bg-surface-50 px-4 py-3"
          >
            <div class="flex items-center gap-3">
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-surface-200">
                <span class="text-xs font-medium text-zinc-400">{{ member.email[0].toUpperCase() }}</span>
              </div>
              <span class="text-sm text-zinc-200">{{ member.email }}</span>
            </div>
            <span
              class="rounded px-1.5 py-0.5 text-[10px] font-medium capitalize"
              :class="member.role === 'owner' ? 'bg-accent/10 text-accent' : 'bg-surface-200 text-zinc-400'"
            >
              {{ member.role === 'owner' ? 'propriétaire' : member.role }}
            </span>
          </div>
        </div>
      </div>

      <!-- Activity tab -->
      <div v-if="activeTab === 'activity'" class="mt-6">
        <div v-if="logs.length === 0" class="mt-12 flex flex-col items-center text-center">
          <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-50 border border-white/[0.06]">
            <Activity class="h-6 w-6 text-muted" />
          </div>
          <h3 class="mt-4 text-sm font-medium text-zinc-300">Aucune activité</h3>
          <p class="mt-1 text-sm text-muted">Les actions sur ce coffre apparaîtront ici.</p>
        </div>

        <div v-else class="space-y-2">
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

    <!-- Secret modal -->
    <Teleport to="body">
      <div v-if="showSecretModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60" @click="showSecretModal = false" />
        <div class="relative w-full max-w-md rounded-xl border border-white/[0.06] bg-surface-50 p-6 shadow-2xl">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-100">Nouveau secret</h2>
            <button @click="showSecretModal = false" class="rounded-md p-1 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div v-if="secretError" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
            {{ secretError }}
          </div>

          <form @submit.prevent="createSecret" class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Nom</label>
              <input
                v-model="newSecretName"
                type="text"
                required
                autofocus
                placeholder="Ex: Mot de passe GitHub"
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Valeur</label>
              <input
                v-model="newSecretValue"
                type="password"
                required
                placeholder="••••••••"
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm font-mono text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Description <span class="text-muted">(optionnel)</span></label>
              <input
                v-model="newSecretDescription"
                type="text"
                placeholder="Notes sur ce secret..."
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showSecretModal = false" class="rounded-lg px-4 py-2 text-sm font-medium text-muted transition-colors hover:bg-white/[0.04] hover:text-zinc-300">
                Annuler
              </button>
              <button
                type="submit"
                :disabled="creatingSecret || !newSecretName.trim() || !newSecretValue"
                class="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
              >
                {{ creatingSecret ? 'Création...' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Member modal -->
    <Teleport to="body">
      <div v-if="showMemberModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60" @click="showMemberModal = false" />
        <div class="relative w-full max-w-md rounded-xl border border-white/[0.06] bg-surface-50 p-6 shadow-2xl">
          <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-zinc-100">Ajouter un membre</h2>
            <button @click="showMemberModal = false" class="rounded-md p-1 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300">
              <X class="h-4 w-4" />
            </button>
          </div>

          <div v-if="memberError" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
            {{ memberError }}
          </div>

          <form @submit.prevent="addMember" class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Adresse email</label>
              <input
                v-model="newMemberEmail"
                type="email"
                required
                autofocus
                placeholder="membre@exemple.com"
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Rôle</label>
              <select
                v-model="newMemberRole"
                class="w-full rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5 text-sm text-zinc-200 outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              >
                <option value="member">Membre</option>
                <option value="admin">Administrateur</option>
              </select>
            </div>
            <div class="flex justify-end gap-3 pt-2">
              <button type="button" @click="showMemberModal = false" class="rounded-lg px-4 py-2 text-sm font-medium text-muted transition-colors hover:bg-white/[0.04] hover:text-zinc-300">
                Annuler
              </button>
              <button
                type="submit"
                :disabled="addingMember || !newMemberEmail.trim()"
                class="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
              >
                {{ addingMember ? 'Ajout...' : 'Ajouter' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </div>
</template>
