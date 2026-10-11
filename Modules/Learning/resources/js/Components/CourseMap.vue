<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Castle, Check, Code, FilePen, Flag, ListChecks, Target } from 'lucide-vue-next';

/**
 * Winding learning path: one circle per lesson, joined by a dotted trail,
 * from START at the top to the Finish castle at the bottom.
 * States: done (completed), current (first unfinished lesson), upcoming.
 */
const props = defineProps({
    course: { type: Object, required: true },
});

const STEP = 150;
const PAD = 120;
const TAIL = 190;

const typeIcons = { material: BookOpen, quiz: ListChecks, assignment: FilePen, code: Code };
const typeIcon = (lesson) => (lesson.assessment === 'pre' ? Flag : lesson.assessment === 'post' ? Target : typeIcons[lesson.type] ?? BookOpen);

const currentId = computed(() => props.course.lessons.find((l) => !l.done)?.id ?? null);

const nodes = computed(() =>
    props.course.lessons.map((lesson, i) => ({
        lesson,
        number: i + 1,
        x: 50 + 30 * Math.sin(i * 0.95),
        y: PAD + STEP * i,
        state: lesson.done ? 'done' : lesson.id === currentId.value ? 'current' : 'upcoming',
        side: Math.sin(i * 0.95) >= 0 ? 'left' : 'right',
    })),
);

const height = computed(() => PAD + STEP * Math.max(0, props.course.lessons.length - 1) + TAIL);
const finish = computed(() => ({ x: 50, y: height.value - 60 }));

/** Smooth S-curves between consecutive points (same idea as the prototype's map). */
const trail = (points) => {
    if (!points.length) return '';
    let d = `M ${points[0].x} ${points[0].y}`;
    for (let i = 1; i < points.length; i++) {
        const my = (points[i - 1].y + points[i].y) / 2;
        d += ` C ${points[i - 1].x} ${my}, ${points[i].x} ${my}, ${points[i].x} ${points[i].y}`;
    }
    return d;
};

const fullPath = computed(() => trail([{ x: 50, y: 20 }, ...nodes.value, finish.value]));
const donePath = computed(() => {
    const doneCount = nodes.value.findIndex((n) => n.state !== 'done');
    const upto = doneCount === -1 ? [...nodes.value, finish.value] : nodes.value.slice(0, doneCount + 1);
    return trail([{ x: 50, y: 20 }, ...upto]);
});

const finished = computed(() => props.course.lessons.length > 0 && props.course.lessons.every((l) => l.done));
</script>

