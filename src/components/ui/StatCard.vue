<template>
  <div class="rounded-xl border border-surface-3 bg-white p-5 shadow-sm">
    <div v-if="loading" class="flex flex-col gap-3">
      <div class="h-4 w-24 animate-pulse rounded bg-surface-2" />
      <div class="h-8 w-16 animate-pulse rounded bg-surface-2" />
    </div>
    <div v-else class="flex items-start justify-between">
      <div>
        <p class="text-sm text-slate-500">{{ label }}</p>
        <p class="mt-1 text-2xl font-bold text-slate-800">{{ value }}</p>
        <p v-if="trend !== undefined" class="mt-1 text-xs" :class="trend >= 0 ? 'text-emerald-600' : 'text-red-500'">
          {{ trend >= 0 ? '▲' : '▼' }} {{ Math.abs(trend) }}%
        </p>
      </div>
      <div v-if="icon" class="rounded-lg bg-brand-50 p-2 text-brand-600">
        <component :is="icon" class="h-5 w-5" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue'

withDefaults(
  defineProps<{
    label: string
    value: string | number
    icon?: Component
    trend?: number
    loading?: boolean
  }>(),
  { loading: false }
)
</script>
