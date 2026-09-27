<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-100 bg-brand-50 px-5 py-4">
      <p class="text-sm text-slate-600">
        این صف بین پنل و ربات مشترک است. برای زمان‌بندی یا انتشار فوری یک مقاله، از صفحه مقالات استفاده کنید.
      </p>
      <router-link to="/posts">
        <Button variant="primary" size="sm" :icon-left="CalendarClock">زمان‌بندی جدید</Button>
      </router-link>
    </div>

    <Card title="صف انتشار">
      <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <Select v-model="filters.status" label="وضعیت" :options="statusOptions" @update:model-value="selected = []" />
        <Button v-if="selected.length" variant="danger" size="sm" :loading="bulkCancelling" @click="handleBulkCancel">
          لغو موارد انتخابی ({{ selected.length }})
        </Button>
      </div>

      <TableSkeleton v-if="loading && !items.length" :rows="5" :columns="tableColumns.length" />
      <Table v-else :columns="tableColumns" :rows="filteredItems" empty-message="صف انتشار خالی است">
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
          <div class="flex flex-col">
            <span>{{ formatSiteDate(row.scheduled_at) }}</span>
            <span v-if="row.status === 'pending'" class="text-xs text-slate-400">{{ relativeFromNow(row.scheduled_at) }}</span>
          </div>
        </template>
        <template #cell-publish_target="{ row }">
          <span class="text-xs text-slate-500">{{ targetLabel(row.publish_target) }}</span>
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="statusVariant(row.status)" dot>{{ statusLabel(row.status) }}</Badge>
        </template>
        <template #cell-last_error="{ row }">
          <span class="text-xs" :class="row.status === 'failed' ? 'text-red-500' : 'text-slate-400'">{{ row.last_error || '—' }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex gap-2">
            <Button v-if="row.status === 'pending'" variant="danger" size="sm" :loading="busyId === row.id" @click="handleCancel(row.id)">لغو</Button>
            <Button v-if="row.status === 'failed'" variant="outline" size="sm" :loading="busyId === row.id" @click="handleRetry(row.id)">تلاش مجدد</Button>
            <span v-if="!['pending', 'failed'].includes(row.status)" class="text-slate-300">—</span>
          </div>
        </template>
      </Table>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { CalendarClock } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import TableSkeleton from '@/components/ui/TableSkeleton.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import { api, botpressConfig } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import { formatSiteDate, parseSiteDate, relativeFromNow, targetOptions } from '@/utils/format'
import type { QueueItem } from '@/types'

const toast = useToast()
const loading = ref(true)
const items = ref<QueueItem[]>([])
const selected = ref<number[]>([])
const busyId = ref<number | null>(null)
const bulkCancelling = ref(false)
const filters = reactive({ status: '' })

const statusOptions = [
  { label: 'همه وضعیت‌ها', value: '' },
  { label: 'در انتظار', value: 'pending' },
  { label: 'در حال انتشار', value: 'processing' },
  { label: 'منتشرشده', value: 'published' },
  { label: 'ناموفق', value: 'failed' },
  { label: 'لغوشده', value: 'cancelled' },
]

const tableColumns = [
  { key: 'select', label: '' },
  { key: 'post_title', label: 'مقاله' },
  { key: 'scheduled_at', label: 'زمان انتشار' },
  { key: 'publish_target', label: 'مقصد' },
  { key: 'status', label: 'وضعیت' },
  { key: 'last_error', label: 'توضیح' },
  { key: 'actions', label: '' },
]

const filteredItems = computed(() => {
  let list = items.value
  if (filters.status) list = list.filter((item) => item.status === filters.status)
  // Pending first (soonest on top), then history (newest on top).
  return [...list].sort((a, b) => {
    const ap = a.status === 'pending' ? 0 : 1
    const bp = b.status === 'pending' ? 0 : 1
    if (ap !== bp) return ap - bp
    const diff = parseSiteDate(a.scheduled_at).getTime() - parseSiteDate(b.scheduled_at).getTime()
    return ap === 0 ? diff : -diff
  })
})

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function fetchQueue() {
  try {
    const { data } = await api.get('/queue')
    items.value = data.items
  } catch {
    // interceptor shows a toast
  } finally {
    loading.value = false
  }
}

function toggleSelect(id: number) {
  const idx = selected.value.indexOf(id)
  if (idx === -1) selected.value.push(id)
  else selected.value.splice(idx, 1)
}

function statusVariant(status: string): 'warning' | 'info' | 'success' | 'danger' | 'neutral' {
  return ({ pending: 'warning', processing: 'info', published: 'success', failed: 'danger' } as const)[status as 'pending'] ?? 'neutral'
}

function statusLabel(status: string) {
  return { pending: 'در انتظار', processing: 'در حال انتشار', published: 'منتشرشده', failed: 'ناموفق', cancelled: 'لغوشده' }[status] ?? status
}

function targetLabel(value: string) {
  return targetOptions.find((o) => o.value === value)?.label ?? value
}

function editPostUrl(postId: number) {
  return `${botpressConfig.siteUrl}/wp-admin/post.php?post=${postId}&action=edit`
}

async function handleCancel(id: number) {
  if (!confirm('این زمان‌بندی لغو شود؟')) return
  busyId.value = id
  try {
    const { data } = await api.delete(`/queue/${id}`)
    toast.success(data.message || 'لغو شد.')
    await fetchQueue()
  } catch {
    // interceptor shows a toast
  } finally {
    busyId.value = null
  }
}

async function handleRetry(id: number) {
  busyId.value = id
  try {
    await api.post(`/queue/${id}/retry`)
    toast.success('برای تلاش مجدد در صف قرار گرفت.')
    await fetchQueue()
  } catch {
    // interceptor shows a toast
  } finally {
    busyId.value = null
  }
}

async function handleBulkCancel() {
  if (!confirm(`${selected.value.length} مورد لغو شوند؟`)) return
  bulkCancelling.value = true
  try {
    await Promise.allSettled(selected.value.map((id) => api.delete(`/queue/${id}`)))
    toast.success('موارد انتخابی لغو شدند.')
    selected.value = []
    await fetchQueue()
  } finally {
    bulkCancelling.value = false
  }
}

onMounted(() => {
  fetchQueue()
  refreshTimer = setInterval(() => {
    if (document.visibilityState === 'visible') fetchQueue()
  }, 20000)
})

onUnmounted(() => clearInterval(refreshTimer))
</script>
