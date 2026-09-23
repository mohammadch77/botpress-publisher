<template>
  <div class="flex flex-col gap-6">
    <Card title="Telegram Bot">
      <template #action>
        <StatusDot :status="settings.telegram.connected ? 'active' : 'inactive'" />
      </template>
      <div class="flex flex-col gap-4">
        <Input v-model="telegramToken" label="Bot Token" type="password" :placeholder="settings.telegram.token_masked || 'Enter token'" />
        <div>
          <Button variant="primary" :loading="savingTelegram" @click="saveToken('telegram')">ذخیره توکن</Button>
        </div>
        <div class="flex items-end gap-2">
          <Input :model-value="settings.telegram.webhook_url" label="Webhook URL" disabled class="flex-1" />
          <Button variant="outline" @click="copyUrl(settings.telegram.webhook_url)">Copy</Button>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="primary" :loading="settingWebhook.telegram" :disabled="!settings.telegram.has_token" @click="setWebhook('telegram')">
            Set Webhook
          </Button>
          <span class="text-sm" :class="settings.telegram.webhook_set ? 'text-emerald-600' : 'text-slate-400'">
            {{ settings.telegram.webhook_set ? '✅ فعال' : '❌ غیرفعال' }}
          </span>
        </div>
      </div>
    </Card>

    <Card title="Bale Bot">
      <template #action>
        <StatusDot :status="settings.bale.connected ? 'active' : 'inactive'" />
      </template>
      <div class="flex flex-col gap-4">
        <Input v-model="baleToken" label="Bot Token" type="password" :placeholder="settings.bale.token_masked || 'Enter token'" />
        <div>
          <Button variant="primary" :loading="savingBale" @click="saveToken('bale')">ذخیره توکن</Button>
        </div>
        <div class="flex items-end gap-2">
          <Input :model-value="settings.bale.webhook_url" label="Webhook URL" disabled class="flex-1" />
          <Button variant="outline" @click="copyUrl(settings.bale.webhook_url)">Copy</Button>
        </div>
        <div class="flex items-center gap-3">
          <Button variant="primary" :loading="settingWebhook.bale" :disabled="!settings.bale.has_token" @click="setWebhook('bale')">
            Set Webhook
          </Button>
          <span class="text-sm" :class="settings.bale.webhook_set ? 'text-emerald-600' : 'text-slate-400'">
            {{ settings.bale.webhook_set ? '✅ فعال' : '❌ غیرفعال' }}
          </span>
        </div>
      </div>
    </Card>

    <Card title="Authorized Users">
      <div class="flex flex-col gap-3">
        <p class="text-sm text-slate-500">Telegram/Bale user IDs allowed to control the bot.</p>
        <div class="flex items-end gap-2">
          <Input v-model="newUserId" label="User ID" placeholder="123456789" class="flex-1" />
          <Button variant="outline" @click="addUser">افزودن</Button>
        </div>
        <ul class="flex flex-col gap-2">
          <li
            v-for="user in settings.authorized_users"
            :key="user"
            class="flex items-center justify-between rounded-lg border border-surface-3 px-3 py-2 text-sm"
          >
            {{ user }}
            <Button variant="ghost" size="sm" @click="removeUser(user)">Remove</Button>
          </li>
        </ul>
        <div v-if="settings.authorized_users.length === 0">
          <EmptyState message="No authorized users yet" />
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'
import StatusDot from '@/components/ui/StatusDot.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { api } from '@/utils/api'
import type { BotSettings } from '@/types'

const telegramToken = ref('')
const baleToken = ref('')
const newUserId = ref('')
const savingTelegram = ref(false)
const savingBale = ref(false)
const settingWebhook = reactive({ telegram: false, bale: false })

const settings = reactive<BotSettings>({
  telegram: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
  bale: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
  authorized_users: [],
  notify_on_publish: true,
  notify_on_fail: true,
})

async function loadSettings() {
  const { data } = await api.get('/settings')
  Object.assign(settings, data)
}

async function saveToken(platform: 'telegram' | 'bale') {
  const token = platform === 'telegram' ? telegramToken.value : baleToken.value
  if (!token) return

  const savingRef = platform === 'telegram' ? savingTelegram : savingBale
  savingRef.value = true
  try {
    await api.post('/settings', { [`${platform}_token`]: token })
    if (platform === 'telegram') telegramToken.value = ''
    else baleToken.value = ''
    await loadSettings()
  } finally {
    savingRef.value = false
  }
}

async function setWebhook(platform: 'telegram' | 'bale') {
  settingWebhook[platform] = true
  try {
    await api.post('/webhook/set', { platform })
    await loadSettings()
  } finally {
    settingWebhook[platform] = false
  }
}

async function copyUrl(url: string) {
  try {
    await navigator.clipboard.writeText(url)
  } catch {
    // clipboard unavailable, ignore
  }
}

async function addUser() {
  if (!newUserId.value.trim()) return
  const users = [...settings.authorized_users, newUserId.value.trim()]
  await api.post('/settings', { authorized_users: users })
  newUserId.value = ''
  await loadSettings()
}

async function removeUser(user: string) {
  const users = settings.authorized_users.filter((u) => u !== user)
  await api.post('/settings', { authorized_users: users })
  await loadSettings()
}

onMounted(loadSettings)
</script>
