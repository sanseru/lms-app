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

// Page fullscreen that the app entered for the student (not via the fullscreen button).
// Only this kind is left again automatically when the student goes back to a normal page.
let autoEntered = false;
const DISMISSED = 'lms.fullscreen-dismissed';

const storage = {
    get: () => {
        try {
            return sessionStorage.getItem(DISMISSED) === '1';
        } catch {
            return false;
        }
    },
    set: (on) => {
        try {
            on ? sessionStorage.setItem(DISMISSED, '1') : sessionStorage.removeItem(DISMISSED);
        } catch {
            // Private mode or blocked storage: the choice just isn't remembered.
        }
    },
};

if (typeof document !== 'undefined') {
    // Esc (or the browser's own control) while in app-entered fullscreen: the student
    // chose to leave, so stop re-entering on the next click until they start a task again.
    document.addEventListener('fullscreenchange', () => {
        if (!document.fullscreenElement && autoEntered) {
            autoEntered = false;
            storage.set(true);
        }
    });
}

/**
 * Go fullscreen for the whole page when a student opens their course or starts working
 * (called from a click, which browsers require). Inertia keeps the same document between
 * pages, so the fullscreen lasts while the student moves between the map and tasks; Esc leaves it.
 */
export function enterPageFullscreen() {
    const root = document.documentElement;
    storage.set(false);
    if (document.fullscreenElement || !document.fullscreenEnabled || !root.requestFullscreen) return;
    root.requestFullscreen()
        .then(() => (autoEntered = true))
        .catch(() => {});
}

/**
 * The course map was opened directly (bookmark, refresh, redirect): browsers only allow
 * fullscreen from a user gesture, so go fullscreen on the student's first click or key press.
 * Skipped when the student left fullscreen with Esc earlier in this browser session.
 * Returns a function that removes the listener.
 */
export function armPageFullscreen() {
    if (document.fullscreenElement || !document.fullscreenEnabled || storage.get()) return () => {};
    const go = () => {
        disarm();
        if (!storage.get()) enterPageFullscreen();
    };
    const disarm = () => {
        document.removeEventListener('pointerdown', go, true);
        document.removeEventListener('keydown', go, true);
    };
    document.addEventListener('pointerdown', go, true);
    document.addEventListener('keydown', go, true);
    return disarm;
}

/** Back on a normal page: leave the fullscreen the app entered (never one the student chose). */
export function leavePageFullscreen() {
    if (!autoEntered) return;
    autoEntered = false;
    if (document.fullscreenElement) document.exitFullscreen().catch(() => {});
}
