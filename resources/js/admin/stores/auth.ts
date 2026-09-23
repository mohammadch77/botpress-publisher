import { defineStore } from 'pinia';
import api, { ensureCsrfCookie } from '@/utils/api';
import type { AdminUser } from '@/types';

interface AuthState {
    user: AdminUser | null;
    initialized: boolean;
}

export const useAuthStore = defineStore('auth', {
    state: (): AuthState => ({
        user: null,
        initialized: false,
    }),

    getters: {
        isAuthenticated: (state) => state.user !== null,
    },

    actions: {
        async login(email: string, password: string): Promise<void> {
            await ensureCsrfCookie();
            const response = await api.post('/auth/login', { email, password });
            this.user = response.data.user;
        },

        async logout(): Promise<void> {
            await api.post('/auth/logout');
            this.user = null;
        },

        async fetchUser(): Promise<void> {
            try {
                const response = await api.get('/auth/me');
                this.user = response.data.user;
            } catch {
                this.user = null;
            } finally {
                this.initialized = true;
            }
        },
    },
});
