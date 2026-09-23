<template>
  <button
    :disabled="disabled || loading"
    class="btn-focus inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
    :class="[sizeClasses, variantClasses]"
  >
    <Spinner v-if="loading" :size="size === 'lg' ? 'md' : 'sm'" />
    <component :is="iconLeft" v-else-if="iconLeft" class="h-4 w-4" />
    <slot />
    <component :is="iconRight" v-if="iconRight && !loading" class="h-4 w-4" />
  </button>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'
import Spinner from './Spinner.vue'

const props = withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'danger' | 'ghost' | 'outline'
    size?: 'sm' | 'md' | 'lg'
    loading?: boolean
    disabled?: boolean
    iconLeft?: Component
    iconRight?: Component
  }>(),
  {
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
  }
)

const sizeClasses = computed(() => ({
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2 text-sm',
  lg: 'px-5 py-2.5 text-base',
}[props.size]))

const variantClasses = computed(() => ({
  primary: 'bg-brand-500 text-white hover:bg-brand-600',
  secondary: 'bg-surface-2 text-slate-700 hover:bg-surface-3',
  danger: 'bg-red-500 text-white hover:bg-red-600',
  ghost: 'bg-transparent text-slate-600 hover:bg-surface-2',
  outline: 'border border-surface-3 bg-transparent text-slate-700 hover:bg-surface-1',
}[props.variant]))
</script>
