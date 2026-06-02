import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import { fileURLToPath, URL } from 'node:url';

const optimizeDeps = [
    '@inertiajs/vue3',
    '@primeuix/themes',
    '@primeuix/themes/aura',
    '@primevue/core/api',
    'axios',
    'jalaali-js',
    'laravel-vite-plugin/inertia-helpers',
    'primevue/accordion',
    'primevue/accordioncontent',
    'primevue/accordionheader',
    'primevue/accordionpanel',
    'primevue/autocomplete',
    'primevue/breadcrumb',
    'primevue/button',
    'primevue/chart',
    'primevue/checkbox',
    'primevue/column',
    'primevue/config',
    'primevue/confirmdialog',
    'primevue/confirmationservice',
    'primevue/datatable',
    'primevue/dialog',
    'primevue/dropdown',
    'primevue/editor',
    'primevue/fileupload',
    'primevue/iconfield',
    'primevue/image',
    'primevue/inputicon',
    'primevue/inputmask',
    'primevue/inputnumber',
    'primevue/inputtext',
    'primevue/menu',
    'primevue/message',
    'primevue/multiselect',
    'primevue/paginator',
    'primevue/password',
    'primevue/popover',
    'primevue/progressspinner',
    'primevue/select',
    'primevue/tag',
    'primevue/textarea',
    'primevue/toast',
    'primevue/toastservice',
    'primevue/treetable',
    'primevue/toggleswitch',
    'primevue/useconfirm',
    'primevue/usetoast',
    'vue',
    'vuedraggable',
];

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: [
                'resources/views/**/*.blade.php',
                'routes/**/*.php',
            ],
        }),
        vue(),
        tailwindcss(),
        Components({
            resolvers: [...PrimeVueResolver()],
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        origin: 'http://127.0.0.1:5173',
        cors: true,
        hmr: {
            host: '127.0.0.1',
        },
        watch: {
            ignored: [
                '**/storage/**',
                '**/vendor/**',
                '**/public/build/**',
            ],
        },
    },
    optimizeDeps: {
        include: optimizeDeps,
    },
});
