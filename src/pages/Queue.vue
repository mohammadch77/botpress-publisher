<template>
  <div class="flex flex-col gap-6">
    <Card title="Add to Queue">
      <div class="flex flex-wrap items-end gap-3">
        <Input v-model="form.post_id" label="Post ID" placeholder="42" />
        <DateTimePicker v-model="form.scheduled_at" label="Scheduled At" />
        <Select v-model="form.target" label="Target" :options="targetOptions" />
        <Button variant="primary" :loading="adding" @click="handleAdd">Add to Queue</Button>
      </div>
      <p v-if="addMessage" class="mt-3 text-sm" :class="addSuccess ? 'text-emerald-600' : 'text-red-500'">
        {{ addMessage }}
      </p>
    </Card>

    <Card title="Publish Queue">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div class="flex flex-wrap gap-3">
          <Select v-model="filters.status" label="Status" :options="statusOptions" @update:modelValue="applyFilters" />
        </div>
        <Button v-if="selected.length" variant="danger" size="sm" @click="handleBulkCancel">
          Cancel Selected ({{ selected.length }})
        </Button>
      </div>

      <TableSkeleton v-if="loading" :rows="5" :columns="columns.length + 1" />
      <Table v-else :columns="tableColumns" :rows="filteredItems" empty-message="Queue is empty">
        <template #cell-select="{ row }">
          <input
            v-if="row.status === 'pending'"
            type="checkbox"
            :checked="selected.includes(row.id)"
            @change="toggleSelect(row.id)"
          />
        </template>
        <template #cell-post_title="{ row }">
          <a :href="editPostUrl(row.post_id)" target="_blank" class="text-brand-600 hover:underline">
            {{ row.post_title || `#${row.post_id}` }}
          </a>
        </template>
        <template #cell-scheduled_at="{ row }">
          {{ formatDate(row.scheduled_at) }}
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="statusVariant(row.status)" dot>{{ row.status }}</Badge>
          <span v-if="row.status === 'processing'" class="ml-1 inline-block h-2 w-2 animate-ping rounded-full bg-sky-400" />
        </template>
        <template #cell-last_error="{ row }">
          <span class="text-red-500">{{ row.last_error || '—' }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button
              v-if="row.status === 'pending'"
              variant="danger"
              size="sm"
              @click="handleCancel(row.id)"
            >
              Cancel
            </Button>
            <Button
              v-if="row.status === 'failed'"
              variant="outline"
              size="sm"
              @click="handleRetry(row.id)"
            >
              Retry
            </Button>
            <span v-if="!['pending', 'failed'].includes(row.status)">—</span>
          </div>
        </template>
      </Table>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import TableSkeleton from '@/components/ui/TableSkeleton.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import DateTimePicker from '@/components/ui/DateTimePicker.vue'
import { api, botpressConfig } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { QueueItem } from '@/types'

const toast = useToast()
const loading = ref(true)
const items = ref<QueueItem[]>([])
const adding = ref(false)
const addMessage = ref('')
const addSuccess = ref(false)
const selected = ref<number[]>([])
const filters = reactive({ status: '' })

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

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Processing', value: 'processing' },
  { label: 'Published', value: 'published' },
  { label: 'Failed', value: 'failed' },
]

const columns = [
  { key: 'post_title', label: 'Post' },
  { key: 'scheduled_at', label: 'Scheduled At' },
  { key: 'status', label: 'Status' },
  { key: 'attempts', label: 'Attempts' },
  { key: 'last_error', label: 'Error' },
  { key: 'actions', label: 'Actions' },
]

const tableColumns = [{ key: 'select', label: '' }, ...columns]

const filteredItems = computed(() => {
  let list = items.value
  if (filters.status) list = list.filter((item) => item.status === filters.status)
  return [...list].sort((a, b) => new Date(b.scheduled_at).getTime() - new Date(a.scheduled_at).getTime())
})

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

function applyFilters() {
  selected.value = []
}

function toggleSelect(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
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

function editPostUrl(postId: number) {
  return `${botpressConfig.siteUrl}/wp-admin/post.php?post=${postId}&action=edit`
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
      toast.success('به صف اضافه شد.')
      await fetchQueue()
    }
  } finally {
    adding.value = false
  }
}

async function handleCancel(id: number) {
  if (!confirm('این زمان‌بندی لغو شود؟')) return
  await api.delete(`/queue/${id}`)
  toast.success('لغو شد.')
  await fetchQueue()
}

async function handleRetry(id: number) {
  await api.post(`/queue/${id}/retry`)
  toast.success('برای تلاش مجدد در صف قرار گرفت.')
  await fetchQueue()
}

async function handleBulkCancel() {
  if (!selected.value.length) return
  if (!confirm(`${selected.value.length} مورد لغو شوند؟`)) return
  await Promise.all(selected.value.map((id) => api.delete(`/queue/${id}`)))
  toast.success('موارد انتخابی لغو شدند.')
  selected.value = []
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
