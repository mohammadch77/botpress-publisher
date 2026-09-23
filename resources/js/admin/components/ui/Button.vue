<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
        size?: 'sm' | 'md' | 'lg';
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        loading?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        disabled: false,
        loading: false,
    },
);

const variantClasses: Record<string, string> = {
    primary: 'bg-primary-600 text-white hover:bg-primary-700 focus-visible:outline-primary-600',
    secondary: 'bg-white text-surface-900 border border-surface-300 hover:bg-surface-50 focus-visible:outline-surface-400',
    danger: 'bg-danger text-white hover:bg-red-600 focus-visible:outline-danger',
    ghost: 'bg-transparent text-surface-700 hover:bg-surface-100 focus-visible:outline-surface-400',
};

const sizeClasses: Record<string, string> = {
    sm: 'px-2.5 py-1.5 text-xs',
    md: 'px-4 py-2 text-sm',
    lg: 'px-5 py-2.5 text-base',
};

const classes = computed(() => [
    'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
    variantClasses[props.variant],
    sizeClasses[props.size],
]);
</script>

<template>
    <button :type="type" :disabled="disabled || loading" :class="classes">
        <svg
            v-if="loading"
            class="h-4 w-4 animate-spin"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <slot />
    </button>
</template>
