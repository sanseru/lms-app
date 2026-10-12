<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import CourseMap from '../../Components/CourseMap.vue';

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
        <!-- Course switcher when the student takes more than one course -->
        <nav v-if="tabs.length > 1" class="mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="Your courses">
            <Link
                v-for="t in tabs"
                :key="t.key"
                :href="route('learning.courses.index', { course: t.courseId })"
                preserve-scroll
                :aria-current="t.active ? 'page' : undefined"
                :class="t.active ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-bold transition"
            >
                {{ t.label }} <span class="ml-1 opacity-75">{{ t.percent }}%</span>
            </Link>
        </nav>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
            <!-- Front panel: the class the student is taking right now -->
            <aside class="space-y-4 lg:sticky lg:top-24">
                <section class="panel-brand p-6">
                    <p class="text-[11px] font-extrabold tracking-[0.12em] text-brand-100 uppercase">{{ klass ? 'Your class' : 'Your course' }}</p>
                    <h1 class="mt-1 font-display text-2xl leading-tight font-extrabold tracking-tight">{{ klass?.name ?? course.title }}</h1>
                    <p class="mt-1 text-sm text-brand-100">
                        <template v-if="program">{{ program.name }} · Level {{ levelNumber }}: {{ course.title }}</template>
                        <template v-else>{{ course.title }} · {{ course.level_label }}</template>
                    </p>
                    <p v-if="course.description" class="mt-3 line-clamp-4 text-sm text-white/85">{{ course.description }}</p>

                    <div class="mt-5">
                        <div class="flex items-end justify-between text-sm">
                            <span class="font-semibold text-brand-100">{{ course.completed }}/{{ course.total }} {{ course.units?.length ? 'tasks' : 'lessons' }} done</span>
                            <span class="font-display text-3xl font-extrabold">{{ course.percent }}<span class="text-base">%</span></span>
                        </div>
                        <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-white/20" role="progressbar" :aria-valuenow="course.percent" aria-valuemin="0" aria-valuemax="100" aria-label="Course progress">
                            <div class="h-full rounded-full bg-success transition-[width] duration-500" :style="{ width: `${course.percent}%` }" />
                        </div>
                    </div>

                    <Link v-if="course.next" :href="route('learning.lessons.show', course.next.id)" class="btn-accent mt-5 w-full">
                        {{ course.completed ? 'Continue' : 'Start' }}: {{ course.next.title }}
                        <Icon name="arrow-right" :size="16" />
                    </Link>
                    <p v-else class="mt-5 flex items-center gap-2 rounded-xl bg-white/15 px-3 py-2.5 text-sm font-bold"><Icon name="trophy" :size="18" /> You finished every lesson. Awesome!</p>
                </section>

                <section v-if="klass" class="card space-y-3 p-5 text-sm">
                    <div v-if="klass.teacher" class="flex items-center gap-3">
                        <Avatar :initials="klass.teacher.initials" :seed="klass.teacher.id" />
                        <div>
                            <p class="text-xs font-semibold text-muted">Teacher</p>
                            <p class="font-bold text-ink">{{ klass.teacher.name }}</p>
                        </div>
                    </div>
                    <p class="flex items-start gap-2.5 text-ink"><Icon name="calendar-days" :size="17" class="mt-0.5 shrink-0 text-brand-600" /> {{ klass.schedule }}</p>
                    <p v-if="nextSession" class="flex items-start gap-2.5 text-ink">
                        <Icon name="clock" :size="17" class="mt-0.5 shrink-0 text-brand-600" />
                        <span>Next class: <b>{{ sessionLabel(nextSession.starts_at) }}</b></span>
                    </p>
                    <a v-if="nextSession?.zoom_url || klass.zoom_url" :href="nextSession?.zoom_url || klass.zoom_url" target="_blank" rel="noopener" class="btn-ghost w-full">
                        <Icon name="video" :size="16" /> Join on Zoom
                    </a>
                    <div class="grid grid-cols-2 gap-2 pt-1 text-center">
                        <div class="rounded-xl bg-chip p-2.5">
                            <p class="font-display text-lg font-extrabold text-ink">{{ course.quiz_avg ?? '–' }}</p>
                            <p class="text-xs text-muted">Quiz average</p>
                        </div>
                        <div class="rounded-xl bg-chip p-2.5">
                            <p :class="course.pending_assignments ? 'text-accent-600' : 'text-ink'" class="font-display text-lg font-extrabold">{{ course.pending_assignments }}</p>
                            <p class="text-xs text-muted">Assignments to hand in</p>
                        </div>
                    </div>
                    <p v-if="course.classes.length > 1" class="text-xs text-muted">Also in: {{ course.classes.slice(1).map((c) => c.name).join(', ') }}</p>
                </section>

                <Link :href="route('learning.courses.show', course.id)" class="flex items-center justify-between rounded-2xl px-1 text-sm font-bold text-brand-600 hover:text-brand-700">
                    See the lesson list and scores <Icon name="arrow-right" :size="16" />
                </Link>
                <Link :href="route('learning.courses.explore')" class="card flex items-center gap-3 p-4 text-sm transition hover:shadow-md">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-50 text-accent-700"><Icon name="compass" :size="20" /></span>
                    <span class="min-w-0 flex-1"><b class="block text-ink">Explore other courses</b><span class="text-muted">See what else you can learn next.</span></span>
                    <Icon name="arrow-right" :size="16" class="text-muted" />
                </Link>
            </aside>

            <!-- The learning path -->
            <section aria-labelledby="map-title">
                <h2 id="map-title" class="sr-only">Learning path for {{ course.title }}</h2>
                <!-- A program: every level in order, one map each; locked levels wait behind a castle -->
                <div v-if="program" class="space-y-6">
                    <template v-for="(level, i) in program.levels" :key="level.id">
                        <div v-if="level.unlocked && courseById(level.id)" :id="`level-${level.id}`">
                            <CourseMap :course="courseById(level.id)" :start-label="level.title" :next-level="nextOf(program.levels, i)" />
                        </div>
                        <div v-else class="card flex items-center gap-4 p-5 opacity-80">
                            <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-chip text-muted"><Icon name="lock" :size="24" /></span>
                            <div class="min-w-0">
                                <p class="font-display text-lg font-extrabold text-ink">Level {{ i + 1 }}: {{ level.title }}</p>
                                <p class="text-sm text-muted">Locked. Finish every lesson of {{ program.levels[i - 1]?.title ?? 'the level before' }} to open it.</p>
                            </div>
                        </div>
                    </template>
                </div>
                <CourseMap v-else-if="course.lessons.length" :course="course" />
                <div v-else class="card">
                    <EmptyState icon="book-open" title="No lessons yet" description="Your teacher hasn't published any lessons for this course yet." />
                </div>
            </section>
        </div>
    </template>

    <div v-else class="card">
        <EmptyState icon="book-open" title="No course yet" description="Your course and learning path appear here once your teacher adds a course to your class.">
            <Link :href="route('learning.courses.explore')" class="btn-ghost"><Icon name="compass" :size="16" /> Explore courses</Link>
        </EmptyState>
    </div>
</template>
