<script setup lang="ts">
defineProps<{
    modelValue: string;
    label?: string;
    type?: string;
    placeholder?: string;
    error?: string;
    helperText?: string;
    id?: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="mb-1 block text-sm font-medium text-surface-700">
            {{ label }}
        </label>
        <input
            :id="id"
            :type="type ?? 'text'"
            :value="modelValue"
            :placeholder="placeholder"
            class="block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2"
            :class="error
                ? 'border-danger focus:border-danger focus:ring-danger/30'
                : 'border-surface-300 focus:border-primary-500 focus:ring-primary-500/30'"
            @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        />
        <p v-if="error" class="mt-1 text-xs text-danger">{{ error }}</p>
        <p v-else-if="helperText" class="mt-1 text-xs text-surface-400">{{ helperText }}</p>
    </div>
</template>
