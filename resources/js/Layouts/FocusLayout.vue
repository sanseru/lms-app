<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import FlashToast from '@/Components/ui/FlashToast.vue';
import Icon from '@/Components/ui/Icon.vue';
import { useFullscreen } from '@/lib/fullscreen';

/**
 * Focus mode for students working on a lesson: no sidebar or header, just a
 * slim bar with the way back to the course map and one dot per lesson.
 * Pages pass course, lesson, outline and done as Inertia props.
 */
const page = usePage();
const course = computed(() => page.props.course);
const lesson = computed(() => page.props.lesson);
const outline = computed(() => page.props.outline ?? []);
// Set when the item belongs to a numbered lesson; the dots are then that lesson's tasks.
const unit = computed(() => page.props.unit ?? null);
const done = computed(() => new Set(page.props.done ?? []));
const position = computed(() => outline.value.findIndex((l) => l.id === lesson.value?.id));

// The whole page can go fullscreen as well (the code editor has its own button).
// It targets the document, the same element the course map uses when a student starts a task.
const root = ref(null);
const canFullscreen = ref(false);
const { active: fullscreen, toggle } = useFullscreen(root);

// Keep the current lesson dot in view when the list is long.
const stepper = ref(null);
const scrollToCurrent = () => nextTick(() => stepper.value?.querySelector('[aria-current="step"]')?.scrollIntoView({ block: 'nearest', inline: 'center' }));
onMounted(() => {
    root.value = document.documentElement;
    canFullscreen.value = !!document.fullscreenEnabled;
    scrollToCurrent();
});
watch(() => lesson.value?.id, scrollToCurrent);
</script>

<template>
    <div class="flex min-h-full flex-col bg-paper">
        <Head><meta name="theme-color" content="#1d2934" /></Head>
        <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[70] focus:rounded-xl focus:bg-white focus:px-4 focus:py-2 focus:font-bold focus:shadow-lg">Skip to content</a>

        <header class="sticky top-0 z-40 bg-ink text-white">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-3 px-3 sm:px-6">
                <Link
                    :href="route('learning.courses.index', { course: course.id })"
                    class="grid size-11 shrink-0 place-items-center rounded-xl bg-white text-ink transition hover:bg-brand-50"
                    aria-label="Back to the course map"
                    title="Back to the course map"
                >
                    <Icon name="arrow-left" :size="20" />
                </Link>
                <div class="hidden min-w-0 md:block md:w-48 lg:w-60">
                    <p class="truncate text-xs font-semibold text-white/70">{{ course.title }}</p>
                    <template v-if="unit">
                        <p class="truncate text-sm font-bold">Lesson {{ unit.number }}<template v-if="unit.title">: {{ unit.title }}</template></p>
                        <p v-if="position >= 0" class="text-xs text-white/70">Task {{ position + 1 }} of {{ outline.length }}</p>
                    </template>
                    <p v-else-if="position >= 0" class="text-sm font-bold">Lesson {{ position + 1 }} of {{ outline.length }}</p>
                </div>

                <!-- One dot per lesson: done, current, or still to do -->
                <nav class="min-w-0 flex-1" :aria-label="unit ? 'Tasks in this lesson' : 'Lessons in this course'">
                    <ol ref="stepper" class="no-scrollbar flex items-center gap-1.5 overflow-x-auto px-1 py-2 md:justify-center">
                        <li v-for="(item, i) in outline" :key="item.id" class="shrink-0">
                            <Link
                                :href="route('learning.lessons.show', item.id)"
                                :aria-current="item.id === lesson.id ? 'step' : undefined"
                                :title="`${i + 1}. ${item.title}`"
                                :aria-label="`${unit ? 'Task' : 'Lesson'} ${i + 1}: ${item.title}${done.has(item.id) ? ' (done)' : ''}`"
                                :class="[
                                    item.id === lesson.id
                                        ? 'bg-white text-ink ring-4 ring-white/25'
                                        : done.has(item.id)
                                          ? 'bg-success text-ink hover:brightness-105'
                                          : 'bg-white/10 text-white hover:bg-white/20',
                                ]"
                                class="grid size-9 place-items-center rounded-full text-sm font-bold transition"
                            >
                                <Icon v-if="done.has(item.id) && item.id !== lesson.id" name="check" :size="16" />
                                <template v-else>{{ i + 1 }}</template>
                            </Link>
                        </li>
                    </ol>
                </nav>

                <button
                    v-if="canFullscreen"
                    type="button"
                    class="grid size-11 shrink-0 place-items-center rounded-xl text-white transition hover:bg-white/10"
                    :aria-label="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    :title="fullscreen ? 'Exit fullscreen' : 'Fullscreen'"
                    @click="toggle"
                >
                    <Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="20" />
                </button>
            </div>
        </header>

        <main id="content" class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <slot />
        </main>

        <FlashToast />
    </div>
</template>
