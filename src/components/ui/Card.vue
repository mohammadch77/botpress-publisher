<template>
  <div
    class="rounded-xl border border-surface-3 bg-white shadow-sm transition-shadow"
    :class="[hover ? 'hover:shadow-md' : '', accentColor ? 'border-l-4' : '']"
    :style="accentColor ? { borderLeftColor: accentColor } : {}"
  >
    <div v-if="title || $slots.action" class="flex items-center justify-between border-b border-surface-3 px-5 py-4">
      <h3 v-if="title" class="text-sm font-semibold text-slate-800">{{ title }}</h3>
      <slot name="action" />
    </div>
    <div class="p-5">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    title?: string
    hover?: boolean
    accentColor?: string
  }>(),
  { hover: false }
)
</script>
