import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

/**
 * Confirm dialog state for destructive actions.
 *   const confirm = useConfirm();
 *   confirm.ask({ title, message, url, method: 'delete' })
 */
export function useConfirm() {
    const state = reactive({ show: false, title: '', message: '', url: null, method: 'delete', confirmText: 'Yes, delete', processing: false });

    const ask = (options) => Object.assign(state, { confirmText: 'Yes, delete', method: 'delete', ...options, show: true });
    const close = () => (state.show = false);
    const run = () => {
        state.processing = true;
        router.visit(state.url, {
            method: state.method,
            preserveScroll: true,
            onFinish: () => Object.assign(state, { processing: false, show: false }),
        });
    };

    return { state, ask, close, run };
}
