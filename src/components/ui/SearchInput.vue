<template>
  <div class="relative">
    <Search class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
    <input
      :value="modelValue"
      type="text"
      :placeholder="placeholder"
      class="btn-focus w-full rounded-lg border border-surface-3 py-2 pl-9 pr-9 text-sm text-slate-800 placeholder:text-slate-400"
      @input="onInput"
    />
    <button
      v-if="modelValue"
      class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
      @click="clear"
    >
      <X class="h-4 w-4" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { Search, X } from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    debounce?: number
  }>(),
  { modelValue: '', placeholder: 'Search...', debounce: 300 }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'search', value: string): void
}>()

let timer: ReturnType<typeof setTimeout> | undefined

function onInput(event: Event) {
  const value = (event.target as HTMLInputElement).value
  emit('update:modelValue', value)
  if (timer) clearTimeout(timer)
  timer = setTimeout(() => emit('search', value), props.debounce)
}

function clear() {
  emit('update:modelValue', '')
  emit('search', '')
}
</script>
