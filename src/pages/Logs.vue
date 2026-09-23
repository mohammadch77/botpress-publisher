<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div class="flex flex-wrap gap-3">
        <Select v-model="filters.platform" label="Platform" :options="platformOptions" />
        <Select v-model="filters.status" label="Status" :options="statusOptions" />
        <Input v-model="filters.date" label="Date" type="date" />
      </div>
      <div class="flex gap-2">
        <Button variant="outline">Export CSV</Button>
        <Button variant="danger">Clear Logs</Button>
      </div>
    </div>

    <Card>
      <Table :columns="columns" :rows="logs" :loading="loading" empty-message="No logs found">
        <template #cell-status="{ row }">
          <Badge :variant="row.status === 'success' ? 'success' : row.status === 'failed' ? 'danger' : 'info'" dot>
            {{ row.status }}
          </Badge>
        </template>
      </Table>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import Table from '@/components/ui/Table.vue'
import Badge from '@/components/ui/Badge.vue'
import Button from '@/components/ui/Button.vue'
import Select from '@/components/ui/Select.vue'
import Input from '@/components/ui/Input.vue'
import { api } from '@/utils/api'
import type { LogEntry } from '@/types'

const loading = ref(true)
const logs = ref<LogEntry[]>([])
const filters = reactive({ platform: '', status: '', date: '' })

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

onMounted(async () => {
  try {
    const { data } = await api.get('/logs')
    logs.value = data.logs
  } finally {
    loading.value = false
  }
})
</script>
