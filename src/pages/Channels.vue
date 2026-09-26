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
          <span :title="row.last_error || ''" class="flex items-center gap-2">
            <StatusDot :status="row.last_error ? 'error' : row.last_used_at ? 'active' : 'inactive'" />
            <span class="text-xs text-slate-400">
              {{ row.last_error ? 'خطا' : row.last_used_at ? formatDate(row.last_used_at) : 'تست نشده' }}
            </span>
          </span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex flex-col gap-1">
            <div class="flex gap-2">
              <Button variant="ghost" size="sm" :loading="testingId === row.id" @click="testChannel(row as Channel)">Test</Button>
              <Button variant="ghost" size="sm" @click="confirmDelete(row as Channel)">Delete</Button>
            </div>
            <p v-if="testResults[row.id]" class="text-xs" :class="testResults[row.id].success ? 'text-emerald-600' : 'text-red-500'">
              {{ testResults[row.id].success ? `متصل: @${testResults[row.id].bot_username || ''}` : testResults[row.id].message }}
            </p>
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

    <ConfirmDialog
      v-model="showDeleteModal"
      title="Delete Channel"
      :message="`آیا از حذف کانال «${channelToDelete?.name}» مطمئن هستید؟`"
      :loading="deleting"
      @confirm="deleteChannel"
    />
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
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { Channel } from '@/types'

const toast = useToast()
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
const testResults = reactive<Record<number, { success: boolean; message?: string; bot_username?: string }>>({})

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
  if (form.platform === 'telegram' && !form.chat_id.startsWith('-')) {
    formError.value = 'شناسه کانال تلگرام باید با - شروع شود.'
    return
  }
  saving.value = true
  formError.value = ''
  try {
    await api.post('/channels', { ...form })
    showModal.value = false
    toast.success('کانال با موفقیت اضافه شد.')
    await loadChannels()
  } catch (e: any) {
    formError.value = e?.response?.data?.message || 'خطا در ذخیره کانال.'
  } finally {
    saving.value = false
  }
}

async function testChannel(row: Channel) {
  testingId.value = row.id
  try {
    const { data } = await api.post(`/channels/${row.id}/test`)
    testResults[row.id] = {
      success: data.success,
      message: data.result?.description || 'اتصال ناموفق',
      bot_username: data.result?.result?.username,
    }
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
    toast.success('کانال حذف شد.')
    await loadChannels()
  } finally {
    deleting.value = false
  }
}

function formatDate(value: string) {
  return new Date(value).toLocaleString()
}

onMounted(loadChannels)
</script>
