export default defineNuxtRouteMiddleware((to) => {
  const publicRoutes = ['/login', '/register']

  if (publicRoutes.includes(to.path)) {
    return
  }

  if (import.meta.client) {
    const { isAuthenticated } = useAuth()
    if (!isAuthenticated.value) {
      return navigateTo('/login')
    }
  }
})
