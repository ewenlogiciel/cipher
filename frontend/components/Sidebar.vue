<script setup lang="ts">
import {
  Home,
  Shield,
  Vault,
  Activity,
  Settings,
  LogOut,
  ChevronLeft,
  User,
  UserCog,
} from 'lucide-vue-next'

const route = useRoute()
const { collapsed, toggle } = useSidebar()
const { user, logout } = useAuth()

const userEmail = computed(() => user.value?.email ?? '')

const navigation = [
  { name: 'Dashboard', icon: Home, path: '/' },
  { name: 'Coffres', icon: Vault, path: '/vaults' },
  { name: 'Activité', icon: Activity, path: '/activity' },
  { name: 'Paramètres', icon: Settings, path: '/settings' },
]

function isActive(path: string): boolean {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}

const showUserMenu = ref(false)
const userMenuRef = ref<HTMLElement | null>(null)

function toggleUserMenu() {
  showUserMenu.value = !showUserMenu.value
}

function handleClickOutside(e: MouseEvent) {
  if (userMenuRef.value && !userMenuRef.value.contains(e.target as Node)) {
    showUserMenu.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-white/[0.06] bg-surface-50 transition-all duration-300"
    :class="collapsed ? 'w-[68px]' : 'w-[240px]'"
  >
    <!-- Logo -->
    <div class="flex h-14 items-center gap-3 border-b border-white/[0.06] px-4">
      <span
        v-show="!collapsed"
        class="text-[20px] font-semibold tracking-tight text-zinc-100"
      >
        Cipher
      </span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 space-y-1 px-3 py-4">
      <NuxtLink
        v-for="item in navigation"
        :key="item.path"
        :to="item.path"
        class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150"
        :class="
          isActive(item.path)
            ? 'bg-white/[0.08] text-zinc-100'
            : 'text-muted hover:bg-white/[0.04] hover:text-zinc-300'
        "
      >
        <component
          :is="item.icon"
          class="h-[18px] w-[18px] shrink-0 transition-colors duration-150"
          :class="isActive(item.path) ? 'text-accent' : 'text-muted group-hover:text-zinc-400'"
        />
        <span v-show="!collapsed">{{ item.name }}</span>
      </NuxtLink>
    </nav>

    <!-- Bottom section -->
    <div class="border-t border-white/[0.06] p-3 space-y-1">
      <!-- Collapse toggle -->
      <button
        @click="toggle()"
        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted transition-colors hover:bg-white/[0.04] hover:text-zinc-300"
      >
        <ChevronLeft
          class="h-[18px] w-[18px] shrink-0 transition-transform duration-300"
          :class="collapsed ? 'rotate-180' : ''"
        />
        <span v-show="!collapsed">Réduire</span>
      </button>

      <!-- User with popover -->
      <div ref="userMenuRef" class="relative">
        <!-- Popover -->
        <Transition
          enter-active-class="transition duration-150 ease-out"
          enter-from-class="opacity-0 translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition duration-100 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 translate-y-1"
        >
          <div
            v-if="showUserMenu"
            class="absolute bottom-full left-0 mb-2 w-52 rounded-xl border border-white/[0.06] bg-surface-50 p-1.5 shadow-xl"
          >
            <NuxtLink
              to="/profile"
              @click="showUserMenu = false"
              class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-zinc-300 transition-colors hover:bg-white/[0.06] hover:text-zinc-100"
            >
              <UserCog class="h-4 w-4 text-muted" />
              Profil
            </NuxtLink>
            <button
              @click="logout()"
              class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-red-400 transition-colors hover:bg-red-500/10"
            >
              <LogOut class="h-4 w-4" />
              Se déconnecter
            </button>
          </div>
        </Transition>

        <button
          @click="toggleUserMenu"
          class="flex w-full items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-white/[0.04]"
        >
          <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-200">
            <User class="h-3.5 w-3.5 text-muted" />
          </div>
          <span v-if="!collapsed && userEmail" class="truncate text-left text-xs font-medium text-zinc-300">{{ userEmail }}</span>
        </button>
      </div>
    </div>
  </aside>
</template>
