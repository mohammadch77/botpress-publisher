<template>
  <div class="overflow-x-auto rounded-xl border border-surface-3">
    <table class="w-full text-left text-sm">
      <thead class="sticky top-0 bg-surface-1">
        <tr>
          <th
            v-for="column in columns"
            :key="column.key"
            class="border-b border-surface-3 px-4 py-3 font-semibold text-slate-600"
          >
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <template v-if="loading">
          <tr v-for="n in 3" :key="`skeleton-${n}`">
            <td v-for="column in columns" :key="column.key" class="px-4 py-3">
              <div class="h-4 w-full animate-pulse rounded bg-surface-2" />
            </td>
          </tr>
        </template>
        <template v-else-if="rows.length === 0">
          <tr>
            <td :colspan="columns.length" class="px-4 py-10">
              <EmptyState :message="emptyMessage" />
            </td>
          </tr>
        </template>
        <template v-else>
          <tr
            v-for="(row, index) in rows"
            :key="index"
            class="border-b border-surface-3 last:border-0 hover:bg-surface-1"
          >
            <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-slate-700">
              <slot :name="`cell-${column.key}`" :row="row">
                {{ row[column.key] }}
              </slot>
            </td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import EmptyState from './EmptyState.vue'

withDefaults(
  defineProps<{
    columns: { key: string; label: string }[]
    rows: Record<string, any>[]
    loading?: boolean
    emptyMessage?: string
  }>(),
  { loading: false, emptyMessage: 'No data available' }
)
</script>
