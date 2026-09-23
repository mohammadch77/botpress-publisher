<template>
  <div class="flex flex-col gap-1.5">
    <label v-if="label" class="text-sm font-medium text-slate-700">{{ label }}</label>
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      class="btn-focus rounded-lg border border-surface-3 px-3 py-2 text-sm text-slate-800 placeholder:text-slate-400 disabled:bg-surface-2"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue?: string
    label?: string
    placeholder?: string
    type?: string
    disabled?: boolean
    error?: string
  }>(),
  { type: 'text', disabled: false }
)

defineEmits<{ (e: 'update:modelValue', value: string): void }>()
</script>
