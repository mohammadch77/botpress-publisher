<script setup lang="ts">
defineProps<{ open: boolean; title?: string }>();
const emit = defineEmits<{ close: [] }>();
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-surface-900/50" @click="emit('close')" />
            <div class="relative w-full max-w-md rounded-lg bg-white p-6 shadow-xl" role="dialog" aria-modal="true">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-surface-900">{{ title }}</h2>
                    <button
                        type="button"
                        class="text-surface-400 hover:text-surface-600"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        &times;
                    </button>
                </div>
                <slot />
            </div>
        </div>
    </Teleport>
</template>
