<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-slate-700">{{ label }}</label>
    <select
      :value="modelValue"
      class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm text-slate-800"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue?: string
    label?: string
    options: { label: string; value: string }[]
  }>(),
  { options: () => [] }
)

defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>
