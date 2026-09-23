<script setup lang="ts">
import { onMounted, ref } from 'vue';
import api from '@/utils/api';
import Table from '@/components/ui/Table.vue';
import Badge from '@/components/ui/Badge.vue';
import type { Tenant, BadgeVariant } from '@/types';

const tenants = ref<Tenant[]>([]);
const loading = ref(true);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'slug', label: 'Slug' },
    { key: 'plan', label: 'Plan' },
    { key: 'status', label: 'Status' },
];

const statusVariant: Record<Tenant['status'], BadgeVariant> = {
    active: 'success',
    trial: 'info',
    suspended: 'warning',
    cancelled: 'danger',
};

onMounted(async () => {
    try {
        const response = await api.get('/tenants');
        tenants.value = response.data.data;
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div>
        <h1 class="mb-6 text-2xl font-semibold text-surface-900">Tenants</h1>

        <Table :columns="columns" :rows="tenants" :loading="loading" empty-text="No tenants yet.">
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[(row as unknown as Tenant).status]">
                    {{ (row as unknown as Tenant).status }}
                </Badge>
            </template>
        </Table>
    </div>
</template>
