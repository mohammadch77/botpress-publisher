<template>
  <div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard label="Total Channels" :value="stats.total_channels" :icon="Radio" :loading="loading" />
      <StatCard label="Published Today" :value="stats.published_today" :icon="Send" :loading="loading" />
      <StatCard
        label="Pending in Queue"
        :value="stats.pending_in_queue"
        :icon="Clock"
        :loading="loading"
        class="cursor-pointer transition hover:border-brand-300 hover:shadow-md"
        @click="router.push('/queue')"
      />
      <StatCard label="Failed Today" :value="stats.failed_today" :icon="AlertTriangle" :loading="loading" />
    </div>

    <Card title="Recent Activity">
      <Table
        :columns="columns"
        :rows="stats.recent_activity"
        :loading="loading"
        empty-message="No recent activity yet"
      >
        <template #cell-status="{ row }">
          <Badge :variant="row.status === 'success' ? 'success' : row.status === 'failed' ? 'danger' : 'info'" dot>
            {{ row.status }}
          </Badge>
        </template>
      </Table>
    </Card>

    <Card title="Quick Publish">
      <div class="flex flex-wrap items-end gap-3">
        <Input v-model="quickPostId" label="Post ID" placeholder="42" />
        <Button variant="primary" :icon-left="Send" :loading="publishing" @click="handleQuickPublish">
          Publish Now
        </Button>
      </div>
      <p v-if="quickPublishMessage" class="mt-3 text-sm" :class="quickPublishSuccess ? 'text-emerald-600' : 'text-red-500'">
        {{ quickPublishMessage }}
      </p>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Radio, Send, Clock, AlertTriangle } from 'lucide-vue-next'
import StatCard from '@/components/ui/StatCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import { api } from '@/utils/api'
import type { DashboardStats } from '@/types'

const router = useRouter()
const loading = ref(true)
const stats = reactive<DashboardStats>({
  total_channels: 0,
  published_today: 0,
  pending_in_queue: 0,
  failed_today: 0,
  recent_activity: [],
})

const quickPostId = ref('')
const publishing = ref(false)
const quickPublishMessage = ref('')
const quickPublishSuccess = ref(false)

const columns = [
  { key: 'action', label: 'Action' },
  { key: 'platform', label: 'Platform' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Time' },
]

async function fetchStats() {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard/stats')
    Object.assign(stats, data)
  } finally {
    loading.value = false
  }
}

async function handleQuickPublish() {
  const postId = Number(quickPostId.value)
  if (!postId) {
    quickPublishMessage.value = 'لطفاً شناسه معتبر مقاله را وارد کنید.'
    quickPublishSuccess.value = false
    return
  }

  publishing.value = true
  quickPublishMessage.value = ''
  try {
    const { data } = await api.post(`/posts/${postId}/publish`, { target: 'both' })
    quickPublishSuccess.value = !!data.success
    quickPublishMessage.value = data.success
      ? 'مقاله با موفقیت منتشر شد.'
      : data.wordpress?.error || 'انتشار با خطا مواجه شد.'
    await fetchStats()
  } catch {
    quickPublishSuccess.value = false
    quickPublishMessage.value = 'انتشار با خطا مواجه شد.'
  } finally {
    publishing.value = false
  }
}

onMounted(fetchStats)
</script>
