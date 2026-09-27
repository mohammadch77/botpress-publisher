<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <p class="text-sm text-slate-500">
        انتشار فوری یا زمان‌بندی مقالات. همه‌چیز با ربات همگام است: زمان‌بندی‌های اینجا در
        <code dir="ltr" class="rounded bg-surface-2 px-1">/pending</code>
        ربات دیده می‌شوند و برعکس.
      </p>
      <router-link to="/queue" class="text-sm text-brand-600 hover:underline">مشاهده صف انتشار ←</router-link>
    </div>

    <Card>
      <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-1 rounded-lg bg-surface-2 p-1">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            type="button"
            class="rounded-md px-3 py-1.5 text-sm transition-colors"
            :class="filters.status === tab.value ? 'bg-white font-medium text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
            @click="setStatus(tab.value)"
          >
            {{ tab.label }}
          </button>
        </div>
        <SearchInput v-model="filters.search" placeholder="جستجوی عنوان..." class="sm:w-72" @search="reload" />
      </div>

      <TableSkeleton v-if="loading && !posts.length" :rows="6" :columns="4" />
      <Table v-else :columns="columns" :rows="posts" empty-message="مقاله‌ای یافت نشد">
        <template #cell-title="{ row }">
          <div class="flex min-w-[14rem] items-center gap-3">
            <img v-if="row.thumbnail" :src="row.thumbnail" alt="" class="h-10 w-10 shrink-0 rounded-md object-cover" />
            <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-surface-2 text-slate-300">
              <FileText class="h-5 w-5" />
            </div>
            <div class="min-w-0">
              <a :href="row.edit_url" target="_blank" class="line-clamp-2 font-medium text-slate-800 hover:text-brand-600">{{ row.title }}</a>
              <p class="text-xs text-slate-400">{{ row.author }} · ویرایش {{ formatSiteDate(row.modified) }}</p>
            </div>
          </div>
        </template>
        <template #cell-status="{ row }">
          <Badge :variant="statusVariant(row.status)" dot>{{ postStatusLabels[row.status] ?? row.status }}</Badge>
        </template>
        <template #cell-queue="{ row }">
          <div v-if="row.queue" class="flex min-w-[9rem] flex-col gap-0.5">
            <span class="flex items-center gap-1 whitespace-nowrap text-sm text-amber-700">
              <Clock class="h-3.5 w-3.5" />
              {{ formatSiteDate(row.queue.scheduled_at) }}
            </span>
            <span class="text-xs text-slate-400">{{ relativeFromNow(row.queue.scheduled_at) }} · {{ targetLabel(row.queue.target) }}</span>
            <button
              type="button"
              class="self-start text-xs text-red-500 hover:underline disabled:opacity-50"
              :disabled="cancellingId === row.queue.id"
              @click="cancelSchedule(row as PostItem)"
            >
              لغو زمان‌بندی
            </button>
          </div>
          <span v-else class="text-xs text-slate-300">—</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-2 whitespace-nowrap">
            <Button variant="primary" size="sm" :icon-left="Zap" @click="open('publish', row as PostItem)">انتشار</Button>
            <Button variant="outline" size="sm" :icon-left="CalendarClock" @click="open('schedule', row as PostItem)">
              {{ row.queue ? 'تغییر زمان' : 'زمان‌بندی' }}
            </Button>
          </div>
        </template>
      </Table>

      <Pagination v-if="totalPages > 1" class="mt-4" :page="page" :total-pages="totalPages" :total="total" @update:page="goToPage" />
    </Card>

    <PublishDialog v-model="dialogOpen" :mode="dialogMode" :post="dialogPost" @done="load" />

    <ConfirmDialog
      v-model="confirmOpen"
      title="لغو زمان‌بندی"
      :message="`زمان‌بندی «${confirmPost?.title}» لغو شود؟`"
      confirm-label="لغو زمان‌بندی"
      cancel-label="بازگشت"
      :loading="cancellingId !== null"
      @confirm="confirmCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { CalendarClock, Clock, FileText, Zap } from 'lucide-vue-next'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import TableSkeleton from '@/components/ui/TableSkeleton.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import SearchInput from '@/components/ui/SearchInput.vue'
import Pagination from '@/components/ui/Pagination.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import PublishDialog from '@/components/PublishDialog.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import { formatSiteDate, postStatusLabels, relativeFromNow, targetOptions } from '@/utils/format'
import type { PostItem } from '@/types'

const toast = useToast()

const statusTabs = [
  { label: 'همه', value: '' },
  { label: 'پیش‌نویس', value: 'draft' },
  { label: 'منتشرشده', value: 'publish' },
  { label: 'در انتظار بررسی', value: 'pending' },
]

const columns = [
  { key: 'title', label: 'مقاله' },
  { key: 'status', label: 'وضعیت' },
  { key: 'queue', label: 'زمان‌بندی' },
  { key: 'actions', label: '' },
]

const posts = ref<PostItem[]>([])
const loading = ref(false)
const page = ref(1)
const total = ref(0)
const totalPages = ref(1)
const filters = reactive({ status: '', search: '' })

const dialogOpen = ref(false)
const dialogMode = ref<'publish' | 'schedule'>('publish')
const dialogPost = ref<PostItem | null>(null)

const confirmOpen = ref(false)
const confirmPost = ref<PostItem | null>(null)
const cancellingId = ref<number | null>(null)

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function load() {
  loading.value = true
  try {
    const { data } = await api.get('/posts', {
      params: { page: page.value, status: filters.status, search: filters.search, per_page: 15 },
    })
    posts.value = data.posts
    total.value = data.total
    totalPages.value = Math.max(1, data.total_pages)
  } catch {
    // interceptor shows a toast
  } finally {
    loading.value = false
  }
}

function reload() {
  page.value = 1
  load()
}

function goToPage(value: number) {
  page.value = value
  load()
}

function setStatus(value: string) {
  filters.status = value
  reload()
}

function open(mode: 'publish' | 'schedule', post: PostItem) {
  dialogMode.value = mode
  dialogPost.value = post
  dialogOpen.value = true
}

function cancelSchedule(post: PostItem) {
  confirmPost.value = post
  confirmOpen.value = true
}

async function confirmCancel() {
  const queueId = confirmPost.value?.queue?.id
  if (!queueId) return
  cancellingId.value = queueId
  try {
    const { data } = await api.delete(`/queue/${queueId}`)
    toast.success(data.message || 'زمان‌بندی لغو شد.')
    confirmOpen.value = false
    await load()
  } catch {
    // interceptor shows a toast
  } finally {
    cancellingId.value = null
  }
}

function statusVariant(status: string): 'success' | 'warning' | 'info' | 'neutral' {
  return ({ publish: 'success', draft: 'neutral', future: 'info', pending: 'warning' } as const)[status as 'publish'] ?? 'neutral'
}

function targetLabel(value: string) {
  return targetOptions.find((o) => o.value === value)?.label ?? value
}

onMounted(() => {
  load()
  refreshTimer = setInterval(() => {
    if (document.visibilityState === 'visible' && !dialogOpen.value) load()
  }, 30000)
})

onUnmounted(() => clearInterval(refreshTimer))
</script>
