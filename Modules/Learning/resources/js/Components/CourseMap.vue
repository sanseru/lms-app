<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Castle, Check, Code, FilePen, Flag, ListChecks, Lock, Target } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import { enterPageFullscreen } from '@/lib/fullscreen';

/**
 * Winding learning path from START at the top to the castle at the bottom.
 * One circle per numbered lesson (its items are tasks, opened from a popup);
 * items that are not in a lesson get a circle of their own.
 * States: done, current (first unfinished circle), upcoming.
 */
const props = defineProps({
    course: { type: Object, required: true },
    /** Shown on the START sign, e.g. the level name inside a program. */
    startLabel: { type: String, default: null },
    /** The level after this one in the program: { title, unlocked }. */
    nextLevel: { type: Object, default: null },
});

const STEP = 150;
const PAD = 160;
const TAIL = 290;

const typeIcons = { material: BookOpen, quiz: ListChecks, assignment: FilePen, code: Code };
const typeIcon = (lesson) => (lesson.assessment === 'pre' ? Flag : lesson.assessment === 'post' ? Target : typeIcons[lesson.type] ?? BookOpen);

// Group items into circles: a numbered lesson gathers its tasks, other items stand alone.
const groups = computed(() => {
    const units = new Map((props.course.units ?? []).map((u) => [u.id, u]));
    const list = [];
    const byUnit = new Map();
    for (const lesson of props.course.lessons) {
        const unit = lesson.unit_id ? units.get(lesson.unit_id) : null;
        if (unit) {
            if (!byUnit.has(unit.id)) {
                const group = { key: `u${unit.id}`, unit, lessons: [] };
                byUnit.set(unit.id, group);
                list.push(group);
            }
            byUnit.get(unit.id).lessons.push(lesson);
        } else {
            list.push({ key: `l${lesson.id}`, unit: null, lessons: [lesson] });
        }
    }
    return list.map((g) => ({
        ...g,
        done: g.lessons.every((l) => l.done),
        doneCount: g.lessons.filter((l) => l.done).length,
        // Lessons open one after another (or when the teacher opens them early).
        locked: g.lessons.every((l) => l.locked),
    }));
});

const currentKey = computed(() => groups.value.find((g) => !g.done && !g.locked)?.key ?? null);

const nodes = computed(() =>
    groups.value.map((group, i) => ({
        group,
        number: i + 1,
        x: 50 + 30 * Math.sin(i * 0.95),
        y: PAD + STEP * i,
        state: group.done ? 'done' : group.locked ? 'locked' : group.key === currentKey.value ? 'current' : 'upcoming',
        side: Math.sin(i * 0.95) >= 0 ? 'left' : 'right',
    })),
);

const height = computed(() => PAD + STEP * Math.max(0, groups.value.length - 1) + TAIL);
const finish = computed(() => ({ x: 50, y: height.value - 110 }));

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

const finished = computed(() => groups.value.length > 0 && groups.value.every((g) => g.done));
const doneGroups = computed(() => groups.value.filter((g) => g.done).length);
const nodeName = (node) => (node.group.unit ? `Lesson ${node.number}` : node.group.lessons[0].title);
const firstOpen = (group) => group.lessons.find((l) => !l.done) ?? group.lessons[0];

// Tasks popup for a numbered lesson.
const open = ref(null);
</script>

