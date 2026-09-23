<template>
  <div class="flex h-screen bg-surface-1">
    <div
      v-if="mobileOpen"
      class="fixed inset-0 z-30 bg-slate-900/40 md:hidden"
      @click="mobileOpen = false"
    />
    <aside
      class="flex flex-col border-r border-surface-3 bg-white transition-all"
      :class="[
        appStore.sidebarCollapsed ? 'w-16' : 'w-60',
        'fixed inset-y-0 z-40 md:static',
        mobileOpen ? 'translate-x-0' : 'translate-x-full md:translate-x-0',
      ]"
    >
      <div class="flex items-center gap-2 px-4 py-5">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 text-white">
          <Share2 class="h-4 w-4" />
        </div>
        <span v-if="!appStore.sidebarCollapsed" class="text-sm font-bold text-slate-800">BotPress</span>
      </div>

      <nav class="flex flex-1 flex-col gap-1 px-3">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-surface-1"
          active-class="bg-brand-50 text-brand-600"
        >
          <component :is="item.icon" class="h-4 w-4 shrink-0" />
          <span v-if="!appStore.sidebarCollapsed">{{ item.label }}</span>
        </router-link>
      </nav>

      <div class="flex items-center justify-between px-4 py-4">
        <span v-if="!appStore.sidebarCollapsed" class="text-xs text-slate-400">v{{ version }}</span>
        <button class="text-slate-400 hover:text-slate-600" @click="appStore.toggleSidebar">
          <ChevronsLeft v-if="!appStore.sidebarCollapsed" class="h-4 w-4" />
          <ChevronsRight v-else class="h-4 w-4" />
        </button>
      </div>
    </aside>

    <div class="flex flex-1 flex-col overflow-hidden">
      <header class="flex items-center justify-between border-b border-surface-3 bg-white px-4 py-4 md:px-6">
        <div class="flex items-center gap-3">
          <button class="text-slate-500 md:hidden" @click="mobileOpen = true">
            <Menu class="h-5 w-5" />
          </button>
          <h1 class="text-lg font-semibold text-slate-800">{{ appStore.pageTitle }}</h1>
        </div>
        <div class="flex items-center gap-4">
          <div class="hidden items-center gap-2 text-sm text-slate-500 sm:flex">
            <StatusDot :status="appStore.botConnected ? 'active' : 'inactive'" />
            {{ appStore.botConnected ? 'Bot Connected' : 'Bot Disconnected' }}
          </div>
          <a :href="authStore.siteUrl" target="_blank" class="text-sm text-brand-600 hover:underline">
            View Site
          </a>
        </div>
      </header>

      <main class="flex-1 overflow-y-auto p-4 md:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  LayoutDashboard,
  Radio,
  Bot,
  FileText,
  ScrollText,
  Share2,
  ChevronsLeft,
  ChevronsRight,
  Clock,
  Menu,
} from 'lucide-vue-next'
import { useAppStore } from '@/stores/app'
import { useAuthStore } from '@/stores/auth'
import StatusDot from '@/components/ui/StatusDot.vue'

const appStore = useAppStore()
const authStore = useAuthStore()
const route = useRoute()
const version = authStore.version
const mobileOpen = ref(false)

const navItems = [
  { path: '/', label: 'Dashboard', icon: LayoutDashboard },
  { path: '/channels', label: 'Channels', icon: Radio },
  { path: '/bot-settings', label: 'Bot Settings', icon: Bot },
  { path: '/templates', label: 'Templates', icon: FileText },
  { path: '/logs', label: 'Logs', icon: ScrollText },
  { path: '/queue', label: 'صف انتشار', icon: Clock },
]

watch(
  () => route.meta.title,
  (title) => {
    if (typeof title === 'string') appStore.setPageTitle(title)
  },
  { immediate: true }
)

watch(
  () => route.path,
  () => {
    mobileOpen.value = false
  }
)
</script>
