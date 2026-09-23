<template>
  <Modal v-model="isOpen" :title="title" size="sm">
    <div class="flex flex-col gap-4">
      <p class="text-sm text-slate-600">{{ message }}</p>
      <div class="flex justify-end gap-2">
        <Button variant="ghost" @click="cancel">{{ cancelLabel }}</Button>
        <Button variant="danger" :loading="loading" @click="confirm">{{ confirmLabel }}</Button>
      </div>
    </div>
  </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import Modal from './Modal.vue'
import Button from './Button.vue'

const props = withDefaults(
  defineProps<{
    modelValue: boolean
    title?: string
    message: string
    confirmLabel?: string
    cancelLabel?: string
    loading?: boolean
  }>(),
  { title: 'تایید عملیات', confirmLabel: 'حذف', cancelLabel: 'انصراف', loading: false }
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'confirm'): void
  (e: 'cancel'): void
}>()

const isOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

function confirm() {
  emit('confirm')
}

function cancel() {
  isOpen.value = false
  emit('cancel')
}
</script>
