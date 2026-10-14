import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Fullscreen for one element (e.g. the code editor or a lesson).
 * Uses the browser Fullscreen API when it is available; otherwise (iPhone Safari,
 * embedded browsers) the element is pinned over the page with CSS instead.
 * `active` is true in both cases, `native` only for the real browser fullscreen.
 */
export function useFullscreen(target) {
    const active = ref(false);
    const native = ref(false);

    let fallback = false;
    const sync = () => {
        native.value = !!target.value && document.fullscreenElement === target.value;
        if (!fallback) active.value = native.value;
    };

    const setFallback = (on) => {
        fallback = on;
        active.value = on;
        document.documentElement.classList.toggle('overflow-hidden', on);
    };
    const onKey = (e) => e.key === 'Escape' && fallback && setFallback(false);

    async function enter() {
        if (!target.value) return;
        if (document.fullscreenEnabled && target.value.requestFullscreen) {
            try {
                await target.value.requestFullscreen();
                return;
            } catch {
                // Blocked (e.g. inside an iframe): fall back to the CSS version.
            }
        }
        setFallback(true);
    }

    async function exit() {
        if (fallback) return setFallback(false);
        if (document.fullscreenElement) await document.exitFullscreen().catch(() => {});
    }

    const toggle = () => (active.value ? exit() : enter());

    onMounted(() => {
        document.addEventListener('fullscreenchange', sync);
        document.addEventListener('keydown', onKey);
    });
    onBeforeUnmount(() => {
        document.removeEventListener('fullscreenchange', sync);
        document.removeEventListener('keydown', onKey);
        if (fallback) setFallback(false);
        // Page-level fullscreen (the document) outlives one layout: Inertia keeps the
        // document while the student moves between the map and tasks.
        if (native.value && target.value !== document.documentElement) document.exitFullscreen().catch(() => {});
    });

    return { active, native, enter, exit, toggle };
}
