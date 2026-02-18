<script setup lang="ts">
import { Shield, Mail, Key } from 'lucide-vue-next'
import QRCode from 'qrcode'

const api = useApi()
const { user } = useAuth()

const { set: setBreadcrumb } = useBreadcrumb()
setBreadcrumb([{ label: 'Paramètres' }])
const qrCodeDataUrl = ref('')

const twoFactorEnabled = ref(user.value?.['2fa_enabled'] ?? false)
const totpSecret = ref('')
const provisioningUri = ref('')
const confirmCode = ref('')
const disableCode = ref('')
const error = ref('')
const success = ref('')
const setupStep = ref<'idle' | 'pending' | 'confirm'>('idle')
const loading = ref(false)

async function enableTwoFactor() {
  error.value = ''
  loading.value = true

  try {
    const res = await api.post<{ secret: string; provisioning_uri: string }>('/api/2fa/enable')
    totpSecret.value = res.secret
    provisioningUri.value = res.provisioning_uri
    qrCodeDataUrl.value = await QRCode.toDataURL(res.provisioning_uri, {
      width: 200,
      margin: 2,
      color: { dark: '#ffffffFF', light: '#00000000' },
    })
    setupStep.value = 'confirm'
  } catch (e: any) {
    error.value = e.error || 'Impossible d\'activer la 2FA.'
  } finally {
    loading.value = false
  }
}

async function confirmTwoFactor() {
  error.value = ''
  loading.value = true

  try {
    await api.post('/api/2fa/confirm', { code: confirmCode.value })
    twoFactorEnabled.value = true
    setupStep.value = 'idle'
    confirmCode.value = ''
    totpSecret.value = ''
    provisioningUri.value = ''
    qrCodeDataUrl.value = ''
    success.value = 'La 2FA a été activée.'
  } catch (e: any) {
    error.value = e.error || 'Code TOTP invalide.'
  } finally {
    loading.value = false
  }
}

async function disableTwoFactor() {
  error.value = ''
  loading.value = true

  try {
    await api.post('/api/2fa/disable', { code: disableCode.value })
    twoFactorEnabled.value = false
    disableCode.value = ''
    success.value = 'La 2FA a été désactivée.'
  } catch (e: any) {
    error.value = e.error || 'Code TOTP invalide.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Paramètres</h1>
      <p class="mt-1 text-sm text-muted">Gérez votre compte et vos préférences de sécurité.</p>
    </div>

    <div v-if="error" class="mt-4 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-400">
      {{ error }}
    </div>
    <div v-if="success" class="mt-4 rounded-lg bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400">
      {{ success }}
    </div>

    <div class="mt-8 space-y-6">
      <section class="rounded-xl border border-white/[0.06] bg-surface-50 p-6">
        <h2 class="text-base font-semibold text-zinc-100">Compte</h2>
        <p class="mt-1 text-sm text-muted">Informations de votre compte.</p>

        <div class="mt-5">
          <label class="mb-1.5 block text-xs font-medium text-muted">Email</label>
          <div class="flex items-center gap-3 rounded-lg border border-white/[0.06] bg-surface px-3 py-2.5">
            <Mail class="h-4 w-4 text-muted" />
            <span class="text-sm text-zinc-300">{{ user?.email }}</span>
          </div>
        </div>
      </section>

      <section class="rounded-xl border border-white/[0.06] bg-surface-50 p-6">
        <h2 class="text-base font-semibold text-zinc-100">Sécurité</h2>
        <p class="mt-1 text-sm text-muted">Protégez votre compte avec une sécurité supplémentaire.</p>

        <div class="mt-5 space-y-4">
          <div class="flex items-center justify-between rounded-lg border border-white/[0.06] bg-surface px-4 py-4">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-accent/10">
                <Shield class="h-5 w-5 text-accent" />
              </div>
              <div>
                <p class="text-sm font-medium text-zinc-100">Authentification à deux facteurs</p>
                <p class="text-xs text-muted">
                  {{ twoFactorEnabled ? 'Votre compte est sécurisé avec le TOTP.' : 'Ajoutez une couche de sécurité supplémentaire.' }}
                </p>
              </div>
            </div>
            <button
              v-if="!twoFactorEnabled && setupStep === 'idle'"
              :disabled="loading"
              @click="enableTwoFactor"
              class="rounded-lg bg-accent px-4 py-2 text-sm font-medium text-zinc-950 transition-colors hover:bg-accent-dim disabled:opacity-50"
            >
              Activer
            </button>
          </div>

          <div v-if="setupStep === 'confirm'" class="rounded-lg border border-white/[0.06] bg-surface p-4 space-y-4">
            <p class="text-sm text-zinc-300">Scannez ce QR code avec votre application d'authentification :</p>

            <div class="flex justify-center rounded-lg bg-surface-50 p-4">
              <img :src="qrCodeDataUrl" alt="QR Code TOTP" class="h-[200px] w-[200px]" />
            </div>

            <div class="rounded-lg bg-surface-50 p-3">
              <p class="text-xs text-muted mb-1">Ou entrez le secret manuellement</p>
              <p class="font-mono text-sm text-zinc-200 break-all select-all">{{ totpSecret }}</p>
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-medium text-muted">Code de vérification</label>
              <div class="flex gap-3">
                <input
                  v-model="confirmCode"
                  type="text"
                  inputmode="numeric"
                  maxlength="6"
                  placeholder="000000"
                  class="w-40 rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-center font-mono text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
                />
                <button
                  :disabled="loading || confirmCode.length < 6"
                  @click="confirmTwoFactor"
                  class="rounded-lg bg-accent px-4 py-2.5 text-sm font-medium text-zinc-950 transition-colors hover:bg-accent-dim disabled:opacity-50"
                >
                  Confirmer
                </button>
              </div>
            </div>
          </div>

          <div v-if="twoFactorEnabled" class="rounded-lg border border-white/[0.06] bg-surface p-4 space-y-3">
            <p class="text-sm text-zinc-300">Entrez votre code TOTP pour désactiver l'authentification à deux facteurs.</p>
            <div class="flex gap-3">
              <input
                v-model="disableCode"
                type="text"
                inputmode="numeric"
                maxlength="6"
                placeholder="000000"
                class="w-40 rounded-lg border border-white/[0.06] bg-surface-50 px-3 py-2.5 text-center font-mono text-sm text-zinc-200 placeholder-muted outline-none transition-colors focus:border-accent/40 focus:ring-1 focus:ring-accent/20"
              />
              <button
                :disabled="loading || disableCode.length < 6"
                @click="disableTwoFactor"
                class="rounded-lg border border-red-500/20 px-4 py-2.5 text-sm font-medium text-red-400 transition-colors hover:bg-red-500/10 disabled:opacity-50"
              >
                Désactiver la 2FA
              </button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>
