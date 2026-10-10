import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import AppLayout from '@/Layouts/AppLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Alprogmatics';

// Halaman inti (resources/js/Pages) dan halaman modul (Modules/<Nama>/resources/js/Pages).
const corePages = import.meta.glob('./Pages/**/*.vue');
const modulePages = import.meta.glob('../../Modules/*/resources/js/Pages/**/*.vue');

/**
 * "Students/Index"          -> resources/js/Pages/Students/Index.vue
 * "People::Students/Index"  -> Modules/People/resources/js/Pages/Students/Index.vue
 */
async function resolvePage(name) {
    const [module, page] = name.includes('::') ? name.split('::') : [null, name];
    const path = module
        ? `../../Modules/${module}/resources/js/Pages/${page}.vue`
        : `./Pages/${page}.vue`;
    const loader = (module ? modulePages : corePages)[path];

    if (!loader) {
        throw new Error(`Halaman Inertia tidak ditemukan: ${name}`);
    }

    const component = (await loader()).default;
    // Semua halaman memakai AppLayout kecuali yang menentukan layout sendiri (mis. halaman Auth).
    component.layout ??= name.startsWith('Auth/') ? undefined : AppLayout;

    return component;
}

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: resolvePage,
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#F58464',
    },
});
