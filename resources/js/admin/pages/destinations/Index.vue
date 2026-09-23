<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import api from '@/utils/api';
import Table from '@/components/ui/Table.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { useToast } from '@/composables/useToast';
import type { Destination, DestinationType, BadgeVariant } from '@/types';

const destinations = ref<Destination[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const toast = useToast();

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'type', label: 'Type' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

const statusVariant: Record<Destination['status'], BadgeVariant> = {
    active: 'success',
    inactive: 'neutral',
    error: 'danger',
    pending: 'warning',
};

const form = reactive({
    name: '',
    type: 'wordpress_site' as DestinationType,
    url: '',
    api_key: '',
    bot_id: '',
    external_chat_id: '',
});

const isWordPress = computed(() => form.type === 'wordpress_site');
const isChatDestination = computed(() => !isWordPress.value);

async function load() {
    loading.value = true;
    try {
        const response = await api.get('/destinations');
        destinations.value = response.data.data;
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    Object.assign(form, {
        name: '',
        type: 'wordpress_site',
        url: '',
        api_key: '',
        bot_id: '',
        external_chat_id: '',
    });
    showModal.value = true;
}

async function submit() {
    saving.value = true;
    try {
        const payload: Record<string, unknown> = { name: form.name, type: form.type };
        if (isWordPress.value) {
            payload.url = form.url;
            payload.api_key = form.api_key;
        } else {
            payload.bot_id = Number(form.bot_id);
            payload.external_chat_id = form.external_chat_id;
        }
        await api.post('/destinations', payload);
        toast.success('Destination created.');
        showModal.value = false;
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to create destination.');
    } finally {
        saving.value = false;
    }
}

async function remove(destination: Destination) {
    if (!confirm(`Delete destination "${destination.name}"?`)) return;
    try {
        await api.delete(`/destinations/${destination.uuid}`);
        toast.success('Destination deleted.');
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to delete destination.');
    }
}

async function testConnection(destination: Destination) {
    const response = await api.post(`/destinations/${destination.uuid}/test-connection`);
    toast.info(response.data.message ?? 'Test connection ran.');
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-surface-900">Destinations</h1>
            <Button @click="openCreate">New Destination</Button>
        </div>

        <Table :columns="columns" :rows="destinations as any" :loading="loading" empty-text="No destinations yet.">
            <template #cell-type="{ row }">
                <Badge variant="info">{{ (row as unknown as Destination).type }}</Badge>
            </template>
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[(row as unknown as Destination).status]">
                    {{ (row as unknown as Destination).status }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex gap-3">
                    <button
                        type="button"
                        class="text-sm font-medium text-primary-600 hover:underline"
                        @click="testConnection(row as unknown as Destination)"
                    >
                        Test
                    </button>
                    <button
                        type="button"
                        class="text-sm font-medium text-danger hover:underline"
                        @click="remove(row as unknown as Destination)"
                    >
                        Delete
                    </button>
                </div>
            </template>
        </Table>

        <Modal :open="showModal" title="New Destination" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <Input v-model="form.name" label="Name" id="name" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-surface-700" for="type">Type</label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="block w-full rounded-md border border-surface-300 px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30"
                    >
                        <option value="wordpress_site">WordPress Site</option>
                        <option value="telegram_channel">Telegram Channel</option>
                        <option value="telegram_group">Telegram Group</option>
                        <option value="bale_channel">Bale Channel</option>
                        <option value="bale_group">Bale Group</option>
                    </select>
                </div>

                <template v-if="isWordPress">
                    <Input v-model="form.url" label="Site URL" id="url" />
                    <Input v-model="form.api_key" label="API Key" type="password" id="api_key" />
                </template>
                <template v-if="isChatDestination">
                    <Input v-model="form.bot_id" label="Bot ID" id="bot_id" />
                    <Input v-model="form.external_chat_id" label="Chat/Channel ID" id="external_chat_id" />
                </template>

                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="showModal = false">Cancel</Button>
                    <Button type="submit" :loading="saving">Create</Button>
                </div>
            </form>
        </Modal>
    </div>
</template>
