import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'url';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false
                }
            }
        })
    ],
    resolve: {
        alias: {
            // 'ziggy-js': path.resolve('vendor/tightenco/ziggy/dist/index.esm.js'),
            // 'ziggy-js': path.resolve('vendor/tightenco/ziggy'),
            'ziggy-js': fileURLToPath(new URL ('vendor/tightenco/ziggy'), import.meta.url),
            // '@css': fileURLToPath(new URL('./resources/css', import.meta.url)),
            // '@scss': fileURLToPath(new URL('./resources/scss', import.meta.url)),
            // '@store': fileURLToPath(new URL('./resources/js/Stores', import.meta.url)),
            // '@buttons': fileURLToPath(new URL('./resources/js/Components/Buttons', import.meta.url)),
            // '@modals': fileURLToPath(new URL('./resources/js/Components/Modals', import.meta.url)),
            // '@node_modules': fileURLToPath(new URL('./node_modules', import.meta.url)),
            // '@assets': fileURLToPath(new URL('./resources/js/Components/Assets', import.meta.url)),
            // '@input': fileURLToPath(new URL('./resources/js/Components/Input', import.meta.url))
        }
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        origin: 'https://tunofy.ddev.site:5173',
        cors: {
            origin: 'https://tunofy.ddev.site',
            methods: ['GET', 'POST'],
            allowedHeaders: ['Content-Type']
        }
    }
});
