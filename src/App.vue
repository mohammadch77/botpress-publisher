<template>
  <div id="botpress-root" dir="rtl" lang="fa">
    <AppLayout>
      <router-view />
    </AppLayout>
    <Toast :toasts="toasts" />
  </div>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import Toast from '@/components/ui/Toast.vue'
import { useToast } from '@/composables/useToast'

const { toasts } = useToast()
</script>
