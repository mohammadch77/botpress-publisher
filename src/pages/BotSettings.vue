<template>
  <div class="flex flex-col gap-6">
    <Card title="Telegram Bot">
      <template #action>
        <StatusDot :status="settings.telegram.connected ? 'active' : 'inactive'" />
      </template>
      <div class="flex flex-col gap-4">
        <Input v-model="telegramToken" label="Bot Token" type="password" :placeholder="settings.telegram.token_masked || 'Enter token'" />
        <div class="flex items-end gap-2">
          <Input :model-value="settings.telegram.webhook_url" label="Webhook URL" disabled class="flex-1" />
          <Button variant="outline">Copy</Button>
        </div>
        <div>
          <Button variant="primary">Set Webhook</Button>
        </div>
      </div>
    </Card>

    <Card title="Bale Bot">
      <template #action>
        <StatusDot :status="settings.bale.connected ? 'active' : 'inactive'" />
      </template>
      <div class="flex flex-col gap-4">
        <Input v-model="baleToken" label="Bot Token" type="password" :placeholder="settings.bale.token_masked || 'Enter token'" />
        <div class="flex items-end gap-2">
          <Input :model-value="settings.bale.webhook_url" label="Webhook URL" disabled class="flex-1" />
          <Button variant="outline">Copy</Button>
        </div>
        <div>
          <Button variant="primary">Set Webhook</Button>
        </div>
      </div>
    </Card>

    <Card title="Authorized Users">
      <div class="flex flex-col gap-3">
        <p class="text-sm text-slate-500">Telegram/Bale user IDs allowed to control the bot.</p>
        <ul class="flex flex-col gap-2">
          <li
            v-for="user in settings.authorized_users"
            :key="user"
            class="flex items-center justify-between rounded-lg border border-surface-3 px-3 py-2 text-sm"
          >
            {{ user }}
            <Button variant="ghost" size="sm">Remove</Button>
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
const settings = reactive<BotSettings>({
  telegram: { token_masked: '', webhook_url: '', connected: false },
  bale: { token_masked: '', webhook_url: '', connected: false },
  authorized_users: [],
})

onMounted(async () => {
  const { data } = await api.get('/settings')
  Object.assign(settings, data)
})
</script>
