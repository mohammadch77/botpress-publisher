<template>
  <Teleport to="body">
    <Transition name="modal-fade">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm"
        @click.self="close"
      >
        <Transition name="modal-slide" appear>
          <div
            class="w-full rounded-xl bg-white p-6 shadow-xl"
            :class="sizeClasses"
          >
            <div class="mb-4 flex items-center justify-between">
              <h3 class="text-base font-semibold text-slate-800">{{ title }}</h3>
              <button class="text-slate-400 hover:text-slate-600" @click="close">✕</button>
            </div>
            <slot />
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    size?: 'sm' | 'md' | 'lg'
  }>(),
  { size: 'md' }
)

const emit = defineEmits<{ (e: 'update:modelValue', value: boolean): void }>()

const sizeClasses = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
}[props.size]))

function close() {
  emit('update:modelValue', false)
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.modelValue) {
    close()
  }
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.15s ease;
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}
.modal-slide-enter-active {
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-slide-enter-from {
  transform: translateY(-12px);
  opacity: 0;
}
</style>
