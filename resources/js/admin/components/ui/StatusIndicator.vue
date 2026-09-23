<script setup lang="ts">
import { computed } from 'vue';
import type { StatusVariant } from '@/types';

const props = defineProps<{ status: StatusVariant; label?: string }>();

const dotClasses: Record<StatusVariant, string> = {
    active: 'bg-emerald-500',
    inactive: 'bg-surface-400',
    error: 'bg-red-500',
    warning: 'bg-amber-500',
    pending: 'bg-primary-400',
};

const dotClass = computed(() => dotClasses[props.status]);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 text-sm text-surface-700">
        <span class="h-2 w-2 rounded-full" :class="dotClass" />
        {{ label ?? status }}
    </span>
</template>
