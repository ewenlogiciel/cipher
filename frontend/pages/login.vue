<script setup lang="ts">
import { Eye, EyeOff } from 'lucide-vue-next'

definePageMeta({ layout: false })

const api = useApi()
const { setToken } = useAuth()

const email = ref('')
const password = ref('')
const showPassword = ref(false)
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
  <div class="flex min-h-screen bg-[#08080d] overflow-hidden">

    <!-- Dot grid backdrop -->
    <div class="absolute inset-0 z-0 pointer-events-none [background-image:radial-gradient(circle,rgba(255,255,255,0.06)_1px,transparent_1px)] [background-size:26px_26px]" />

    <!-- ── Left panel : Form ── -->
    <div class="relative z-10 flex flex-1 items-center justify-center px-8 py-12">
      <div class="w-full max-w-[360px]">
        <Transition name="slide" mode="out-in">

          <!-- Login form -->
          <form v-if="!needs2fa" key="login" @submit.prevent="handleLogin" class="flex flex-col gap-5">
            <div class="mb-1">
              <h1 class="text-2xl font-semibold tracking-tight text-zinc-100">Connexion</h1>
              <p class="mt-1 text-sm text-zinc-500">Accédez à vos secrets chiffrés</p>
            </div>

            <div v-if="error" class="rounded-lg border border-red-500/20 bg-red-500/[0.07] px-4 py-3 text-sm text-red-400">
              {{ error }}
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-medium text-zinc-500">Email</label>
              <input
                v-model="email"
                type="email"
                required
                placeholder="caca@exemple.com"
                class="w-full rounded-[10px] border border-white/[0.065] bg-white/[0.025] px-4 py-3 text-sm text-zinc-200 outline-none transition-all placeholder:text-zinc-600 focus:border-accent/40 focus:bg-accent/[0.025] focus:ring-2 focus:ring-accent/10"
              />
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-medium text-zinc-500">Mot de passe</label>
              <div class="relative">
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="••••••••"
                  class="w-full rounded-[10px] border border-white/[0.065] bg-white/[0.025] px-4 py-3 pr-10 text-sm text-zinc-200 outline-none transition-all placeholder:text-zinc-600 focus:border-accent/40 focus:bg-accent/[0.025] focus:ring-2 focus:ring-accent/10"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-600 transition-colors hover:text-zinc-300"
                >
                  <Eye v-if="showPassword" class="h-4 w-4" />
                  <EyeOff v-else class="h-4 w-4" />
                </button>
              </div>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="mt-1 w-full rounded-[10px] bg-accent py-3 text-sm font-semibold text-zinc-950 transition-all hover:bg-accent-dim active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span v-if="!loading">Se connecter</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#0c0a16]/30 border-t-[#0c0a16]" />
                Connexion…
              </span>
            </button>

            <p class="text-center text-sm text-zinc-600">
              Pas encore de compte ?
              <NuxtLink to="/register" class="text-accent transition-colors hover:underline">S'inscrire</NuxtLink>
            </p>
          </form>

          <!-- 2FA form -->
          <form v-else key="twofa" @submit.prevent="handleVerify" class="flex flex-col gap-5">
            <div class="mb-1">
              <h1 class="text-2xl font-semibold tracking-tight text-zinc-100">Vérification</h1>
              <p class="mt-1 text-sm text-zinc-500">Entrez le code de votre application d'authentification</p>
            </div>

            <div v-if="error" class="rounded-lg border border-red-500/20 bg-red-500/[0.07] px-4 py-3 text-sm text-red-400">
              {{ error }}
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-[0.675rem] font-medium uppercase tracking-[0.1em] text-zinc-600">Code TOTP</label>
              <input
                v-model="totpCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                required
                placeholder="000 000"
                class="w-full rounded-[10px] border border-white/[0.065] bg-white/[0.025] px-4 py-4 text-center font-mono text-2xl tracking-[0.3em] text-zinc-200 outline-none transition-all placeholder:text-zinc-700 focus:border-accent/40 focus:bg-accent/[0.025] focus:ring-2 focus:ring-accent/10"
              />
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="mt-1 w-full rounded-[10px] bg-accent py-3 text-sm font-semibold text-zinc-950 transition-all hover:-translate-y-px hover:bg-accent-dim active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <span v-if="!loading">Vérifier</span>
              <span v-else class="flex items-center justify-center gap-2">
                <span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-[#0c0a16]/30 border-t-[#0c0a16]" />
                Vérification…
              </span>
            </button>
          </form>

        </Transition>
      </div>
    </div>

    <!-- ── Right panel : Brand ── -->
    <div class="relative z-10 hidden w-[42%] shrink-0 items-center justify-center bg-zinc-900 md:flex">
      <span class="font-sans text-5xl font-bold text-white-300">cipher</span>
    </div>

  </div>
</template>

<style scoped>
/* Vue transition — requires named CSS classes, not expressible with Tailwind utilities */
.slide-enter-active { transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-leave-active { transition: all 0.15s ease-in; }
.slide-enter-from   { opacity: 0; transform: translateX(12px); }
.slide-leave-to     { opacity: 0; transform: translateX(-12px); }
</style>
