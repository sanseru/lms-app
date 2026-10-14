<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { BookOpen, Check, Code, FilePen, Flag, ListChecks, Lock, Target } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import CastleArt from './CastleArt.vue';

/**
 * One level of the learning world, drawn like the client prototype: a banner, a dotted
 * trail winding across the full width, big numbered circles and a castle at the end.
 * It has no background of its own; the page (WorldLayout) paints the sky.
 *
 * One circle per numbered lesson (its items are tasks, opened from a popup); items that
 * are not in a lesson get a circle of their own. States: done, current, upcoming, locked.
 */
const props = defineProps({
    course: { type: Object, required: true },
    /** Banner text, e.g. the level name. */
    title: { type: String, default: null },
    /** First level of the world: the banner reads "START · …". */
    first: { type: Boolean, default: true },
    /** The level after this one in the program: { title, unlocked }. */
    nextLevel: { type: Object, default: null },
});

const STEP = 175;
const PAD = 120;
const TAIL = 400;

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
    })),
);

const height = computed(() => PAD + STEP * Math.max(0, groups.value.length - 1) + TAIL);
const castle = computed(() => ({ x: 50, y: height.value - 170 }));

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

const fullPath = computed(() => trail([{ x: 50, y: 0 }, ...nodes.value, castle.value]));
const donePath = computed(() => {
    const doneCount = nodes.value.findIndex((n) => n.state !== 'done');
    const upto = doneCount === -1 ? [...nodes.value, castle.value] : nodes.value.slice(0, doneCount + 1);
    return trail([{ x: 50, y: 0 }, ...upto]);
});

const finished = computed(() => groups.value.length > 0 && groups.value.every((g) => g.done));
const doneGroups = computed(() => groups.value.filter((g) => g.done).length);
const nodeName = (node) => (node.group.unit ? `Lesson ${node.number}` : node.group.lessons[0].title);
const nodeLabel = (node) => (node.group.unit ? node.group.unit.title : node.group.lessons[0].assessment_label ?? node.group.lessons[0].type_label);
const firstOpen = (group) => group.lessons.find((l) => !l.done) ?? group.lessons[0];

// Tasks popup for a numbered lesson.
const open = ref(null);
</script>

