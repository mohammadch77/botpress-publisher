<script setup lang="ts">
export interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
}

const props = defineProps<{
    columns: TableColumn[];
    rows: Record<string, unknown>[];
    loading?: boolean;
    emptyText?: string;
    sortKey?: string;
    sortDir?: 'asc' | 'desc';
}>();

const emit = defineEmits<{ sort: [key: string] }>();
</script>

<template>
    <div class="overflow-x-auto rounded-lg border border-surface-200">
        <table class="min-w-full divide-y divide-surface-200 text-sm">
            <thead class="bg-surface-50">
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        class="px-4 py-3 text-left font-medium text-surface-500"
                        :class="column.sortable ? 'cursor-pointer select-none' : ''"
                        @click="column.sortable && emit('sort', column.key)"
                    >
                        <span class="inline-flex items-center gap-1">
                            {{ column.label }}
                            <span v-if="sortKey === column.key" class="text-xs">
                                {{ sortDir === 'asc' ? '▲' : '▼' }}
                            </span>
                        </span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-surface-100 bg-white">
                <tr v-if="loading">
                    <td :colspan="columns.length" class="px-4 py-8 text-center text-surface-400">
                        Loading…
                    </td>
                </tr>
                <tr v-else-if="rows.length === 0">
                    <td :colspan="columns.length" class="px-4 py-8 text-center text-surface-400">
                        {{ emptyText ?? 'No records found.' }}
                    </td>
                </tr>
                <tr v-for="(row, index) in rows" v-else :key="index" class="hover:bg-surface-50">
                    <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-surface-700">
                        <slot :name="`cell-${column.key}`" :row="row">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
