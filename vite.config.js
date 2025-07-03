import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/test-react.jsx',
                'resources/js/app.jsx'
            ],
            refresh: true,
        }),
        tailwindcss(),
        react(),
    ],
    server: {
        host: process.env.VITE_HOST || 'localhost',
        port: parseInt(process.env.VITE_PORT || '5173'),
        hmr: {
            host: process.env.VITE_HOST || 'localhost',
        },
    },
});
