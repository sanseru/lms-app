<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import CourseCover from '../../Components/CourseCover.vue';

const props = defineProps({
    courses: Array,
    current: { type: Object, default: null },
});

// Filter by subject so a long catalog stays easy to scan.
const subject = ref('all');
const subjects = computed(() => [...new Set(props.courses.map((c) => c.subject).filter(Boolean))].sort());
const shown = computed(() => (subject.value === 'all' ? props.courses : props.courses.filter((c) => c.subject === subject.value)));

// Own courses open the course map; the rest open the read-only overview.
const hrefFor = (c) => (c.mine ? route('learning.courses.index', { course: c.id }) : route('learning.courses.show', c.id));
const hours = (m) => (m >= 60 ? `${Math.round(m / 60)} h` : `${m} min`);
</script>

<template>
    <Head title="Explore courses" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:items-start">
        <!-- The course the student is taking now, so the way back is always one click -->
        <aside class="lg:sticky lg:top-24">
            <section v-if="current" class="panel-brand p-6">
                <p class="text-[11px] font-extrabold tracking-[0.12em] text-brand-100 uppercase">Your course</p>
                <h2 class="mt-1 font-display text-xl leading-tight font-extrabold">{{ current.title }}</h2>
                <p v-if="current.description" class="mt-2 line-clamp-3 text-sm text-white/85">{{ current.description }}</p>
                <div class="mt-4 flex items-end justify-between text-sm">
                    <span class="font-semibold text-brand-100">{{ current.completed }}/{{ current.total }} lessons done</span>
                    <span class="font-display text-2xl font-extrabold">{{ current.percent }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20" role="progressbar" :aria-valuenow="current.percent" aria-valuemin="0" aria-valuemax="100" aria-label="Course progress">
                    <div class="h-full rounded-full bg-success" :style="{ width: `${current.percent}%` }" />
                </div>
                <Link :href="route('learning.courses.index', { course: current.id })" class="btn-accent mt-5 w-full">
                    Continue course <Icon name="arrow-right" :size="16" />
                </Link>
            </section>
            <section v-else class="card p-5 text-sm text-muted">
                <p class="font-bold text-ink">No course yet</p>
                <p class="mt-1">When your teacher adds a course to your class, it shows up here. Meanwhile, have a look around.</p>
            </section>
        </aside>

        <div class="min-w-0">
            <header class="mb-6">
                <p class="eyebrow mb-1">Course catalog</p>
                <h1 class="font-display text-3xl leading-tight font-extrabold tracking-tight text-ink sm:text-4xl">
                    Find courses <span class="block text-accent-600">to help you grow.</span>
                </h1>
                <p class="mt-2 max-w-2xl text-muted">See what else you can learn. Like one? Tap <b class="text-ink">I'm interested</b> on the course and we will tell your teacher and parents.</p>
            </header>

            <nav v-if="subjects.length > 1" class="no-scrollbar mb-5 flex gap-2 overflow-x-auto pb-1" aria-label="Filter by subject">
                <button
                    v-for="s in ['all', ...subjects]"
                    :key="s"
                    type="button"
                    :aria-pressed="subject === s"
                    :class="subject === s ? 'bg-brand-600 text-white' : 'bg-white text-ink ring-1 ring-line hover:bg-chip'"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-bold transition"
                    @click="subject = s"
                >
                    {{ s === 'all' ? 'All' : s }}
                </button>
            </nav>

            <ul v-if="shown.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <li v-for="c in shown" :key="c.id">
                    <Link :href="hrefFor(c)" class="card group flex h-full flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-brand-300">
                        <CourseCover :course="c">
                            <span v-if="c.mine" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-700"><Icon name="check" :size="12" /> Your course</span>
                            <span v-else-if="c.interested" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-accent-700"><Icon name="sparkles" :size="12" /> Interested</span>
                        </CourseCover>
                        <div class="flex flex-1 flex-col p-5">
                            <h2 class="font-display text-lg font-extrabold text-ink group-hover:text-brand-600">{{ c.title }}</h2>
                            <p v-if="c.description" class="mt-1 line-clamp-2 text-sm text-muted">{{ c.description }}</p>
                            <div v-if="c.mine" class="mt-4">
                                <div class="h-1.5 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full bg-success" :style="{ width: `${c.percent}%` }" /></div>
                                <p class="mt-1 text-xs font-semibold text-muted">{{ c.percent }}% done</p>
                            </div>
                            <p class="mt-auto flex flex-wrap gap-x-3 gap-y-1 pt-4 text-xs font-semibold text-muted">
                                <span>{{ c.lessons_count }} {{ c.lessons_count === 1 ? 'lesson' : 'lessons' }}</span>
                                <span v-if="c.minutes">{{ hours(c.minutes) }}</span>
                                <span>{{ c.level_label }}</span>
                            </p>
                        </div>
                    </Link>
                </li>
            </ul>
            <div v-else class="card">
                <EmptyState icon="compass" title="No courses to show yet" description="New courses appear here as soon as they are published." />
            </div>
        </div>
    </div>
</template>
