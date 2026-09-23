<script setup lang="ts">
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Toast from '@/components/ui/Toast.vue';

const auth = useAuthStore();
const router = useRouter();

const navItems = [
    { name: 'dashboard', label: 'Dashboard' },
    { name: 'tenants', label: 'Tenants' },
    { name: 'users', label: 'Users' },
    { name: 'bots', label: 'Bots' },
    { name: 'destinations', label: 'Destinations' },
];

const disabledNavItems = [
    { label: 'Content' },
    { label: 'Publications' },
    { label: 'Logs' },
    { label: 'Settings' },
];

async function handleLogout() {
    await auth.logout();
    router.push({ name: 'login' });
}
</script>

<template>
    <div class="flex min-h-screen bg-surface-50">
        <aside class="w-64 shrink-0 border-r border-surface-200 bg-white">
            <div class="flex h-16 items-center px-6">
                <span class="text-lg font-semibold text-primary-600">BotPress</span>
            </div>
            <nav class="space-y-1 px-3">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.name"
                    :to="{ name: item.name }"
                    class="block rounded-md px-3 py-2 text-sm font-medium text-surface-700 hover:bg-surface-100"
                    active-class="bg-primary-50 text-primary-700"
                >
                    {{ item.label }}
                </RouterLink>

                <span
                    v-for="item in disabledNavItems"
                    :key="item.label"
                    class="block cursor-not-allowed rounded-md px-3 py-2 text-sm font-medium text-surface-300"
                    title="Coming in a later phase"
                >
                    {{ item.label }}
                </span>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-surface-200 bg-white px-6">
                <div />
                <div class="flex items-center gap-4">
                    <span class="text-sm text-surface-600">{{ auth.user?.name }}</span>
                    <button
                        type="button"
                        class="text-sm font-medium text-surface-500 hover:text-surface-900"
                        @click="handleLogout"
                    >
                        Logout
                    </button>
                </div>
            </header>

            <main class="flex-1 p-6">
                <RouterView />
            </main>
        </div>

        <Toast />
    </div>
</template>
