<script setup lang="ts">
import { Mail, Lock, Shield, User } from 'lucide-vue-next'

const api = useApi()
const { user: authUser } = useAuth()

const { set: setBreadcrumb } = useBreadcrumb()
setBreadcrumb([{ label: 'Profil' }])

interface Profile {
  id: number
  email: string
  twoFactorEnabled: boolean
  createdAt: string
}

const profile = ref<Profile | null>(null)
const loading = ref(true)

const email = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const confirmNewPassword = ref('')
const saving = ref(false)
const error = ref('')
const success = ref('')

async function fetchProfile() {
  loading.value = true
  try {
    profile.value = await api.get<Profile>('/api/profile')
    email.value = profile.value.email
  } catch {
    profile.value = null
  } finally {
    loading.value = false
  }
}

async function saveProfile() {
  error.value = ''
  success.value = ''

  if (!currentPassword.value) {
    error.value = 'Le mot de passe actuel est requis pour confirmer les modifications.'
    return
  }

  if (newPassword.value && newPassword.value !== confirmNewPassword.value) {
    error.value = 'Les mots de passe ne correspondent pas.'
    return
  }

  saving.value = true
  try {
    const payload: Record<string, string> = {
      email: email.value,
      currentPassword: currentPassword.value,
    }
    if (newPassword.value) {
      payload.newPassword = newPassword.value
    }

    profile.value = await api.put<Profile>('/api/profile', payload)
    email.value = profile.value.email
    currentPassword.value = ''
    newPassword.value = ''
    confirmNewPassword.value = ''
    success.value = 'Profil mis à jour avec succès.'
  } catch (e: any) {
    error.value = e.error || 'Impossible de mettre à jour le profil.'
  } finally {
    saving.value = false
  }
}

function formatDate(iso: string): string {
  return new Date(iso).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(fetchProfile)
</script>

<template>
  <div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Profil</h1>
      <p class="mt-1 text-sm text-muted">Modifiez les informations de votre compte.</p>
    </div>

    <div v-if="loading" class="mt-16 flex justify-center">
      <div class="h-6 w-6 animate-spin rounded-full border-2 border-accent border-t-transparent" />
    </div>

    <template v-else-if="profile">
      <div v-if="error" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
        {{ error }}
      </div>
      <div v-if="success" class="mt-4 rounded-lg bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400">
        {{ success }}
      </div>

      <form @submit.prevent="saveProfile" class="mt-8 space-y-6">
        <!-- Infos générales -->
        <section class="rounded-xl border border-white/[0.06] bg-surface p-6">
          <h2 class="text-base font-semibold text-zinc-100">Informations générales</h2>
          <p class="mt-1 text-sm text-muted">Votre adresse email sert d'identifiant de connexion.</p>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Adresse email</label>
              <div class="relative">
                <Mail class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                <input
                  v-model="email"
                  type="email"
                  required
                  class="w-full rounded-lg border border-white/[0.06] bg-surface-50 py-2.5 pl-10 pr-4 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
                />
              </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border border-white/[0.06] bg-surface-50 px-4 py-3">
              <Shield class="h-4 w-4 text-muted" />
              <span class="text-sm text-zinc-400">2FA</span>
              <span
                class="rounded px-1.5 py-0.5 text-[10px] font-medium"
                :class="profile.twoFactorEnabled ? 'bg-emerald-500/10 text-emerald-400' : 'bg-surface-200 text-zinc-500'"
              >
                {{ profile.twoFactorEnabled ? 'Activée' : 'Désactivée' }}
              </span>
              <NuxtLink to="/settings" class="ml-auto text-xs text-accent hover:text-accent-dim">Gérer</NuxtLink>
            </div>

            <div class="text-xs text-muted">
              Membre depuis le {{ formatDate(profile.createdAt) }}
            </div>
          </div>
        </section>

        <!-- Mot de passe -->
        <section class="rounded-xl border border-white/[0.06] bg-surface p-6">
          <h2 class="text-base font-semibold text-zinc-100">Mot de passe</h2>
          <p class="mt-1 text-sm text-muted">Laissez le nouveau mot de passe vide pour ne pas le changer.</p>

          <div class="mt-5 space-y-4">
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Mot de passe actuel <span class="text-red-400">*</span></label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                <input
                  v-model="currentPassword"
                  type="password"
                  required
                  placeholder="••••••••"
                  class="w-full rounded-lg border border-white/[0.06] bg-surface-50 py-2.5 pl-10 pr-4 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
                />
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Nouveau mot de passe</label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                <input
                  v-model="newPassword"
                  type="password"
                  placeholder="••••••••"
                  class="w-full rounded-lg border border-white/[0.06] bg-surface-50 py-2.5 pl-10 pr-4 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
                />
              </div>
            </div>
            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Confirmer le nouveau mot de passe</label>
              <div class="relative">
                <Lock class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" />
                <input
                  v-model="confirmNewPassword"
                  type="password"
                  placeholder="••••••••"
                  class="w-full rounded-lg border border-white/[0.06] bg-surface-50 py-2.5 pl-10 pr-4 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
                />
              </div>
            </div>
          </div>
        </section>

        <div class="flex justify-end">
          <button
            type="submit"
            :disabled="saving"
            class="rounded-lg bg-accent px-5 py-2.5 text-sm font-medium text-zinc-950 transition-colors hover:bg-accent-dim disabled:opacity-50"
          >
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>
