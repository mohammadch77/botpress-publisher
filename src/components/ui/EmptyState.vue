<template>
  <div class="flex flex-col items-center justify-center gap-3 py-12 text-center">
    <component :is="icon" v-if="icon" class="h-10 w-10 text-slate-300" />
    <p class="text-sm font-medium text-slate-500">{{ message }}</p>
    <slot />
  </div>
</template>

<script setup lang="ts">
import type { Component } from 'vue'

defineProps<{
  message: string
  icon?: Component
}>()
</script>
