import { defineStore } from 'pinia'
import { botpressConfig } from '@/utils/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    siteUrl: botpressConfig.siteUrl,
    version: botpressConfig.version,
  }),
})
