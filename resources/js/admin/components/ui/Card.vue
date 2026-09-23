<script setup lang="ts">
defineProps<{ title?: string }>();
</script>

<template>
    <div class="rounded-lg border border-surface-200 bg-white shadow-sm">
        <div v-if="title || $slots.header" class="border-b border-surface-200 px-4 py-3">
            <slot name="header">
                <h3 class="text-sm font-semibold text-surface-900">{{ title }}</h3>
            </slot>
        </div>
        <div class="p-4">
            <slot />
        </div>
        <div v-if="$slots.footer" class="border-t border-surface-200 px-4 py-3">
            <slot name="footer" />
        </div>
    </div>
</template>
