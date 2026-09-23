<template>
  <div class="flex flex-col gap-6">
    <Card title="Add to Queue">
      <div class="flex flex-wrap items-end gap-3">
        <Input v-model="form.post_id" label="Post ID" placeholder="42" />
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-slate-700">Scheduled At</label>
          <input
            v-model="form.scheduled_at"
            type="datetime-local"
            class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm text-slate-800"
          />
        </div>
        <Select v-model="form.target" label="Target" :options="targetOptions" />
        <Button variant="primary" :loading="adding" @click="handleAdd">Add to Queue</Button>
      </div>
      <p v-if="addMessage" class="mt-3 text-sm" :class="addSuccess ? 'text-emerald-600' : 'text-red-500'">
        {{ addMessage }}
      </p>
    </Card>

    <Card title="Publish Queue">
      <Table :columns="columns" :rows="items" :loading="loading" empty-message="Queue is empty">
        <template #cell-post_title="{ row }">
          {{ row.post_title || `#${row.post_id}` }}
        </template>
        <template #cell-scheduled_at="{ row }">
          {{ formatDate(row.scheduled_at) }}
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="statusVariant(row.status)" dot>{{ row.status }}</Badge>
        </template>
        <template #cell-last_error="{ row }">
          <span class="text-red-500">{{ row.last_error || '—' }}</span>
        </template>
        <template #cell-actions="{ row }">
          <Button
            v-if="row.status === 'pending'"
            variant="danger"
            size="sm"
            @click="handleCancel(row.id)"
          >
            Cancel
          </Button>
          <span v-else>—</span>
        </template>
      </Table>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import { api } from '@/utils/api'
import type { QueueItem } from '@/types'

const loading = ref(true)
const items = ref<QueueItem[]>([])
const adding = ref(false)
const addMessage = ref('')
const addSuccess = ref(false)

const form = reactive({
  post_id: '',
  scheduled_at: '',
  target: 'both',
})

const targetOptions = [
  { label: 'WordPress + Channels', value: 'both' },
  { label: 'WordPress Only', value: 'wordpress' },
  { label: 'Channels Only', value: 'channel' },
]

const columns = [
  { key: 'post_title', label: 'Post' },
  { key: 'scheduled_at', label: 'Scheduled At' },
  { key: 'status', label: 'Status' },
  { key: 'attempts', label: 'Attempts' },
  { key: 'last_error', label: 'Error' },
  { key: 'actions', label: 'Actions' },
]

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function fetchQueue() {
  loading.value = true
  try {
    const { data } = await api.get('/queue')
    items.value = data.items
  } finally {
    loading.value = false
  }
}

function statusVariant(status: string): 'warning' | 'info' | 'success' | 'danger' | 'neutral' {
  const map: Record<string, 'warning' | 'info' | 'success' | 'danger'> = {
    pending: 'warning',
    processing: 'info',
    published: 'success',
    failed: 'danger',
  }
  return map[status] ?? 'neutral'
}

function formatDate(value: string) {
  return new Date(value).toLocaleString()
}

async function handleAdd() {
  const postId = Number(form.post_id)
  if (!postId || !form.scheduled_at) {
    addSuccess.value = false
    addMessage.value = 'شناسه مقاله و زمان انتشار الزامی است.'
    return
  }

  adding.value = true
  addMessage.value = ''
  try {
    const scheduledAt = form.scheduled_at.replace('T', ' ') + ':00'
    const { data } = await api.post('/queue', {
      post_id: postId,
      scheduled_at: scheduledAt,
      target: form.target,
    })
    addSuccess.value = !!data.success
    addMessage.value = data.success ? 'به صف اضافه شد.' : data.message || 'خطا در افزودن به صف.'
    if (data.success) {
      form.post_id = ''
      form.scheduled_at = ''
      await fetchQueue()
    }
  } finally {
    adding.value = false
  }
}

async function handleCancel(id: number) {
  if (!confirm('این زمان‌بندی لغو شود؟')) return
  await api.delete(`/queue/${id}`)
  await fetchQueue()
}

onMounted(() => {
  fetchQueue()
  refreshTimer = setInterval(fetchQueue, 30000)
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>
