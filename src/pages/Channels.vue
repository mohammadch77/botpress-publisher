<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
      <p class="text-sm text-slate-500">Manage the Telegram and Bale channels you publish to.</p>
      <Button variant="primary" :icon-left="Plus" @click="showModal = true">Add Channel</Button>
    </div>

    <Card>
      <Table :columns="columns" :rows="channels" :loading="loading" empty-message="No channels configured yet">
        <template #cell-platform="{ row }">
          <Badge :variant="row.platform === 'telegram' ? 'info' : 'success'">{{ row.platform }}</Badge>
        </template>
        <template #cell-is_active="{ row }">
          <StatusDot :status="row.is_active ? 'active' : 'inactive'" />
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button variant="ghost" size="sm">Edit</Button>
            <Button variant="ghost" size="sm">Delete</Button>
          </div>
        </template>
      </Table>
    </Card>

    <Modal v-model="showModal" title="Add Channel">
      <div class="flex flex-col gap-4">
        <Input v-model="form.name" label="Name" placeholder="My Telegram Channel" />
        <Select
          v-model="form.platform"
          label="Platform"
          :options="[
            { label: 'Telegram', value: 'telegram' },
            { label: 'Bale', value: 'bale' },
          ]"
        />
        <Input v-model="form.chat_id" label="Chat ID" placeholder="-1001234567890" />
        <Input v-model="form.bot_token" label="Bot Token" type="password" placeholder="123456:ABC-DEF..." />
        <div class="flex justify-between">
          <Button variant="outline">Test Connection</Button>
          <div class="flex gap-2">
            <Button variant="ghost" @click="showModal = false">Cancel</Button>
            <Button variant="primary">Save</Button>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import StatusDot from '@/components/ui/StatusDot.vue'
import { api } from '@/utils/api'
import type { Channel } from '@/types'

const loading = ref(true)
const channels = ref<Channel[]>([])
const showModal = ref(false)
const form = reactive({ name: '', platform: 'telegram', chat_id: '', bot_token: '' })

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'platform', label: 'Platform' },
  { key: 'chat_id', label: 'Chat ID' },
  { key: 'is_active', label: 'Status' },
  { key: 'last_used_at', label: 'Last Used' },
  { key: 'actions', label: '' },
]

onMounted(async () => {
  try {
    const { data } = await api.get('/channels')
    channels.value = data.channels
  } finally {
    loading.value = false
  }
})
</script>
