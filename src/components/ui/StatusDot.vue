<template>
  <span class="relative inline-flex h-2.5 w-2.5">
    <span
      v-if="status === 'active'"
      class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
    />
    <span class="relative inline-flex h-2.5 w-2.5 rounded-full" :class="colorClass" />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{ status?: 'active' | 'inactive' | 'error' | 'warning' }>(),
  { status: 'inactive' }
)

const colorClass = computed(() => ({
  active: 'bg-emerald-500',
  inactive: 'bg-slate-300',
  error: 'bg-red-500',
  warning: 'bg-amber-500',
}[props.status]))
</script>
