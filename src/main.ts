import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from '@/App.vue'
import { router } from '@/router'
import '@/assets/main.css'

const app = createApp(App)

app.config.errorHandler = (err, instance, info) => {
  console.error('[BotPress] Vue error:', err, info)
  const el = document.getElementById('botpress-app')
  if (el && !el.querySelector('[data-botpress-fatal]')) {
    const box = document.createElement('div')
    box.setAttribute('data-botpress-fatal', '1')
    box.style.cssText = 'padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;font-family:monospace;white-space:pre-wrap;'
    box.textContent = 'BotPress UI error: ' + (err instanceof Error ? err.message + '\n' + err.stack : String(err)) + '\n\ninfo: ' + info
    el.appendChild(box)
  }
}

app.use(createPinia())
app.use(router)

router.isReady().then(() => {
  try {
    app.mount('#botpress-app')
  } catch (err) {
    app.config.errorHandler?.(err, null as any, 'mount')
  }
})
