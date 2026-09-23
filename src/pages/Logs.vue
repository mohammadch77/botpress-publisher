<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="flex flex-wrap gap-3">
        <Select v-model="filters.platform" label="Platform" :options="platformOptions" @update:modelValue="reload" />
        <Select v-model="filters.status" label="Status" :options="statusOptions" @update:modelValue="reload" />
      </div>
      <div class="flex gap-2">
        <Button variant="danger" @click="handleClear">Clear Logs</Button>
      </div>
    </div>

    <Card>
      <Table :columns="columns" :rows="logs" :loading="loading" empty-message="No logs found">
        <template #cell-platform="{ row }">
          <Badge v-if="row.platform" variant="neutral">{{ row.platform }}</Badge>
          <span v-else>—</span>
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="row.status === 'success' ? 'success' : row.status === 'failed' ? 'danger' : 'info'" dot>
            {{ row.status }}
          </Badge>
        </template>
        <template #cell-created_at="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </Table>

      <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
        <span>{{ total }} log(s) — page {{ page }} of {{ totalPages }}</span>
        <div class="flex gap-2">
          <Button variant="outline" size="sm" :disabled="page <= 1" @click="changePage(page - 1)">Prev</Button>
          <Button variant="outline" size="sm" :disabled="page >= totalPages" @click="changePage(page + 1)">Next</Button>
        </div>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import { api } from '@/utils/api'
import type { LogEntry } from '@/types'

const loading = ref(true)
const logs = ref<LogEntry[]>([])
const total = ref(0)
const page = ref(1)
const perPage = 20
const filters = reactive({ platform: '', status: '' })

const platformOptions = [
  { label: 'All Platforms', value: '' },
  { label: 'Telegram', value: 'telegram' },
  { label: 'Bale', value: 'bale' },
  { label: 'WordPress', value: 'wordpress' },
]

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Success', value: 'success' },
  { label: 'Failed', value: 'failed' },
  { label: 'Info', value: 'info' },
]

const columns = [
  { key: 'action', label: 'Action' },
  { key: 'platform', label: 'Platform' },
  { key: 'status', label: 'Status' },
  { key: 'message', label: 'Message' },
  { key: 'created_at', label: 'Time' },
]

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / perPage)))

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function fetchLogs() {
  loading.value = true
  try {
    const { data } = await api.get('/logs', {
      params: {
        platform: filters.platform || undefined,
        status: filters.status || undefined,
        page: page.value,
        per_page: perPage,
      },
    })
    logs.value = data.logs
    total.value = data.total
  } finally {
    loading.value = false
  }
}

function reload() {
  page.value = 1
  fetchLogs()
}

function changePage(next: number) {
  if (next < 1 || next > totalPages.value) return
  page.value = next
  fetchLogs()
}

async function handleClear() {
  if (!confirm('همه لاگ‌ها حذف شوند؟ این عمل قابل بازگشت نیست.')) return
  await api.delete('/logs')
  reload()
}

function formatDate(value: string) {
  return new Date(value).toLocaleString()
}

onMounted(() => {
  fetchLogs()
  refreshTimer = setInterval(fetchLogs, 30000)
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>
