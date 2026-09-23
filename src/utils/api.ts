import axios from 'axios'

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

export const botpressConfig = config
