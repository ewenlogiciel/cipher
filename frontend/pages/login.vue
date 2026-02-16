<script setup lang="ts">
import { Shield } from 'lucide-vue-next'

definePageMeta({ layout: false })

const api = useApi()
const { setToken } = useAuth()

const email = ref('')
const password = ref('')
const totpCode = ref('')
const error = ref('')
const loading = ref(false)
const needs2fa = ref(false)
const pendingToken = ref('')

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    const res = await api.post<{ token: string; '2fa_required'?: boolean }>('/api/login', {
      email: email.value,
      password: password.value,
    })

    if (res['2fa_required']) {
      needs2fa.value = true
      pendingToken.value = res.token
    } else {
      setToken(res.token)
      navigateTo('/')
    }
  } catch (e: any) {
    error.value = e.message || 'Identifiants invalides.'
  } finally {
    loading.value = false
  }
}

async function handleVerify() {
  error.value = ''
  loading.value = true

  try {
    const response = await fetch(`${useRuntimeConfig().public.apiBase}/api/2fa/verify`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${pendingToken.value}`,
      },
      body: JSON.stringify({ code: totpCode.value }),
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.error || 'Code invalide.')
    }

    setToken(data.token)
    navigateTo('/')
  } catch (e: any) {
    error.value = e.message || 'Code TOTP invalide.'
  } finally {
    loading.value = false
  }
}
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

      <form v-if="!needs2fa" @submit.prevent="handleLogin" class="space-y-4">
        <h1 class="text-center text-lg font-semibold text-zinc-100">Connectez-vous à votre compte</h1>

        <div v-if="error" class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
          {{ error }}
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
            class="w-full rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
            placeholder="••••••••"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
        >
          {{ loading ? 'Connexion...' : 'Se connecter' }}
        </button>

        <p class="text-center text-sm text-muted">
          Pas encore de compte ?
          <NuxtLink to="/register" class="text-accent hover:underline">S'inscrire</NuxtLink>
        </p>
      </form>

      <form v-else @submit.prevent="handleVerify" class="space-y-4">
        <h1 class="text-center text-lg font-semibold text-zinc-100">Authentification à deux facteurs</h1>
        <p class="text-center text-sm text-muted">Entrez le code de votre application d'authentification.</p>

        <div v-if="error" class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
          {{ error }}
        </div>

        <div>
          <input
            v-model="totpCode"
            type="text"
            inputmode="numeric"
            maxlength="6"
            required
            class="w-full rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-center font-mono text-lg tracking-[0.3em] text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
            placeholder="000000"
          />
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="w-full rounded-lg bg-accent px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-accent-dim disabled:opacity-50"
        >
          {{ loading ? 'Vérification...' : 'Vérifier' }}
        </button>
      </form>
    </div>
  </div>
</template>
