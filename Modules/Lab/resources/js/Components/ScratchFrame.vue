<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { LoaderCircle, Maximize, Minimize } from 'lucide-vue-next';
import { useFullscreen } from '@/lib/fullscreen';

/**
 * The self-hosted Scratch editor (public/vendor/scratch-gui, installed by
 * scripts/fetch-scratch-gui.mjs) in an iframe, so Scratch's React and CSS stay apart
 * from the app. `src` is a same-origin .sb3 URL; without it Scratch's default project opens.
 * Talks to resources/scratch/host.html with postMessage; see that file for the protocol.
 */
const props = defineProps({
    src: { type: String, default: null },
    mode: { type: String, default: 'editor' }, // editor | player
    title: { type: String, default: 'Scratch editor' },
});
const emit = defineEmits(['ready', 'change', 'error']);

const HOST = '/vendor/scratch-gui/host.html';
const url = computed(() => {
    const query = new URLSearchParams({ mode: props.mode });
    if (props.src) query.set('src', props.src);
    return `${HOST}?${query}`;
});

const frame = ref(null);
const box = ref(null);
const { active: fullscreen, toggle: toggleFullscreen } = useFullscreen(box);
const ready = ref(false);
const failed = ref(null); // 'missing' | 'load'
const pending = new Map();

function onMessage(event) {
    if (event.origin !== window.location.origin || event.source !== frame.value?.contentWindow) return;
    const msg = event.data ?? {};
    if (msg.scratch === 'ready') {
        ready.value = true;
        emit('ready');
    } else if (msg.scratch === 'changed') {
        emit('change');
    } else if (msg.scratch === 'error') {
        failed.value = msg.reason;
        ready.value = msg.reason === 'load'; // the editor itself works, only the saved project did not load
        emit('error', msg.reason);
    } else if (msg.scratch === 'saved' && pending.has(msg.id)) {
        pending.get(msg.id)(msg.blob);
        pending.delete(msg.id);
    }
}

/** The project as an .sb3 Blob. */
function save() {
    return new Promise((resolve, reject) => {
        if (!ready.value || !frame.value?.contentWindow) return reject(new Error('The Scratch editor is not ready yet.'));
        const id = Math.random().toString(36).slice(2);
        pending.set(id, resolve);
        frame.value.contentWindow.postMessage({ scratch: 'save', id }, window.location.origin);
        setTimeout(() => pending.has(id) && (pending.delete(id), reject(new Error('Scratch did not answer.'))), 30000);
    });
}

onMounted(() => window.addEventListener('message', onMessage));
onBeforeUnmount(() => window.removeEventListener('message', onMessage));

defineExpose({ save, ready });
</script>

<template>
    <div ref="box" :class="fullscreen && 'fixed inset-0 z-[55] rounded-none'" class="relative overflow-hidden rounded-2xl bg-white ring-1 ring-line">
        <iframe ref="frame" :key="url" :src="url" :title="title" allow="microphone; camera; fullscreen" class="block size-full" />
        <div v-if="!ready && !failed" class="absolute inset-0 grid place-items-center bg-white" role="status">
            <p class="flex items-center gap-2 text-sm font-semibold text-muted"><LoaderCircle :size="18" class="animate-spin" aria-hidden="true" /> Opening Scratch…</p>
        </div>
        <button
            v-if="mode === 'player' && ready"
            type="button"
            class="absolute top-1.5 right-1.5 grid size-9 place-items-center rounded-lg bg-white text-ink ring-1 ring-line hover:bg-chip"
            :aria-label="fullscreen ? 'Exit fullscreen' : 'Play fullscreen'"
            @click="toggleFullscreen"
        >
            <component :is="fullscreen ? Minimize : Maximize" :size="18" aria-hidden="true" />
        </button>
        <p v-if="failed === 'load'" class="absolute inset-x-3 top-3 rounded-xl bg-accent-50 px-4 py-2 text-sm font-semibold text-accent-700" role="alert">
            The saved project could not be opened, so a new one was started. Don't save over it unless you mean to.
        </p>
    </div>
</template>
