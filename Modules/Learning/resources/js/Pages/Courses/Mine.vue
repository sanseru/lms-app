<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import WorldLayout from '@/Layouts/WorldLayout.vue';
import { enterPageFullscreen } from '@/lib/fullscreen';
import CastleArt from '../../Components/CastleArt.vue';
import CourseMap from '../../Components/CourseMap.vue';

// The course map is the whole screen, like the client prototype (no sidebar).
defineOptions({ layout: WorldLayout });

const props = defineProps({
    courses: Array,
    /** Programs the student is in, with every level and whether it is unlocked/completed. */
    programs: { type: Array, default: () => [] },
    selected: { type: Number, default: null },
    nextSessions: { type: Object, default: () => ({}) },
});

const courseById = (id) => props.courses.find((c) => c.id === id) ?? null;
const programOf = (courseId) => props.programs.find((p) => p.levels.some((l) => l.id === courseId)) ?? null;
// The level to work on now: the first unlocked one that is not finished yet.
const currentLevel = (program) => {
    const open = program.levels.filter((l) => l.unlocked && courseById(l.id));
    return open.find((l) => !l.completed) ?? open[open.length - 1] ?? null;
};

const course = computed(() => {
    if (props.selected && courseById(props.selected)) return courseById(props.selected);
    const first = props.programs.map(currentLevel).find(Boolean);
    return (first && courseById(first.id)) ?? props.courses[0] ?? null;
});
const program = computed(() => (course.value ? programOf(course.value.id) : null));
const levelNumber = computed(() => (program.value ? program.value.levels.findIndex((l) => l.id === course.value.id) + 1 : null));

// Switcher: one tab per program, plus courses that stand on their own.
const tabs = computed(() => [
    ...props.programs.map((p) => {
        const total = p.levels.reduce((n, l) => n + l.total, 0);
        const done = p.levels.reduce((n, l) => n + l.done, 0);
        return { key: `p${p.id}`, label: p.name, courseId: currentLevel(p)?.id, percent: total ? Math.round((done / total) * 100) : 0, active: program.value?.id === p.id };
    }).filter((t) => t.courseId),
    ...props.courses.filter((c) => !programOf(c.id)).map((c) => ({ key: `c${c.id}`, label: c.title, courseId: c.id, percent: c.percent, active: !program.value && course.value?.id === c.id })),
]);
const nextOf = (levels, i) => (levels[i + 1] ? { title: levels[i + 1].title, unlocked: levels[i + 1].unlocked } : null);
const klass = computed(() => course.value?.classes?.[0] ?? null);
const nextSession = computed(() => (klass.value ? props.nextSessions[klass.value.id] ?? null : null));

const sessionLabel = (iso) =>
    new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(iso));
</script>

