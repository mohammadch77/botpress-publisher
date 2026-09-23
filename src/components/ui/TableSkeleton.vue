<template>
  <div class="overflow-x-auto rounded-xl border border-surface-3">
    <table class="w-full text-left text-sm">
      <tbody>
        <tr v-for="row in rows" :key="row" class="border-b border-surface-3 last:border-0">
          <td v-for="col in columns" :key="col" class="px-4 py-3">
            <div class="h-4 w-full animate-pulse rounded bg-surface-2" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
withDefaults(
  defineProps<{
    rows?: number
    columns?: number
  }>(),
  { rows: 5, columns: 4 }
)
</script>
