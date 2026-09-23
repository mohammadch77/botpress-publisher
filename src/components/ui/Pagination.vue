<template>
  <div class="flex items-center justify-between text-sm text-slate-500">
    <span v-if="total !== undefined">{{ total }} item(s) — page {{ page }} of {{ totalPages }}</span>
    <span v-else>page {{ page }} of {{ totalPages }}</span>
    <div class="flex gap-2">
      <Button variant="outline" size="sm" :disabled="page <= 1" @click="$emit('update:page', page - 1)">Previous</Button>
      <Button variant="outline" size="sm" :disabled="page >= totalPages" @click="$emit('update:page', page + 1)">Next</Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from './Button.vue'

defineProps<{
  page: number
  totalPages: number
  total?: number
}>()

defineEmits<{ (e: 'update:page', value: number): void }>()
</script>
