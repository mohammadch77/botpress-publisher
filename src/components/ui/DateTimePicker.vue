<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-slate-700">{{ label }}</label>
    <input
      type="datetime-local"
      :value="modelValue"
      class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm text-slate-800"
      :class="error ? 'border-red-400' : ''"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="displayValue" class="text-xs text-slate-400">{{ displayValue }}</p>
    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    label?: string
    error?: string
  }>(),
  { modelValue: '' }
)

defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const displayValue = computed(() => {
  if (!props.modelValue) return ''
  const date = new Date(props.modelValue)
  if (Number.isNaN(date.getTime())) return ''
  return new Intl.DateTimeFormat('fa-IR', {
    dateStyle: 'full',
    timeStyle: 'short',
  }).format(date)
})
</script>
