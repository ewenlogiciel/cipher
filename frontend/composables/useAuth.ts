interface JwtPayload {
  email: string
  roles: string[]
  exp: number
  '2fa_pending'?: boolean
  '2fa_enabled'?: boolean
}

interface AuthState {
  token: string | null
  user: JwtPayload | null
}

const state = reactive<AuthState>({
  token: null,
  user: null,
})

function parseJwt(token: string): JwtPayload | null {
  try {
    const base64 = token.split('.')[1]
    const json = atob(base64.replace(/-/g, '+').replace(/_/g, '/'))
    return JSON.parse(json)
  } catch {
    return null
  }
}

export function useAuth() {
  function setToken(token: string) {
    state.token = token
    state.user = parseJwt(token)
    if (import.meta.client) {
      localStorage.setItem('token', token)
    }
  }

  function logout() {
    state.token = null
    state.user = null
    if (import.meta.client) {
      localStorage.removeItem('token')
    }
    navigateTo('/login')
  }

  function init() {
    if (import.meta.client) {
      const stored = localStorage.getItem('token')
      if (stored) {
        const payload = parseJwt(stored)
        if (payload && payload.exp * 1000 > Date.now()) {
          state.token = stored
          state.user = payload
        } else {
          localStorage.removeItem('token')
        }
      }
    }
  }

  const isAuthenticated = computed(() => !!state.token && !!state.user)
  const is2faPending = computed(() => state.user?.['2fa_pending'] === true)

  return {
    token: computed(() => state.token),
    user: computed(() => state.user),
    isAuthenticated,
    is2faPending,
    setToken,
    logout,
    init,
  }
}
