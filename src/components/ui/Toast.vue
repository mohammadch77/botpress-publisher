<template>
  <Teleport to="body">
    <div class="fixed right-4 top-4 z-[60] flex w-80 flex-col gap-2">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="relative overflow-hidden rounded-lg border border-surface-3 bg-white p-4 shadow-lg"
        >
          <div class="flex items-start gap-2">
            <span class="h-2 w-2 shrink-0 rounded-full" :class="dotClass(toast.variant)" />
            <p class="text-sm text-slate-700">{{ toast.message }}</p>
          </div>
          <div class="absolute bottom-0 left-0 h-1 bg-brand-500" :style="{ width: '100%' }" />
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
interface ToastItem {
  id: number
  message: string
  variant: 'success' | 'error' | 'warning' | 'info'
}

defineProps<{ toasts: ToastItem[] }>()

function dotClass(variant: ToastItem['variant']) {
  return {
    success: 'bg-emerald-500',
    error: 'bg-red-500',
    warning: 'bg-amber-500',
    info: 'bg-sky-500',
  }[variant]
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.2s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>
