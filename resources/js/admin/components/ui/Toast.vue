<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, remove } = useToast();

const variantClasses = {
    success: 'bg-emerald-600',
    error: 'bg-danger',
    info: 'bg-surface-800',
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed bottom-4 right-4 z-50 flex flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="flex items-center gap-3 rounded-md px-4 py-3 text-sm text-white shadow-lg"
                :class="variantClasses[toast.variant]"
            >
                <span>{{ toast.message }}</span>
                <button type="button" class="text-white/70 hover:text-white" @click="remove(toast.id)">
                    &times;
                </button>
            </div>
        </div>
    </Teleport>
</template>
