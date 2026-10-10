import { router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

/**
 * Filter tabel yang tersinkron ke query string (debounce untuk pencarian).
 */
export function useFilters(initial, routeName, params = {}) {
    const filters = reactive({ ...initial });
    let timer;

    watch(
        () => ({ ...filters }),
        () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null && v !== undefined));
                router.get(route(routeName, params), query, { preserveState: true, preserveScroll: true, replace: true });
            }, 300);
        },
    );

    return filters;
}
