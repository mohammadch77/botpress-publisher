<template>
  <div class="flex flex-col gap-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <StatCard label="Total Channels" :value="stats.total_channels" :icon="Radio" :loading="loading" />
      <StatCard label="Published Today" :value="stats.published_today" :icon="Send" :loading="loading" />
      <StatCard label="Pending in Queue" :value="stats.pending_in_queue" :icon="Clock" :loading="loading" />
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

    <Card title="Quick Actions">
      <div class="flex gap-3">
        <Button variant="primary" :icon-left="Send">Publish Now</Button>
        <Button variant="outline" :icon-left="ListOrdered">View Queue</Button>
      </div>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { Radio, Send, Clock, AlertTriangle, ListOrdered } from 'lucide-vue-next'
import StatCard from '@/components/ui/StatCard.vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import { api } from '@/utils/api'
import type { DashboardStats } from '@/types'

const loading = ref(true)
const stats = reactive<DashboardStats>({
  total_channels: 0,
  published_today: 0,
  pending_in_queue: 0,
  failed_today: 0,
  recent_activity: [],
})

const columns = [
  { key: 'action', label: 'Action' },
  { key: 'platform', label: 'Platform' },
  { key: 'status', label: 'Status' },
  { key: 'created_at', label: 'Time' },
]

onMounted(async () => {
  try {
    const { data } = await api.get('/dashboard/stats')
    Object.assign(stats, data)
  } finally {
    loading.value = false
  }
})
</script>
