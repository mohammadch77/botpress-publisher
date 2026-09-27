<template>
  <div class="flex items-center justify-between text-sm text-slate-500">
    <span v-if="total !== undefined">{{ total.toLocaleString('fa-IR') }} مورد — صفحهٔ {{ page.toLocaleString('fa-IR') }} از {{ totalPages.toLocaleString('fa-IR') }}</span>
    <span v-else>صفحهٔ {{ page.toLocaleString('fa-IR') }} از {{ totalPages.toLocaleString('fa-IR') }}</span>
    <div class="flex gap-2">
      <Button variant="outline" size="sm" :disabled="page <= 1" @click="$emit('update:page', page - 1)">قبلی</Button>
      <Button variant="outline" size="sm" :disabled="page >= totalPages" @click="$emit('update:page', page + 1)">بعدی</Button>
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
