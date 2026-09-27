import { defineStore } from 'pinia'

export const useAppStore = defineStore('app', {
  state: () => ({
    sidebarCollapsed: false,
    pageTitle: 'پیشخوان',
    botConnected: false,
  }),
  actions: {
    toggleSidebar() {
      this.sidebarCollapsed = !this.sidebarCollapsed
    },
    setPageTitle(title: string) {
      this.pageTitle = title
    },
  },
})
