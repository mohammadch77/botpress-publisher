import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/css/admin.css', 'resources/js/admin/main.ts'],
            refresh: true,
        }),
        vue(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js/admin'),
        },
    },
});
