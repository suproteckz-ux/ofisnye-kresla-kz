import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],

    build: {
        // esbuild встроен в Vite — не требует отдельной установки
        // terser не установлен в node_modules, поэтому используем esbuild
        minify: 'esbuild',

        rollupOptions: {
            output: {
                manualChunks: {
                    alpine: ['alpinejs'],
                },
                entryFileNames:   'assets/[name]-[hash].js',
                chunkFileNames:   'assets/[name]-[hash].js',
                assetFileNames:   'assets/[name]-[hash][extname]',
            },
        },

        chunkSizeWarningLimit: 500,
        sourcemap: false,
    },
})
