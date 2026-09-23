import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = createRouter({
    history: createWebHistory('/admin'),
    routes: [
        {
            path: '/login',
            name: 'login',
            component: () => import('@/pages/auth/Login.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/',
            component: () => import('@/layouts/AdminLayout.vue'),
            meta: { requiresAuth: true },
            children: [
                {
                    path: '',
                    name: 'dashboard',
                    component: () => import('@/pages/dashboard/Index.vue'),
                },
                {
                    path: 'tenants',
                    name: 'tenants',
                    component: () => import('@/pages/tenants/Index.vue'),
                },
                {
                    path: 'bots',
                    name: 'bots',
                    component: () => import('@/pages/bots/Index.vue'),
                },
                {
                    path: 'destinations',
                    name: 'destinations',
                    component: () => import('@/pages/destinations/Index.vue'),
                },
                {
                    path: 'users',
                    name: 'users',
                    component: () => import('@/pages/users/Index.vue'),
                },
            ],
        },
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (!auth.initialized) {
        await auth.fetchUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
