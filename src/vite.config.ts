import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: { '@': path.resolve(__dirname, '.') }
    },
    build: {
        outDir: '../admin',
        emptyOutDir: true,
        cssCodeSplit: false,
        rollupOptions: {
            input: path.resolve(__dirname, 'main.ts'),
            output: {
                entryFileNames: 'index.js',
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: (info) => {
                    if (info.name?.endsWith('.css')) return 'index.css'
                    return 'assets/[name]-[hash][extname]'
                }
            }
        }
    },
    base: './'
})
