<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="flex flex-wrap gap-3">
        <Select v-model="filters.platform" label="Platform" :options="platformOptions" @update:modelValue="reload" />
        <Select v-model="filters.status" label="Status" :options="statusOptions" @update:modelValue="reload" />
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-slate-700">From</label>
          <input v-model="filters.from" type="date" class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm" @change="reload" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-slate-700">To</label>
          <input v-model="filters.to" type="date" class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm" @change="reload" />
        </div>
        <div class="flex flex-col gap-1.5">
          <label class="text-sm font-medium text-slate-700">Search</label>
          <SearchInput v-model="filters.search" placeholder="Search message..." @search="reload" />
        </div>
      </div>
      <div class="flex gap-2">
        <Button variant="outline" @click="handleExport">Export CSV</Button>
        <Button variant="danger" @click="handleClear">Clear Logs</Button>
      </div>
    </div>

    <Card>
      <TableSkeleton v-if="loading" :rows="5" :columns="columns.length" />
      <Table v-else :columns="columns" :rows="logs" empty-message="No logs found">
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
        <template #cell-message="{ row }">
          <button class="text-left hover:underline" @click="openDetail(row as LogEntry)">{{ row.message }}</button>
        </template>
      </Table>

      <Pagination class="mt-4" :page="page" :total-pages="totalPages" :total="total" @update:page="changePage" />
    </Card>

    <Modal v-model="showDetail" title="Log Detail">
      <pre v-if="selectedLog" class="max-h-96 overflow-auto whitespace-pre-wrap rounded-lg bg-surface-1 p-3 text-xs text-slate-700">{{ JSON.stringify(selectedLog, null, 2) }}</pre>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import TableSkeleton from '@/components/ui/TableSkeleton.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Pagination from '@/components/ui/Pagination.vue'
import Modal from '@/components/ui/Modal.vue'
import { api, botpressConfig } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { LogEntry } from '@/types'

const toast = useToast()
const loading = ref(true)
const logs = ref<LogEntry[]>([])
const total = ref(0)
const page = ref(1)
const perPage = 20
const filters = reactive({ platform: '', status: '', from: '', to: '', search: '' })
const showDetail = ref(false)
const selectedLog = ref<LogEntry | null>(null)

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
        from: filters.from || undefined,
        to: filters.to || undefined,
        search: filters.search || undefined,
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

function openDetail(row: LogEntry) {
  selectedLog.value = row
  showDetail.value = true
}

async function handleClear() {
  if (!confirm('همه لاگ‌ها حذف شوند؟ این عمل قابل بازگشت نیست.')) return
  await api.delete('/logs')
  toast.success('لاگ‌ها حذف شدند.')
  reload()
}

function handleExport() {
  const params = new URLSearchParams()
  if (filters.platform) params.set('platform', filters.platform)
  if (filters.status) params.set('status', filters.status)
  if (filters.from) params.set('from', filters.from)
  if (filters.to) params.set('to', filters.to)
  params.set('_wpnonce', botpressConfig.nonce)
  const url = `${botpressConfig.apiUrl}/logs/export?${params.toString()}`
  window.open(url, '_blank')
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
