<script setup lang="ts">
import {
  Shield,
  Vault,
  Activity,
  Settings,
  LogOut,
  ChevronLeft,
  User,
} from 'lucide-vue-next'

const route = useRoute()
const { collapsed, toggle } = useSidebar()
const { user, logout } = useAuth()

const navigation = [
  { name: 'Coffres', icon: Vault, path: '/' },
  { name: 'Activité', icon: Activity, path: '/activity' },
  { name: 'Paramètres', icon: Settings, path: '/settings' },
]

function isActive(path: string): boolean {
  if (path === '/') return route.path === '/'
  return route.path.startsWith(path)
}
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-white/[0.06] bg-surface-50 transition-all duration-300"
    :class="collapsed ? 'w-[68px]' : 'w-[240px]'"
  >
    <!-- Logo -->
    <div class="flex h-14 items-center gap-3 border-b border-white/[0.06] px-4">
      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-accent/10">
        <Shield class="h-4 w-4 text-accent" />
      </div>
      <span
        v-show="!collapsed"
        class="text-[15px] font-semibold tracking-tight text-zinc-100"
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

      <!-- User -->
      <div
        class="flex items-center gap-3 rounded-lg px-3 py-2"
      >
        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-surface-200">
          <User class="h-3.5 w-3.5 text-muted" />
        </div>
        <div v-show="!collapsed" class="flex-1 min-w-0">
          <p class="truncate text-sm font-medium text-zinc-300">{{ user?.email }}</p>
        </div>
        <button
          v-show="!collapsed"
          @click="logout()"
          class="shrink-0 rounded-md p-1 text-muted transition-colors hover:bg-white/[0.06] hover:text-zinc-300"
        >
          <LogOut class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  </aside>
</template>
