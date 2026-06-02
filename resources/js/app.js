import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';

import Aura from '@primeuix/themes/aura';
import { $t } from '@primeuix/themes';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/assets/tailwind.css';
import '../css/frontend/main.css';
import '@/assets/styles.scss';

$t().preset(Aura).use({ useDefaultOptions: true });
document.documentElement.setAttribute('dir', 'rtl');

const pages = import.meta.env.DEV
    ? {
        ...import.meta.glob('./Pages/**/*.vue', { eager: true }),
        ...import.meta.glob('./Frontend/Pages/**/*.vue', { eager: true })
    }
    : {
        ...import.meta.glob('./Pages/**/*.vue'),
        ...import.meta.glob('./Frontend/Pages/**/*.vue')
    };

createInertiaApp({
    resolve: (name) => {
        const pagePath = name.startsWith('Frontend/')
            ? `./Frontend/Pages/${name.replace('Frontend/', '')}.vue`
            : `./Pages/${name}.vue`;

        return resolvePageComponent(pagePath, pages);
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                theme: {
                    preset: Aura,
                    options: {
                        darkModeSelector: '.app-dark',
                        prefix: 'p',
                        cssLayer: false
                    }
                }
            })
            .use(ToastService)
            .use(ConfirmationService)
            .mount(el);
    }
});
