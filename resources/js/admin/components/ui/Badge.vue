<script setup lang="ts">
import { computed } from 'vue';
import type { BadgeVariant } from '@/types';

const props = withDefaults(defineProps<{ variant?: BadgeVariant }>(), {
    variant: 'neutral',
});

const variantClasses: Record<BadgeVariant, string> = {
    success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    warning: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    danger: 'bg-red-50 text-red-700 ring-red-600/20',
    info: 'bg-primary-50 text-primary-700 ring-primary-600/20',
    neutral: 'bg-surface-100 text-surface-700 ring-surface-500/20',
};

const classes = computed(() => variantClasses[props.variant]);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
        :class="classes"
    >
        <slot />
    </span>
</template>
