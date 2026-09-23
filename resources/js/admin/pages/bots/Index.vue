<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import api from '@/utils/api';
import Table from '@/components/ui/Table.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { useToast } from '@/composables/useToast';
import type { Bot, BadgeVariant } from '@/types';

const bots = ref<Bot[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const toast = useToast();

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'platform', label: 'Platform' },
    { key: 'token_preview', label: 'Token' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

const statusVariant: Record<Bot['status'], BadgeVariant> = {
    active: 'success',
    inactive: 'neutral',
    error: 'danger',
    pending: 'warning',
};

const platformVariant: Record<Bot['platform'], BadgeVariant> = {
    telegram: 'info',
    bale: 'warning',
};

const form = reactive({ name: '', platform: 'telegram', token: '' });

async function load() {
    loading.value = true;
    try {
        const response = await api.get('/bots');
        bots.value = response.data.data;
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    Object.assign(form, { name: '', platform: 'telegram', token: '' });
    showModal.value = true;
}

async function submit() {
    saving.value = true;
    try {
        await api.post('/bots', form);
        toast.success('Bot created.');
        showModal.value = false;
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to create bot.');
    } finally {
        saving.value = false;
    }
}

async function remove(bot: Bot) {
    if (!confirm(`Delete bot "${bot.name}"?`)) return;
    try {
        await api.delete(`/bots/${bot.uuid}`);
        toast.success('Bot deleted.');
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to delete bot.');
    }
}

async function testConnection(bot: Bot) {
    const response = await api.post(`/bots/${bot.uuid}/test-connection`);
    toast.info(response.data.message ?? 'Test connection ran.');
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-surface-900">Bots</h1>
            <Button @click="openCreate">New Bot</Button>
        </div>

        <Table :columns="columns" :rows="bots as any" :loading="loading" empty-text="No bots yet.">
            <template #cell-platform="{ row }">
                <Badge :variant="platformVariant[(row as unknown as Bot).platform]">
                    {{ (row as unknown as Bot).platform }}
                </Badge>
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[(row as unknown as Bot).status]">
                    {{ (row as unknown as Bot).status }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="text-sm font-medium text-primary-600 hover:underline"
                        @click="testConnection(row as unknown as Bot)"
                    >
                        Test
                    </button>
                    <button
                        type="button"
                        class="text-sm font-medium text-danger hover:underline"
                        @click="remove(row as unknown as Bot)"
                    >
                        Delete
                    </button>
                </div>
            </template>
        </Table>

        <Modal :open="showModal" title="New Bot" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <Input v-model="form.name" label="Name" id="name" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-surface-700" for="platform">Platform</label>
                    <select
                        id="platform"
                        v-model="form.platform"
                        class="block w-full rounded-md border border-surface-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
                    >
                        <option value="telegram">Telegram</option>
                        <option value="bale">Bale</option>
                    </select>
                </div>
                <Input v-model="form.token" label="Bot token" type="password" id="token" />
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="showModal = false">Cancel</Button>
                    <Button type="submit" :loading="saving">Create</Button>
                </div>
            </form>
        </Modal>
    </div>
</template>
