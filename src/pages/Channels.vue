<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
      <p class="text-sm text-slate-500">Manage the Telegram and Bale channels you publish to.</p>
      <Button variant="primary" :icon-left="Plus" @click="openAddModal">Add Channel</Button>
    </div>

    <Card>
      <Table :columns="columns" :rows="channels" :loading="loading" empty-message="No channels configured yet">
        <template #cell-platform="{ row }">
          <Badge :variant="row.platform === 'telegram' ? 'info' : 'success'">{{ row.platform }}</Badge>
        </template>
        <template #cell-is_active="{ row }">
          <StatusDot :status="row.last_error ? 'error' : row.is_active ? 'active' : 'inactive'" />
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button variant="ghost" size="sm" :loading="testingId === row.id" @click="testChannel(row as Channel)">Test</Button>
            <Button variant="ghost" size="sm" @click="confirmDelete(row as Channel)">Delete</Button>
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
        <p v-if="formError" class="text-sm text-red-500">{{ formError }}</p>
        <div class="flex justify-end gap-2">
          <Button variant="ghost" @click="showModal = false">Cancel</Button>
          <Button variant="primary" :loading="saving" @click="saveChannel">Save</Button>
        </div>
      </div>
    </Modal>

    <Modal v-model="showDeleteModal" title="Delete Channel" size="sm">
      <div class="flex flex-col gap-4">
        <p class="text-sm text-slate-600">
          آیا از حذف کانال «{{ channelToDelete?.name }}» مطمئن هستید؟
        </p>
        <div class="flex justify-end gap-2">
          <Button variant="ghost" @click="showDeleteModal = false">Cancel</Button>
          <Button variant="danger" :loading="deleting" @click="deleteChannel">Delete</Button>
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
const saving = ref(false)
const deleting = ref(false)
const testingId = ref<number | null>(null)
const channels = ref<Channel[]>([])
const showModal = ref(false)
const showDeleteModal = ref(false)
const channelToDelete = ref<Channel | null>(null)
const formError = ref('')
const form = reactive({ name: '', platform: 'telegram', chat_id: '', bot_token: '' })

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'platform', label: 'Platform' },
  { key: 'chat_id', label: 'Chat ID' },
  { key: 'is_active', label: 'Status' },
  { key: 'last_used_at', label: 'Last Used' },
  { key: 'actions', label: '' },
]

async function loadChannels() {
  loading.value = true
  try {
    const { data } = await api.get('/channels')
    channels.value = data.channels
  } finally {
    loading.value = false
  }
}

function openAddModal() {
  form.name = ''
  form.platform = 'telegram'
  form.chat_id = ''
  form.bot_token = ''
  formError.value = ''
  showModal.value = true
}

async function saveChannel() {
  if (!form.name || !form.chat_id || !form.bot_token) {
    formError.value = 'همه فیلدها الزامی هستند.'
    return
  }
  saving.value = true
  formError.value = ''
  try {
    await api.post('/channels', { ...form })
    showModal.value = false
    await loadChannels()
  } catch (e) {
    formError.value = 'خطا در ذخیره کانال.'
  } finally {
    saving.value = false
  }
}

async function testChannel(row: Channel) {
  testingId.value = row.id
  try {
    await api.post(`/channels/${row.id}/test`)
    await loadChannels()
  } finally {
    testingId.value = null
  }
}

function confirmDelete(row: Channel) {
  channelToDelete.value = row
  showDeleteModal.value = true
}

async function deleteChannel() {
  if (!channelToDelete.value) return
  deleting.value = true
  try {
    await api.delete(`/channels/${channelToDelete.value.id}`)
    showDeleteModal.value = false
    await loadChannels()
  } finally {
    deleting.value = false
  }
}

onMounted(loadChannels)
</script>
