interface JwtPayload {
  username: string
  roles: string[]
  exp: number
  '2fa_pending'?: boolean
  '2fa_enabled'?: boolean
  email: string
}

function parseJwt(token: string): JwtPayload | null {
  try {
    const base64 = token.split('.')[1]
    const json = atob(base64.replace(/-/g, '+').replace(/_/g, '/'))
    const payload = JSON.parse(json)
    // Symfony utilise "username" dans le JWT, on normalise en "email"
    if (payload.username && !payload.email) {
      payload.email = payload.username
    }
    return payload
  } catch {
    return null
  }
}

export function useAuth() {
  const token = useState<string | null>('auth:token', () => null)
  const user = useState<JwtPayload | null>('auth:user', () => null)

  function setToken(newToken: string) {
    token.value = newToken
    user.value = parseJwt(newToken)
    if (import.meta.client) {
      localStorage.setItem('token', newToken)
    }
  }

  function logout() {
    token.value = null
    user.value = null
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
          token.value = stored
          user.value = payload
        } else {
          localStorage.removeItem('token')
        }
      }
    }
  }

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const is2faPending = computed(() => user.value?.['2fa_pending'] === true)

  return {
    token: computed(() => token.value),
    user: computed(() => user.value),
    isAuthenticated,
    is2faPending,
    setToken,
    logout,
    init,
  }
}