<template>
    <div class="course-world relative overflow-hidden rounded-3xl border border-sky/30">
        <!-- clouds -->
        <span class="cloud top-24 left-[6%] scale-90" aria-hidden="true" />
        <span class="cloud top-[38%] right-[5%]" aria-hidden="true" />
        <span class="cloud top-[70%] left-[10%] scale-75" aria-hidden="true" />

        <div class="relative mx-auto max-w-xl px-2" :style="{ height: `${height}px` }">
            <div class="absolute top-2 left-1/2 z-10 flex -translate-x-1/2 flex-col items-center">
                <span class="inline-flex max-w-[80vw] items-center gap-2 rounded-full bg-brand-600 px-5 py-2 font-display text-sm font-extrabold text-white shadow-[0_5px_0_var(--color-brand-800)]">
                    <Flag :size="16" aria-hidden="true" class="shrink-0" /> <span class="truncate">START<template v-if="startLabel"> · {{ startLabel }}</template></span>
                </span>
                <span class="mt-2 rounded-full bg-white/80 px-2.5 py-0.5 text-xs font-semibold text-ink">{{ doneGroups }}/{{ groups.length }} lessons completed</span>
            </div>

            <svg class="absolute inset-0 size-full" :viewBox="`0 0 100 ${height}`" preserveAspectRatio="none" aria-hidden="true">
                <path :d="fullPath" fill="none" stroke="white" stroke-width="14" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.8" />
                <path :d="fullPath" fill="none" stroke="var(--color-sky)" stroke-width="4" stroke-dasharray="2 12" stroke-linecap="round" vector-effect="non-scaling-stroke" />
                <path :d="donePath" fill="none" stroke="var(--color-brand-500)" stroke-width="5" stroke-dasharray="2 12" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>

            <ol class="contents">
                <li v-for="node in nodes" :key="node.group.key" class="absolute -translate-x-1/2 -translate-y-1/2" :style="{ left: `${node.x}%`, top: `${node.y}px` }">
                    <div class="relative flex flex-col items-center">
                        <span v-if="node.state === 'current'" class="pin absolute -top-9 rounded-full bg-accent-500 px-2.5 py-1 text-xs font-extrabold whitespace-nowrap text-ink shadow-[0_3px_0_var(--color-accent-700)]">
                            {{ node.number === 1 ? 'Start here' : 'Up next' }}
                        </span>
                        <span
                            v-if="node.state === 'locked'"
                            :aria-label="`${nodeName(node)} (locked)`"
                            title="Finish the lesson before this one first"
                            class="relative grid size-20 place-items-center rounded-full bg-chip text-muted shadow-[0_6px_0_var(--color-line)] sm:size-[5.5rem]"
                        >
                            <span class="flex flex-col items-center leading-none">
                                <Lock :size="18" aria-hidden="true" />
                                <span class="mt-1 font-display text-xl font-extrabold">{{ node.number }}</span>
                            </span>
                        </span>
                        <component
                            :is="node.group.unit ? 'button' : Link"
                            v-else
                            v-bind="node.group.unit ? { type: 'button' } : { href: route('learning.lessons.show', node.group.lessons[0].id) }"
                            :aria-label="`${nodeName(node)}${node.group.unit && node.group.unit.title ? `: ${node.group.unit.title}` : ''}${node.state === 'done' ? ' (completed)' : node.state === 'current' ? ' (up next)' : ''}`"
                            :aria-haspopup="node.group.unit ? 'dialog' : undefined"
                            :class="{
                                'bg-brand-100 text-brand-700 shadow-[0_6px_0_var(--color-brand-300)] hover:bg-brand-200': node.state === 'done',
                                'node-current bg-brand-600 text-white shadow-[0_6px_0_var(--color-brand-800)] outline-4 outline-offset-4 outline-success hover:bg-brand-700': node.state === 'current',
                                'bg-white text-muted shadow-[0_6px_0_var(--color-line)] ring-1 ring-line hover:text-brand-600': node.state === 'upcoming',
                            }"
                            class="relative grid size-20 place-items-center rounded-full transition hover:-translate-y-0.5 focus-visible:ring-4 focus-visible:ring-brand-300 focus-visible:outline-none sm:size-[5.5rem]"
                            @click.capture="node.group.unit ? (open = node) : enterPageFullscreen()"
                        >
                            <span class="flex flex-col items-center leading-none">
                                <component :is="typeIcon(node.group.lessons[0])" v-if="!node.group.unit" :size="20" aria-hidden="true" />
                                <span :class="node.group.unit ? 'text-3xl' : 'mt-1 text-xl'" class="font-display font-extrabold">{{ node.number }}</span>
                            </span>
                            <span v-if="node.state === 'done'" class="absolute -right-0.5 -bottom-0.5 grid size-7 place-items-center rounded-full bg-success text-white ring-4 ring-white">
                                <Check :size="15" :stroke-width="3" aria-hidden="true" />
                            </span>
                        </component>
                        <span
                            :class="node.side === 'left' ? 'sm:right-full sm:mr-4 sm:text-right' : 'sm:left-full sm:ml-4 sm:text-left'"
                            class="mt-3 w-32 rounded-xl bg-white/90 px-2.5 py-1.5 text-center text-xs font-semibold text-ink shadow-sm backdrop-blur sm:absolute sm:top-1/2 sm:mt-0 sm:w-40 sm:-translate-y-1/2"
                        >
                            <template v-if="node.group.unit">
                                <span class="line-clamp-2">{{ nodeName(node) }}<template v-if="node.group.unit.title">: {{ node.group.unit.title }}</template></span>
                                <span class="mt-0.5 block text-[11px] font-medium text-muted">{{ node.group.doneCount }}/{{ node.group.lessons.length }} tasks done</span>
                            </template>
                            <template v-else>
                                <span class="line-clamp-2">{{ node.group.lessons[0].title }}</span>
                                <span class="mt-0.5 block text-[11px] font-medium text-muted">
                                    {{ node.group.lessons[0].assessment_label ?? node.group.lessons[0].type_label }}<template v-if="node.group.lessons[0].quiz_best !== null && node.group.lessons[0].quiz_best !== undefined"> · {{ node.group.lessons[0].quiz_best }}</template>
                                </span>
                            </template>
                        </span>
                    </div>
                </li>
            </ol>

            <!-- The castle: end of this level, and the door to the next one in the program -->
            <div class="absolute w-64 -translate-x-1/2 -translate-y-1/2 text-center" :style="{ left: `${finish.x}%`, top: `${finish.y}px` }">
                <span :class="finished ? 'bg-amber-300 text-amber-900 shadow-[0_6px_0_#b45309]' : 'bg-white text-brand-600 shadow-[0_6px_0_var(--color-line)] ring-1 ring-line'" class="relative mx-auto grid size-24 place-items-center rounded-3xl">
                    <Castle :size="44" aria-hidden="true" />
                    <span v-if="nextLevel && !nextLevel.unlocked" class="absolute -right-2 -bottom-2 grid size-8 place-items-center rounded-full bg-ink text-white ring-4 ring-white"><Lock :size="14" aria-hidden="true" /></span>
                </span>
                <template v-if="nextLevel">
                    <b class="mt-3 block font-display text-sm font-extrabold text-ink">Next: {{ nextLevel.title }}</b>
                    <span class="text-xs font-semibold text-muted">{{ nextLevel.unlocked ? 'Unlocked, keep going below' : 'Finish every lesson here to unlock it' }}</span>
                </template>
                <b v-else class="mt-3 block font-display text-sm font-extrabold text-ink">{{ finished ? 'Course complete!' : 'Finish' }}</b>
            </div>
        </div>

        <!-- Tasks of one numbered lesson -->
        <Modal :show="!!open" max-width="lg" @close="open = null">
            <div v-if="open" class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="eyebrow">Lesson {{ open.number }}</p>
                        <h2 class="font-display text-xl font-extrabold text-ink">{{ open.group.unit.title }}</h2>
                        <p class="text-sm text-muted">{{ open.group.doneCount }} of {{ open.group.lessons.length }} tasks done</p>
                    </div>
                    <button type="button" class="grid size-10 place-items-center rounded-xl text-muted hover:bg-chip hover:text-ink" aria-label="Close" @click="open = null">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </button>
                </div>
                <ol class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <li v-for="(task, i) in open.group.lessons" :key="task.id">
                        <Link
                            :href="route('learning.lessons.show', task.id)"
                            @click.capture="enterPageFullscreen"
                            :class="task.done ? 'border-success bg-success-soft' : task.id === firstOpen(open.group).id ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-100' : 'border-line bg-white hover:border-brand-300'"
                            class="flex h-full flex-col items-center gap-2 rounded-2xl border-2 p-4 text-center transition"
                        >
                            <span :class="task.done ? 'bg-success text-white' : 'bg-white text-brand-600 ring-2 ring-brand-200'" class="grid size-14 place-items-center rounded-full">
                                <Check v-if="task.done" :size="24" :stroke-width="3" aria-hidden="true" />
                                <component :is="typeIcon(task)" v-else :size="22" aria-hidden="true" />
                            </span>
                            <span class="text-xs font-bold text-muted">Task {{ i + 1 }} · {{ task.assessment_label ?? task.type_label }}</span>
                            <span class="line-clamp-2 text-sm font-semibold text-ink">{{ task.title }}</span>
                        </Link>
                    </li>
                </ol>
                <Link :href="route('learning.lessons.show', firstOpen(open.group).id)" class="btn-accent mt-5 w-full" @click.capture="enterPageFullscreen">
                    {{ open.group.done ? 'Review this lesson' : open.group.doneCount ? 'Continue' : 'Start lesson' }}
                </Link>
            </div>
        </Modal>
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
