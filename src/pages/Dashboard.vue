<template>
  <div class="flex flex-col gap-6">
    <Card title="System Status">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="flex items-center gap-2 text-sm">
          <StatusDot :status="botSettings.telegram.connected ? 'active' : 'inactive'" />
          Telegram Bot — {{ botSettings.telegram.connected ? 'متصل' : 'قطع' }}
        </div>
        <div class="flex items-center gap-2 text-sm">
          <StatusDot :status="botSettings.bale.connected ? 'active' : 'inactive'" />
          Bale Bot — {{ botSettings.bale.connected ? 'متصل' : 'قطع' }}
        </div>
      </div>
    </Card>

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
      <StatCard
        label="Failed Today"
        :value="stats.failed_today"
        :icon="AlertTriangle"
        :loading="loading"
        :class="stats.failed_today > 0 ? 'border-red-300' : ''"
      />
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

    <Card title="Quick Actions">
      <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-end gap-3">
          <Input v-model="quickPostId" label="Post ID" placeholder="42" />
          <Button variant="primary" :icon-left="Send" :loading="publishing" @click="handleQuickPublish">
            Publish Now
          </Button>
        </div>
        <p v-if="quickPublishMessage" class="text-sm" :class="quickPublishSuccess ? 'text-emerald-600' : 'text-red-500'">
          {{ quickPublishMessage }}
        </p>

        <div class="flex flex-wrap items-end gap-3 border-t border-surface-3 pt-4">
          <Input v-model="scheduleForm.postId" label="Post ID" placeholder="42" />
          <DateTimePicker v-model="scheduleForm.scheduledAt" label="Scheduled At" />
          <Button variant="secondary" :loading="scheduling" @click="handleQuickSchedule">Schedule</Button>
          <router-link to="/queue" class="text-sm text-brand-600 hover:underline">View Queue →</router-link>
        </div>
        <p v-if="scheduleMessage" class="text-sm" :class="scheduleSuccess ? 'text-emerald-600' : 'text-red-500'">
          {{ scheduleMessage }}
        </p>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Radio, Send, Clock, AlertTriangle } from 'lucide-vue-next'
import StatCard from '@/components/ui/StatCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import DateTimePicker from '@/components/ui/DateTimePicker.vue'
import StatusDot from '@/components/ui/StatusDot.vue'
import { api } from '@/utils/api'
import { useToast } from '@/composables/useToast'
import type { BotSettings, DashboardStats } from '@/types'

const toast = useToast()
const router = useRouter()
const loading = ref(true)
const stats = reactive<DashboardStats>({
  total_channels: 0,
  published_today: 0,
  pending_in_queue: 0,
  failed_today: 0,
  recent_activity: [],
})

const botSettings = reactive<Pick<BotSettings, 'telegram' | 'bale'>>({
  telegram: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
  bale: { token_masked: '', has_token: false, webhook_url: '', webhook_set: false, connected: false },
})

const quickPostId = ref('')
const publishing = ref(false)
const quickPublishMessage = ref('')
const quickPublishSuccess = ref(false)

const scheduleForm = reactive({ postId: '', scheduledAt: '' })
const scheduling = ref(false)
const scheduleMessage = ref('')
const scheduleSuccess = ref(false)

const columns = [
  { key: 'action', label: 'Action' },
  { key: 'platform', label: 'Platform' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Time' },
]

let refreshTimer: ReturnType<typeof setInterval> | undefined

async function fetchStats() {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard/stats')
    Object.assign(stats, data)
  } finally {
    loading.value = false
  }
}

async function fetchBotSettings() {
  const { data } = await api.get('/settings')
  botSettings.telegram = data.telegram
  botSettings.bale = data.bale
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
    if (data.success) toast.success('مقاله منتشر شد.')
    await fetchStats()
  } catch {
    quickPublishSuccess.value = false
    quickPublishMessage.value = 'انتشار با خطا مواجه شد.'
  } finally {
    publishing.value = false
  }
}

async function handleQuickSchedule() {
  const postId = Number(scheduleForm.postId)
  if (!postId || !scheduleForm.scheduledAt) {
    scheduleSuccess.value = false
    scheduleMessage.value = 'شناسه مقاله و زمان الزامی است.'
    return
  }
  scheduling.value = true
  scheduleMessage.value = ''
  try {
    const scheduledAt = scheduleForm.scheduledAt.replace('T', ' ') + ':00'
    const { data } = await api.post('/queue', { post_id: postId, scheduled_at: scheduledAt, target: 'both' })
    scheduleSuccess.value = !!data.success
    scheduleMessage.value = data.success ? 'زمان‌بندی شد.' : data.message || 'خطا در زمان‌بندی.'
    if (data.success) {
      toast.success('به صف زمان‌بندی اضافه شد.')
      scheduleForm.postId = ''
      scheduleForm.scheduledAt = ''
      await fetchStats()
    }
  } finally {
    scheduling.value = false
  }
}

onMounted(() => {
  fetchStats()
  fetchBotSettings()
  refreshTimer = setInterval(() => {
    fetchStats()
    fetchBotSettings()
  }, 60000)
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>
