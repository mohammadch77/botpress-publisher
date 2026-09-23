import axios from 'axios'
import { useToast } from '@/composables/useToast'

declare global {
  interface Window {
    botpressData?: {
      apiUrl: string
      nonce: string
      siteUrl: string
      version: string
    }
  }
}

const config = window.botpressData ?? {
  apiUrl: '/wp-json/botpress/v1',
  nonce: '',
  siteUrl: '',
  version: 'dev',
}

export const api = axios.create({
  baseURL: config.apiUrl,
  headers: {
    'X-WP-Nonce': config.nonce,
  },
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const toast = useToast()
    if (!error.response) {
      toast.error('خطای اتصال')
    } else {
      const status = error.response.status
      const apiMessage = error.response.data?.message || error.response.data?.code
      if (status >= 500) {
        toast.error('خطای سرور')
      } else if (status >= 400) {
        toast.error(apiMessage || 'خطای درخواست')
      }
    }
    return Promise.reject(error)
  }
)

export const botpressConfig = config
