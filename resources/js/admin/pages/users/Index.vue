<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import api from '@/utils/api';
import Table from '@/components/ui/Table.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import { useToast } from '@/composables/useToast';
import type { AppUser, BadgeVariant } from '@/types';

const users = ref<AppUser[]>([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const roleModalUser = ref<AppUser | null>(null);
const roleId = ref('');
const toast = useToast();

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'status', label: 'Status' },
    { key: 'roles', label: 'Roles' },
    { key: 'actions', label: '' },
];

const statusVariant: Record<AppUser['status'], BadgeVariant> = {
    active: 'success',
    inactive: 'neutral',
    banned: 'danger',
};

const form = reactive({ name: '', email: '', password: '' });

async function load() {
    loading.value = true;
    try {
        const response = await api.get('/users');
        users.value = response.data.data;
    } finally {
        loading.value = false;
    }
}

function openCreate() {
    Object.assign(form, { name: '', email: '', password: '' });
    showModal.value = true;
}

async function submit() {
    saving.value = true;
    try {
        await api.post('/users', form);
        toast.success('User created.');
        showModal.value = false;
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to create user.');
    } finally {
        saving.value = false;
    }
}

async function remove(user: AppUser) {
    if (!confirm(`Delete user "${user.name}"?`)) return;
    try {
        await api.delete(`/users/${user.uuid}`);
        toast.success('User deleted.');
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to delete user.');
    }
}

function openRoleAssign(user: AppUser) {
    roleModalUser.value = user;
    roleId.value = '';
}

async function assignRole() {
    if (!roleModalUser.value || !roleId.value) return;
    try {
        await api.post(`/users/${roleModalUser.value.uuid}/roles`, { role_id: Number(roleId.value) });
        toast.success('Role assigned.');
        roleModalUser.value = null;
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to assign role.');
    }
}

async function removeRole(user: AppUser, roleIdToRemove: number) {
    try {
        await api.delete(`/users/${user.uuid}/roles/${roleIdToRemove}`);
        toast.success('Role removed.');
        await load();
    } catch (e: any) {
        toast.error(e?.response?.data?.message ?? 'Failed to remove role.');
    }
}

onMounted(load);
</script>

<template>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-semibold text-surface-900">Users</h1>
            <Button @click="openCreate">New User</Button>
        </div>

        <Table :columns="columns" :rows="users as any" :loading="loading" empty-text="No users yet.">
            <template #cell-status="{ row }">
                <Badge :variant="statusVariant[(row as unknown as AppUser).status]">
                    {{ (row as unknown as AppUser).status }}
                </Badge>
            </template>
            <template #cell-roles="{ row }">
                <div class="flex flex-wrap gap-1">
                    <Badge v-for="role in (row as unknown as AppUser).roles" :key="role.id" variant="info">
                        {{ role.name }}
                        <button
                            type="button"
                            class="ml-1 text-primary-500 hover:text-primary-800"
                            @click="removeRole(row as unknown as AppUser, role.id)"
                        >
                            &times;
                        </button>
                    </Badge>
                    <button
                        type="button"
                        class="text-xs font-medium text-primary-600 hover:underline"
                        @click="openRoleAssign(row as unknown as AppUser)"
                    >
                        + Assign role
                    </button>
                </div>
            </template>
            <template #cell-actions="{ row }">
                <button
                    type="button"
                    class="text-sm font-medium text-danger hover:underline"
                    @click="remove(row as unknown as AppUser)"
                >
                    Delete
                </button>
            </template>
        </Table>

        <Modal :open="showModal" title="New User" @close="showModal = false">
            <form class="space-y-4" @submit.prevent="submit">
                <Input v-model="form.name" label="Name" id="name" />
                <Input v-model="form.email" label="Email" type="email" id="email" />
                <Input v-model="form.password" label="Password" type="password" id="password" />
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="showModal = false">Cancel</Button>
                    <Button type="submit" :loading="saving">Create</Button>
                </div>
            </form>
        </Modal>

        <Modal :open="!!roleModalUser" title="Assign Role" @close="roleModalUser = null">
            <form class="space-y-4" @submit.prevent="assignRole">
                <Input v-model="roleId" label="Role ID" id="role_id" helper-text="Enter the numeric role id to assign." />
                <div class="flex justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="roleModalUser = null">Cancel</Button>
                    <Button type="submit">Assign</Button>
                </div>
            </form>
        </Modal>
    </div>
</template>
