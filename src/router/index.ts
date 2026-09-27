import { createRouter, createWebHashHistory } from 'vue-router'

const routes = [
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/Dashboard.vue'),
    meta: { title: 'پیشخوان' },
  },
  {
    path: '/posts',
    name: 'posts',
    component: () => import('@/pages/Posts.vue'),
    meta: { title: 'مقالات و انتشار' },
  },
  {
    path: '/channels',
    name: 'channels',
    component: () => import('@/pages/Channels.vue'),
    meta: { title: 'کانال‌ها' },
  },
  {
    path: '/bot-settings',
    name: 'bot-settings',
    component: () => import('@/pages/BotSettings.vue'),
    meta: { title: 'تنظیمات ربات' },
  },
  {
    path: '/templates',
    name: 'templates',
    component: () => import('@/pages/Templates.vue'),
    meta: { title: 'قالب پیام‌ها' },
  },
  {
    path: '/logs',
    name: 'logs',
    component: () => import('@/pages/Logs.vue'),
    meta: { title: 'گزارش‌ها' },
  },
  {
    path: '/queue',
    name: 'queue',
    component: () => import('@/pages/Queue.vue'),
    meta: { title: 'صف انتشار' },
  },
  {
    path: '/ai-writer',
    name: 'ai-writer',
    component: () => import('@/pages/AiWriter.vue'),
    meta: { title: 'تولید مقاله با هوش مصنوعی' },
  },
  {
    path: '/ai-settings',
    name: 'ai-settings',
    component: () => import('@/pages/AiSettings.vue'),
    meta: { title: 'تنظیمات هوش مصنوعی' },
  },
]

export const router = createRouter({
  history: createWebHashHistory(),
  routes,
})
