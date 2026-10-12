<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import CourseMap from '../../Components/CourseMap.vue';

const props = defineProps({
    courses: Array,
    selected: { type: Number, default: null },
    nextSessions: { type: Object, default: () => ({}) },
});

const course = computed(() => props.courses.find((c) => c.id === props.selected) ?? props.courses[0] ?? null);
const klass = computed(() => course.value?.classes?.[0] ?? null);
const nextSession = computed(() => (klass.value ? props.nextSessions[klass.value.id] ?? null : null));

const sessionLabel = (iso) =>
    new Intl.DateTimeFormat('en-GB', { weekday: 'long', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date(iso));
</script>

<template>
    <Head title="Course" />

    <template v-if="course">
        <!-- Course switcher when the student takes more than one course -->
        <nav v-if="courses.length > 1" class="mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="Your courses">
            <Link
                v-for="c in courses"
                :key="c.id"
                :href="route('learning.courses.index', { course: c.id })"
                preserve-scroll
                :aria-current="c.id === course.id ? 'page' : undefined"
                :class="c.id === course.id ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'"
                class="shrink-0 rounded-full px-4 py-2 text-sm font-bold transition"
            >
                {{ c.title }} <span class="ml-1 opacity-75">{{ c.percent }}%</span>
            </Link>
        </nav>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-start">
            <!-- Front panel: the class the student is taking right now -->
            <aside class="space-y-4 lg:sticky lg:top-24">
                <section class="panel-brand p-6">
                    <p class="text-[11px] font-extrabold tracking-[0.12em] text-brand-100 uppercase">{{ klass ? 'Your class' : 'Your course' }}</p>
                    <h1 class="mt-1 font-display text-2xl leading-tight font-extrabold tracking-tight">{{ klass?.name ?? course.title }}</h1>
                    <p class="mt-1 text-sm text-brand-100">{{ course.title }} · {{ course.level_label }}</p>
                    <p v-if="course.description" class="mt-3 line-clamp-4 text-sm text-white/85">{{ course.description }}</p>

                    <div class="mt-5">
                        <div class="flex items-end justify-between text-sm">
                            <span class="font-semibold text-brand-100">{{ course.completed }}/{{ course.total }} lessons done</span>
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
                <CourseMap v-if="course.lessons.length" :course="course" />
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
