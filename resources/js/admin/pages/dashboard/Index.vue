<script setup lang="ts">
import { onMounted, ref } from 'vue';
import api from '@/utils/api';
import Card from '@/components/ui/Card.vue';
import Spinner from '@/components/ui/Spinner.vue';
import type { DashboardStats } from '@/types';

const stats = ref<DashboardStats | null>(null);
const loading = ref(true);

const cards = [
    { key: 'tenants' as const, label: 'Tenants' },
    { key: 'bots' as const, label: 'Bots' },
    { key: 'destinations' as const, label: 'Destinations' },
    { key: 'publications' as const, label: 'Publications' },
];

onMounted(async () => {
    try {
        const response = await api.get('/dashboard/stats');
        stats.value = response.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="mb-6 text-2xl font-semibold text-surface-900">Dashboard</h1>

        <div v-if="loading" class="flex justify-center py-12">
            <Spinner size="lg" />
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="card in cards" :key="card.key">
                <p class="text-sm text-surface-500">{{ card.label }}</p>
                <p class="mt-2 text-3xl font-semibold text-surface-900">
                    {{ stats?.[card.key] ?? 0 }}
                </p>
            </Card>
        </div>
    </div>
</template>
