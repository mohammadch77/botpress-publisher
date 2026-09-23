import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/Dashboard.vue'),
    meta: { title: 'Dashboard' },
  },
  {
    path: '/channels',
    name: 'channels',
    component: () => import('@/pages/Channels.vue'),
    meta: { title: 'Channels' },
  },
  {
    path: '/bot-settings',
    name: 'bot-settings',
    component: () => import('@/pages/BotSettings.vue'),
    meta: { title: 'Bot Settings' },
  },
  {
    path: '/templates',
    name: 'templates',
    component: () => import('@/pages/Templates.vue'),
    meta: { title: 'Templates' },
  },
  {
    path: '/logs',
    name: 'logs',
    component: () => import('@/pages/Logs.vue'),
    meta: { title: 'Logs' },
  },
  {
    path: '/queue',
    name: 'queue',
    component: () => import('@/pages/Queue.vue'),
    meta: { title: 'Queue' },
  },
]

export const router = createRouter({
  history: createWebHashHistory(),
  routes,
})
