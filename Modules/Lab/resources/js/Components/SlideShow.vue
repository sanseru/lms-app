<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, Maximize, Minimize } from 'lucide-vue-next';
import { useFullscreen } from '@/lib/fullscreen';
import { parseSlides } from '../lib/slides';

/** Plays a Presentation project: one slide at a time with Back / Next, arrow keys and fullscreen. */
const props = defineProps({
    source: { type: String, default: '' },
    tall: Boolean,
});

const slides = computed(() => parseSlides(props.source));
const index = ref(0);
watch(slides, (list) => (index.value = Math.min(index.value, Math.max(list.length - 1, 0))));

const go = (step) => (index.value = Math.min(Math.max(index.value + step, 0), slides.value.length - 1));
const onKey = (e) => {
    if (['ArrowRight', 'PageDown', ' '].includes(e.key)) (e.preventDefault(), go(1));
    if (['ArrowLeft', 'PageUp'].includes(e.key)) (e.preventDefault(), go(-1));
};

const stage = ref(null);
const { active: fullscreen, toggle } = useFullscreen(stage);

defineExpose({ first: () => (index.value = 0) });
</script>

<template>
    <div
        ref="stage"
        tabindex="0"
        :class="[fullscreen ? 'fixed inset-0 z-[55] rounded-none' : 'rounded-2xl', tall && !fullscreen ? 'min-h-[28rem]' : 'min-h-72']"
        class="flex flex-col overflow-hidden bg-ink text-white outline-none focus-visible:ring-2 focus-visible:ring-brand-300"
        aria-roledescription="slideshow"
        aria-label="Slideshow"
        @keydown="onKey"
    >
        <div class="flex flex-1 items-center justify-center p-3 sm:p-5">
            <!-- 16:9 slide, scaled to the space it has -->
            <article
                v-if="slides.length"
                class="slide aspect-video w-full max-w-5xl overflow-hidden rounded-xl bg-white p-[5%] text-ink shadow-xl"
                :class="fullscreen && 'max-w-[min(100%,calc((100vh-6rem)*16/9))]'"
                aria-live="polite"
                :aria-label="`Slide ${index + 1} of ${slides.length}`"
                v-html="slides[index]"
            />
            <p v-else class="text-sm text-white/60">Write your slides on the left. Put a line with three dashes (---) between slides.</p>
        </div>
        <div class="flex items-center justify-between gap-2 border-t border-white/10 px-3 py-2">
            <button type="button" class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-bold hover:bg-white/10 disabled:opacity-40" :disabled="index === 0" @click="go(-1)">
                <ChevronLeft :size="16" aria-hidden="true" /> Back
            </button>
            <span class="font-mono text-xs text-white/70">{{ slides.length ? index + 1 : 0 }} / {{ slides.length }}</span>
            <div class="flex items-center gap-1">
                <button type="button" class="inline-flex items-center gap-1 rounded-lg bg-success px-3 py-1.5 text-sm font-extrabold text-ink disabled:opacity-40" :disabled="index >= slides.length - 1" @click="go(1)">
                    Next <ChevronRight :size="16" aria-hidden="true" />
                </button>
                <button type="button" class="grid size-8 place-items-center rounded-lg hover:bg-white/10" :aria-label="fullscreen ? 'Exit fullscreen' : 'Play fullscreen'" @click="toggle">
                    <component :is="fullscreen ? Minimize : Maximize" :size="16" aria-hidden="true" />
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Slide typography: big and readable like a real deck, scaled to the slide's width (cqw). */
.slide { container-type: inline-size; display: flex; flex-direction: column; justify-content: center; }
.slide :deep(h1) { font-family: var(--font-display); font-size: clamp(1.6rem, 7cqw, 4.5rem); font-weight: 800; line-height: 1.05; color: var(--color-brand-700); }
.slide :deep(h2) { font-family: var(--font-display); font-size: clamp(1.35rem, 5.2cqw, 3.4rem); font-weight: 800; line-height: 1.1; color: var(--color-brand-700); }
.slide :deep(h3) { font-size: clamp(1.15rem, 4cqw, 2.4rem); font-weight: 700; }
.slide :deep(p), .slide :deep(li) { font-size: clamp(1rem, 3.3cqw, 2.1rem); line-height: 1.4; margin-top: 0.45em; }
.slide :deep(ul) { list-style: disc; padding-left: 1.3em; }
.slide :deep(ol) { list-style: decimal; padding-left: 1.3em; }
.slide :deep(code) { font-family: var(--font-mono); background: var(--color-chip); border-radius: 0.3em; padding: 0 0.3em; }
.slide :deep(img) { max-height: 45cqw; max-width: 100%; margin-top: 0.5em; border-radius: 0.5em; }
</style>
