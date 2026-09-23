<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import Card from '@/components/ui/Card.vue';

const auth = useAuthStore();
const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleSubmit() {
    error.value = '';
    loading.value = true;

    try {
        await auth.login(email.value, password.value);
        router.push({ name: 'dashboard' });
    } catch {
        error.value = 'Invalid email or password.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-surface-50 px-4">
        <Card class="w-full max-w-sm">
            <template #header>
                <h1 class="text-lg font-semibold text-surface-900">BotPress Publisher</h1>
                <p class="text-sm text-surface-500">Sign in to the admin panel</p>
            </template>

            <form class="space-y-4" @submit.prevent="handleSubmit">
                <Input
                    id="email"
                    v-model="email"
                    type="email"
                    label="Email"
                    placeholder="admin@example.com"
                />
                <Input
                    id="password"
                    v-model="password"
                    type="password"
                    label="Password"
                    placeholder="••••••••"
                    :error="error"
                />
                <Button type="submit" class="w-full" :loading="loading">
                    Sign in
                </Button>
            </form>
        </Card>
    </div>
</template>
