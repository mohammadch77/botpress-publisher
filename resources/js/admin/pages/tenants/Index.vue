<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import api from '@/utils/api';
import Table from '@/components/ui/Table.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { useToast } from '@/composables/useToast';
import type { Tenant, BadgeVariant } from '@/types';

const tenants = ref<Tenant[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const toast = useToast();

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'slug', label: 'Slug' },
    { key: 'plan', label: 'Plan' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

const statusVariant: Record<Tenant['status'], BadgeVariant> = {
    active: 'success',
    trial: 'info',
    suspended: 'warning',
    cancelled: 'danger',
};

const form = reactive({
    name: '',
    slug: '',
    plan: 'free',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

async function load() {
    loading.value = true;
    try {
        const response = await api.get('/tenants');
        tenants.value = response.data.data;
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    Object.assign(form, {
        name: '',
        slug: '',
        plan: 'free',
        admin_name: '',
        admin_email: '',
        admin_password: '',
    });
    showModal.value = true;
}

async function submit() {
    saving.value = true;
    try {
        await api.post('/tenants', form);
        toast.success('Tenant created.');
        showModal.value = false;
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to create tenant.');
    } finally {
        saving.value = false;
    }
}

async function remove(tenant: Tenant) {
    if (!confirm(`Delete tenant "${tenant.name}"?`)) return;
    try {
        await api.delete(`/tenants/${tenant.uuid}`);
        toast.success('Tenant deleted.');
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to delete tenant.');
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-surface-900">Tenants</h1>
            <Button @click="openCreate">New Tenant</Button>
        </div>

        <Table :columns="columns" :rows="tenants as any" :loading="loading" empty-text="No tenants yet.">
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[(row as unknown as Tenant).status]">
                    {{ (row as unknown as Tenant).status }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <button
                    type="button"
                    class="text-sm font-medium text-danger hover:underline"
                    @click="remove(row as unknown as Tenant)"
                >
                    Delete
                </button>
            </template>
        </Table>

        <Modal :open="showModal" title="New Tenant" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <Input v-model="form.name" label="Tenant name" id="name" />
                <Input v-model="form.slug" label="Slug (optional)" id="slug" />
                <Input v-model="form.admin_name" label="Admin name" id="admin_name" />
                <Input v-model="form.admin_email" label="Admin email" type="email" id="admin_email" />
                <Input v-model="form.admin_password" label="Admin password" type="password" id="admin_password" />
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="showModal = false">Cancel</Button>
                    <Button type="submit" :loading="saving">Create</Button>
                </div>
            </form>
        </Modal>
    </div>
</template>