<template>
    <section class="relative">
        <!-- Level banner -->
        <div class="relative z-10 px-4 pb-2 text-center">
            <h2 class="inline-flex max-w-full items-center gap-3 rounded-full bg-brand-600 px-6 py-3 font-display text-xl font-extrabold text-white shadow-[0_6px_0_var(--color-brand-800)] sm:px-8 sm:text-3xl">
                <Flag v-if="first" :size="26" class="shrink-0 text-accent-300" aria-hidden="true" />
                <span class="truncate">{{ first ? 'START · ' : '' }}{{ title ?? course.title }}</span>
            </h2>
            <p class="mt-3 text-sm font-bold text-sky-ink sm:text-base">{{ doneGroups }}/{{ groups.length }} lessons completed</p>
        </div>

        <div class="relative mx-auto max-w-5xl" :style="{ height: `${height}px` }">
            <svg class="absolute inset-0 size-full" :viewBox="`0 0 100 ${height}`" preserveAspectRatio="none" aria-hidden="true">
                <path :d="fullPath" fill="none" stroke="white" stroke-width="12" stroke-dasharray="1 22" stroke-linecap="round" vector-effect="non-scaling-stroke" opacity="0.95" />
                <path :d="donePath" fill="none" stroke="var(--color-brand-500)" stroke-width="12" stroke-dasharray="1 22" stroke-linecap="round" vector-effect="non-scaling-stroke" />
            </svg>

            <ol class="contents">
                <li v-for="node in nodes" :key="node.group.key" class="absolute w-40 -translate-x-1/2 -translate-y-1/2 text-center sm:w-48" :style="{ left: `${node.x}%`, top: `${node.y}px` }">
                    <span v-if="node.state === 'current'" class="pin absolute -top-12 left-1/2 -translate-x-1/2 rounded-full bg-accent-500 px-3 py-1 text-xs font-extrabold whitespace-nowrap text-ink shadow-[0_3px_0_var(--color-accent-700)]">
                        {{ node.number === 1 ? 'Start here' : 'Up next' }}
                    </span>

                    <!-- Locked: grey and not clickable -->
                    <span
                        v-if="node.state === 'locked'"
                        :aria-label="`${nodeName(node)} (locked)`"
                        title="Finish the lesson before this one first"
                        class="relative mx-auto grid size-[4.5rem] place-items-center rounded-full bg-[#9aa6b2] text-white shadow-[0_8px_0_#7b8794] sm:size-24"
                    >
                        <span class="font-display text-3xl font-extrabold sm:text-4xl">{{ node.number }}</span>
                        <Lock :size="16" class="absolute -top-1 -right-1 rounded-full bg-ink p-1 text-white ring-4 ring-white/70" :stroke-width="3" aria-hidden="true" />
                    </span>
                    <component
                        :is="node.group.unit ? 'button' : Link"
                        v-else
                        v-bind="node.group.unit ? { type: 'button' } : { href: route('learning.lessons.show', node.group.lessons[0].id) }"
                        :aria-label="`${nodeName(node)}${node.group.unit && node.group.unit.title ? `: ${node.group.unit.title}` : ''}${node.state === 'done' ? ' (completed)' : node.state === 'current' ? ' (up next)' : ''}`"
                        :aria-haspopup="node.group.unit ? 'dialog' : undefined"
                        :class="{
                            'bg-brand-300 shadow-[0_8px_0_var(--color-brand-500)]': node.state === 'done',
                            'node-current bg-brand-600 shadow-[0_8px_0_var(--color-brand-800)] outline-8 outline-offset-4 outline-success': node.state === 'current',
                            'bg-brand-500 shadow-[0_8px_0_var(--color-brand-700)]': node.state === 'upcoming',
                        }"
                        class="relative mx-auto grid size-[4.5rem] place-items-center rounded-full text-white transition hover:-translate-y-1 focus-visible:ring-4 focus-visible:ring-ink focus-visible:ring-offset-4 focus-visible:outline-none sm:size-24"
                        @click.capture="node.group.unit && (open = node)"
                    >
                        <span class="flex flex-col items-center leading-none">
                            <component :is="typeIcon(node.group.lessons[0])" v-if="!node.group.unit" :size="18" class="mb-1" aria-hidden="true" />
                            <span class="font-display text-3xl font-extrabold sm:text-4xl">{{ node.number }}</span>
                        </span>
                        <span v-if="node.state === 'done'" class="absolute -top-1 -right-1 grid size-8 place-items-center rounded-full bg-success text-white ring-4 ring-white/80">
                            <Check :size="17" :stroke-width="3" aria-hidden="true" />
                        </span>
                        <span v-else-if="node.state === 'current' && node.group.unit && node.group.doneCount" class="absolute -top-2 -right-3 rounded-full bg-white px-2 py-0.5 text-xs font-extrabold text-ink shadow">
                            {{ node.group.doneCount }}/{{ node.group.lessons.length }}
                        </span>
                    </component>

                    <!-- Label on a soft card so the dotted trail never runs through the text -->
                    <span class="relative mt-4 inline-block max-w-full rounded-2xl bg-white/85 px-3 py-1.5 shadow-sm backdrop-blur">
                    <span class="block font-display text-base font-extrabold text-[#1f4a66] sm:text-lg">{{ node.group.unit ? nodeName(node) : node.group.lessons[0].title }}</span>
                    <span class="line-clamp-2 block text-xs font-semibold text-[#3b6a85] sm:text-sm">
                        {{ nodeLabel(node) }}<template v-if="!node.group.unit && node.group.lessons[0].quiz_best !== null && node.group.lessons[0].quiz_best !== undefined"> · best {{ node.group.lessons[0].quiz_best }}</template>
                    </span>
                    </span>
                </li>
            </ol>

            <!-- The castle at the end of the level -->
            <div class="absolute w-72 -translate-x-1/2 -translate-y-1/2 text-center" :style="{ left: `${castle.x}%`, top: `${castle.y}px` }">
                <CastleArt :lit="finished" class="mx-auto h-32 w-auto drop-shadow-[0_6px_4px_rgba(0,0,0,0.15)] sm:h-36" />
                <b class="mx-auto mt-1 block w-max max-w-full rounded-full bg-white px-5 py-2 font-display text-base font-extrabold text-brand-700 shadow-[0_4px_0_rgba(91,65,133,0.2)]">
                    {{ finished ? 'Level complete!' : 'Finish' }}
                </b>
                <p v-if="nextLevel && !finished" class="mt-2 text-xs font-bold text-sky-ink">Finish every lesson to open {{ nextLevel.title }}</p>
            </div>
        </div>

        <!-- Tasks of one numbered lesson (prototype: "Tasks for Lesson …") -->
        <Modal :show="!!open" max-width="2xl" @close="open = null">
            <div v-if="open">
                <div class="flex items-start justify-between gap-3 border-b border-line px-6 py-5 sm:px-7">
                    <div>
                        <p class="eyebrow">Lesson {{ open.number }} · {{ open.group.doneCount }} of {{ open.group.lessons.length }} tasks done</p>
                        <h2 class="font-display text-2xl font-extrabold text-ink">{{ open.group.unit.title }}</h2>
                    </div>
                    <button type="button" class="grid size-10 shrink-0 place-items-center rounded-full bg-chip text-ink hover:bg-line" aria-label="Close" @click="open = null">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
                    </button>
                </div>
                <ol class="flex flex-wrap justify-center gap-5 px-6 py-7 sm:justify-start sm:px-7">
                    <li v-for="(task, i) in open.group.lessons" :key="task.id" class="w-32">
                        <Link :href="route('learning.lessons.show', task.id)" class="group flex flex-col items-center gap-2 text-center">
                            <span
                                :class="task.done ? 'border-success bg-success-soft text-success-ink' : task.id === firstOpen(open.group).id ? 'border-brand-600 bg-brand-50 text-brand-600 ring-4 ring-brand-100' : 'border-brand-400 bg-chip text-brand-600'"
                                class="relative grid size-28 place-items-center rounded-full border-2 transition group-hover:-translate-y-1 group-focus-visible:ring-4 group-focus-visible:ring-ink"
                            >
                                <span class="flex flex-col items-center leading-none">
                                    <component :is="typeIcon(task)" :size="20" aria-hidden="true" />
                                    <span class="mt-1 font-display text-4xl font-extrabold">{{ i + 1 }}</span>
                                </span>
                                <span v-if="task.done" class="absolute -top-1 -right-1 grid size-9 place-items-center rounded-full bg-success text-white ring-4 ring-white">
                                    <Check :size="18" :stroke-width="3" aria-hidden="true" />
                                </span>
                            </span>
                            <span class="text-xs font-bold tracking-wide text-muted uppercase">{{ task.assessment_label ?? task.type_label }}</span>
                            <span class="line-clamp-2 text-sm font-semibold text-ink">{{ task.title }}</span>
                        </Link>
                    </li>
                </ol>
                <div class="border-t border-line px-6 py-4 sm:px-7">
                    <Link :href="route('learning.lessons.show', firstOpen(open.group).id)" class="btn-accent w-full">
                        {{ open.group.done ? 'Review this lesson' : open.group.doneCount ? 'Continue' : 'Start lesson' }}
                    </Link>
                </div>
            </div>
        </Modal>
    </section>
</template>

<style scoped>
.node-current {
    animation: node-pulse 1.8s ease-in-out infinite;
}
.pin {
    animation: pin-bob 1.2s ease-in-out infinite;
}
@keyframes node-pulse {
    50% { transform: scale(1.06); }
}
@keyframes pin-bob {
    50% { transform: translate(-50%, -5px); }
}
@media (prefers-reduced-motion: reduce) {
    .node-current, .pin { animation: none; }
}
</style>
