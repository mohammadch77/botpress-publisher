<template>
  <AppLayout>
    <router-view />
  </AppLayout>
  <Toast :toasts="toasts" />
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Toast from '@/components/ui/Toast.vue'
import { useToast } from '@/composables/useToast'

const { toasts } = useToast()
</script>