<template>
    <div class="course-world relative overflow-hidden rounded-3xl border border-sky/30">
        <!-- clouds -->
        <span class="cloud top-24 left-[6%] scale-90" aria-hidden="true" />
        <span class="cloud top-[38%] right-[5%]" aria-hidden="true" />
        <span class="cloud top-[70%] left-[10%] scale-75" aria-hidden="true" />

        <div class="relative mx-auto max-w-xl px-2" :style="{ height: `${height}px` }">
            <div class="absolute top-2 left-1/2 z-10 -translate-x-1/2">
                <span class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2 font-display text-sm font-extrabold text-white shadow-[0_5px_0_var(--color-brand-800)]">
                    <Flag :size="16" aria-hidden="true" /> START
                </span>
            </div>

            <svg class="absolute inset-0 size-full" :viewBox="`0 0 100 ${height}`" preserveAspectRatio="none" aria-hidden="true">
                <path :d="fullPath" fill="none" stroke="white" stroke-width="14" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.8" />
                <path :d="fullPath" fill="none" stroke="var(--color-sky)" stroke-width="4" stroke-dasharray="2 12" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                <path :d="donePath" fill="none" stroke="var(--color-brand-500)" stroke-width="5" stroke-dasharray="2 12" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>

            <ol class="contents">
                <li v-for="node in nodes" :key="node.lesson.id" class="absolute -translate-x-1/2 -translate-y-1/2" :style="{ left: `${node.x}%`, top: `${node.y}px` }">
                    <div class="relative flex flex-col items-center">
                        <span v-if="node.state === 'current'" class="pin absolute -top-9 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-extrabold whitespace-nowrap text-ink shadow-[0_3px_0_var(--color-accent-700)]">
                            {{ node.number === 1 ? 'Start here' : 'Up next' }}
                        </span>
                        <Link
                            :href="route('learning.lessons.show', node.lesson.id)"
                            :aria-label="`Lesson ${node.number}: ${node.lesson.title}${node.state === 'done' ? ' (completed)' : node.state === 'current' ? ' (up next)' : ''}`"
                            :class="{
                                'bg-brand-100 text-brand-700 shadow-[0_6px_0_var(--color-brand-300)] hover:bg-brand-200': node.state === 'done',
                                'node-current bg-brand-600 text-white shadow-[0_6px_0_var(--color-brand-800)] outline-4 outline-offset-4 outline-success hover:bg-brand-700': node.state === 'current',
                                'bg-white text-muted shadow-[0_6px_0_var(--color-line)] ring-1 ring-line hover:text-brand-600': node.state === 'upcoming',
                            }"
                            class="relative grid size-20 place-items-center rounded-full transition hover:-translate-y-0.5 focus-visible:ring-4 focus-visible:ring-brand-300 focus-visible:outline-none sm:size-[5.5rem]"
                        >
                            <span class="flex flex-col items-center leading-none">
                                <component :is="typeIcon(node.lesson)" :size="20" aria-hidden="true" />
                                <span class="mt-1 font-display text-xl font-extrabold">{{ node.number }}</span>
                            </span>
                            <span v-if="node.state === 'done'" class="absolute -right-0.5 -bottom-0.5 grid size-7 place-items-center rounded-full bg-success text-white ring-4 ring-white">
                                <Check :size="15" :stroke-width="3" aria-hidden="true" />
                            </span>
                        </Link>
                        <span
                            :class="node.side === 'left' ? 'sm:right-full sm:mr-4 sm:text-right' : 'sm:left-full sm:ml-4 sm:text-left'"
                            class="mt-3 w-32 rounded-xl bg-white/90 px-2.5 py-1.5 text-center text-xs font-semibold text-ink shadow-sm backdrop-blur sm:absolute sm:top-1/2 sm:mt-0 sm:w-40 sm:-translate-y-1/2"
                        >
                            <span class="line-clamp-2">{{ node.lesson.title }}</span>
                            <span class="mt-0.5 block text-[11px] font-medium text-muted">
                                {{ node.lesson.assessment_label ?? node.lesson.type_label }}<template v-if="node.lesson.quiz_best !== null && node.lesson.quiz_best !== undefined"> · {{ node.lesson.quiz_best }}</template>
                            </span>
                        </span>
                    </div>
                </li>
            </ol>

            <div class="absolute -translate-x-1/2 -translate-y-1/2 text-center" :style="{ left: `${finish.x}%`, top: `${finish.y}px` }">
                <span :class="finished ? 'bg-amber-300 text-amber-900 shadow-[0_6px_0_#b45309]' : 'bg-white text-brand-600 shadow-[0_6px_0_var(--color-line)] ring-1 ring-line'" class="mx-auto grid size-24 place-items-center rounded-3xl">
                    <Castle :size="44" aria-hidden="true" />
                </span>
                <b class="mt-3 block font-display text-sm font-extrabold text-ink">{{ finished ? 'Course complete!' : 'Finish' }}</b>
            </div>
        </div>
    </div>
</template>

<style scoped>
.course-world {
    background: linear-gradient(180deg, #bfe9f7 0%, #e3f6fc 45%, #f6fcff 100%);
}
.cloud {
    position: absolute;
    width: 9rem;
    height: 2.6rem;
    border-radius: 999px;
    background: rgb(255 255 255 / 0.85);
}
.cloud::before,
.cloud::after {
    content: '';
    position: absolute;
    border-radius: 999px;
    background: inherit;
}
.cloud::before {
    width: 3.6rem;
    height: 3.6rem;
    left: 1.4rem;
    top: -1.7rem;
}
.cloud::after {
    width: 2.6rem;
    height: 2.6rem;
    right: 1.6rem;
    top: -1.1rem;
}
.node-current {
    animation: node-pulse 2s ease-in-out infinite;
}
.pin {
    animation: pin-bob 1.6s ease-in-out infinite;
}
@keyframes node-pulse {
    0%, 100% { box-shadow: 0 6px 0 var(--color-brand-800), 0 0 0 0 rgb(102 185 135 / 0.45); }
    50% { box-shadow: 0 6px 0 var(--color-brand-800), 0 0 0 14px rgb(102 185 135 / 0); }
}
@keyframes pin-bob {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
@media (prefers-reduced-motion: reduce) {
    .node-current, .pin { animation: none; }
}
</style>
