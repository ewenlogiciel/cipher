<script setup lang="ts">
import { Shield } from 'lucide-vue-next'

definePageMeta({ layout: false })

const api = useApi()
const { setToken } = useAuth()

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string[]>>({})
const loading = ref(false)

async function handleRegister() {
  errors.value = {}
  loading.value = true

  try {
    await api.post('/api/register', {
      email: email.value,
      password: password.value,
    })

    const res = await api.post<{ token: string }>('/api/login', {
      email: email.value,
      password: password.value,
    })

    setToken(res.token)
    navigateTo('/')
  } catch (e: any) {
    if (e.errors) {
      errors.value = e.errors
    } else {
      errors.value = { general: [e.message || 'Échec de l\'inscription.'] }
    }
  } finally {
    loading.value = false
  }
}

const firstError = computed(() => {
  const allErrors = Object.values(errors.value).flat()
  return allErrors[0] || ''
})
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-surface px-4">
    <div class="w-full max-w-sm">
      <div class="mb-8 flex items-center justify-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-accent/10">
          <Shield class="h-5 w-5 text-accent" />
        </div>
        <span class="text-xl font-semibold tracking-tight text-zinc-100">Cipher</span>
      </div>

      <form @submit.prevent="handleRegister" class="space-y-4">
        <h1 class="text-center text-lg font-semibold text-zinc-100">Créer un compte</h1>

        <div v-if="firstError" class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
          {{ firstError }}
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-muted">Email</label>
          <input
            v-model="email"
            type="email"
            required
            class="w-full rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
            placeholder="vous@exemple.com"
          />
        </div>

        <div>
          <label class="mb-1.5 block text-xs font-medium text-muted">Mot de passe</label>
          <input
            v-model="password"
            type="password"
            required
            minlength="8"
            class="w-full rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
            placeholder="Min. 8 caractères"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
        >
          {{ loading ? 'Création...' : 'Créer un compte' }}
        </button>

        <p class="text-center text-sm text-muted">
          Déjà un compte ?
          <NuxtLink to="/login" class="text-accent hover:underline">Se connecter</NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>