<template>
    <Head title="Course" />

    <template v-if="course">
        <!-- Above the world: program tabs and a compact card for the class the student is in -->
        <div class="mx-auto max-w-6xl space-y-4 px-4 pt-5 sm:px-6">
            <nav v-if="tabs.length > 1" class="no-scrollbar flex gap-2 overflow-x-auto pb-1" aria-label="Your programs">
                <Link
                    v-for="t in tabs"
                    :key="t.key"
                    :href="route('learning.courses.index', { course: t.courseId })"
                    :aria-current="t.active ? 'page' : undefined"
                    :class="t.active ? 'bg-brand-600 text-white shadow-[0_4px_0_var(--color-brand-800)]' : 'bg-white text-ink shadow-[0_4px_0_rgba(91,65,133,0.18)] hover:bg-brand-50'"
                    class="shrink-0 rounded-full px-5 py-2 text-sm font-extrabold transition"
                >
                    {{ t.label }} <span class="ml-1 opacity-75">{{ t.percent }}%</span>
                </Link>
            </nav>

            <section class="flex flex-wrap items-center gap-x-6 gap-y-4 rounded-3xl bg-white/90 p-5 shadow-[0_6px_0_rgba(91,65,133,0.12)] backdrop-blur">
                <div class="min-w-48 flex-1">
                    <p class="eyebrow">{{ klass ? 'Your class' : 'Your course' }}</p>
                    <h1 class="font-display text-xl leading-tight font-extrabold text-ink sm:text-2xl">{{ klass?.name ?? course.title }}</h1>
                    <p class="text-sm text-muted">
                        <template v-if="program">{{ program.name }} · Level {{ levelNumber }}: {{ course.title }}</template>
                        <template v-else>{{ course.title }} · {{ course.level_label }}</template>
                    </p>
                </div>

                <div class="w-full sm:w-52">
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="font-semibold text-muted">{{ course.completed }}/{{ course.total }} tasks</span>
                        <span class="font-display text-2xl font-extrabold text-ink">{{ course.percent }}%</span>
                    </div>
                    <div class="mt-1 h-2.5 overflow-hidden rounded-full bg-chip" role="progressbar" :aria-valuenow="course.percent" aria-valuemin="0" aria-valuemax="100" aria-label="Level progress">
                        <div class="h-full rounded-full bg-success transition-[width] duration-500" :style="{ width: `${course.percent}%` }" />
                    </div>
                </div>

                <div v-if="klass" class="flex min-w-56 items-center gap-3 text-sm">
                    <Avatar v-if="klass.teacher" :initials="klass.teacher.initials" :seed="klass.teacher.id" />
                    <div class="min-w-0">
                        <p class="truncate font-bold text-ink">{{ klass.teacher?.name ?? 'Your teacher' }}</p>
                        <p class="text-muted">{{ nextSession ? `Next class: ${sessionLabel(nextSession.starts_at)}` : klass.schedule }}</p>
                    </div>
                </div>

                <div class="flex w-full flex-wrap gap-2 sm:w-auto">
                    <a v-if="nextSession?.zoom_url || klass?.zoom_url" :href="nextSession?.zoom_url || klass.zoom_url" target="_blank" rel="noopener" class="btn-ghost flex-1 sm:flex-none">
                        <Icon name="video" :size="16" /> Join Zoom
                    </a>
                    <Link v-if="course.next" :href="route('learning.lessons.show', course.next.id)" class="btn-accent flex-1 sm:flex-none" @click.capture="enterPageFullscreen">
                        {{ course.completed ? 'Continue' : 'Start' }} <Icon name="arrow-right" :size="16" />
                    </Link>
                    <span v-else class="inline-flex items-center gap-2 rounded-xl bg-success-soft px-3 py-2 text-sm font-bold text-success-ink"><Icon name="trophy" :size="16" /> Level done!</span>
                </div>

                <p class="flex w-full flex-wrap gap-x-5 gap-y-1 border-t border-line pt-3 text-sm font-bold">
                    <Link :href="route('learning.courses.show', course.id)" class="text-brand-600 hover:text-brand-700">Lesson list and scores</Link>
                    <span v-if="course.quiz_avg !== null" class="font-semibold text-muted">Quiz average {{ course.quiz_avg }}</span>
                    <span v-if="course.pending_assignments" class="font-semibold text-accent-700">{{ course.pending_assignments }} {{ course.pending_assignments === 1 ? 'assignment' : 'assignments' }} to hand in</span>
                </p>
            </section>
        </div>

        <!-- The learning world: every level of the program, one after another -->
        <div class="pt-10" aria-label="Learning path">
            <template v-if="program">
                <template v-for="(level, i) in program.levels" :key="level.id">
                    <div v-if="level.unlocked && courseById(level.id)" :id="`level-${level.id}`">
                        <CourseMap :course="courseById(level.id)" :title="level.title" :first="i === 0" :next-level="nextOf(program.levels, i)" />
                    </div>
                    <!-- A level that is still locked: grey banner and a closed castle -->
                    <section v-else class="px-4 pb-16 text-center" :aria-label="`${level.title} (locked)`">
                        <p class="inline-flex max-w-full items-center gap-3 rounded-full bg-[#9aa6b2] px-6 py-3 font-display text-xl font-extrabold text-white shadow-[0_6px_0_#75818e] sm:px-8 sm:text-3xl">
                            <Icon name="lock" :size="22" /> <span class="truncate">{{ level.title }}</span>
                        </p>
                        <p class="mt-3 text-sm font-bold text-sky-ink sm:text-base">Locked · finish {{ program.levels[i - 1]?.title ?? 'the level before' }} to open it</p>
                        <CastleArt locked class="mx-auto mt-8 h-28 w-auto" />
                    </section>
                </template>
            </template>
            <CourseMap v-else-if="course.lessons.length" :course="course" />
            <div v-else class="mx-auto max-w-xl px-4 pb-16">
                <div class="card">
                    <EmptyState icon="book-open" title="No lessons yet" description="Your teacher hasn't published any lessons for this course yet." />
                </div>
            </div>
        </div>

        <!-- Ground at the end of the world -->
        <footer class="world-ground px-4 pt-16 pb-12 text-center">
            <p class="font-display text-xl font-extrabold text-brand-700 sm:text-2xl">
                {{ program ? `Keep going on the ${program.name} path!` : 'Keep going, you are doing great!' }}
            </p>
            <Link :href="route('learning.courses.explore')" class="mt-3 inline-flex items-center gap-1.5 text-sm font-bold text-brand-700 hover:underline">
                <Icon name="compass" :size="16" /> Explore other courses
            </Link>
        </footer>
    </template>

    <div v-else class="mx-auto max-w-xl px-4 py-16">
        <div class="card">
            <EmptyState icon="book-open" title="No course yet" description="Your course and learning path appear here once your teacher adds a course to your class.">
                <Link :href="route('learning.courses.explore')" class="btn-ghost"><Icon name="compass" :size="16" /> Explore courses</Link>
            </EmptyState>
        </div>
    </div>
</template>

<style scoped>
.world-ground {
    background: linear-gradient(180deg, transparent 0%, #c9f2c7 40%, #9ee493 100%);
}
</style>
