<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import CourseCover from '../../Components/CourseCover.vue';

/**
 * Course catalog, laid out like the client prototype: a "Selected course" panel on the
 * left and one card per program (Data, Python, Scratch, …) on the right. Opening a
 * program shows its levels; levels of the student's own program show progress or a lock.
 */
const props = defineProps({
    courses: Array,
    programs: { type: Array, default: () => [] },
    current: { type: Object, default: null },
});

const levelsOf = (programId) => props.courses.filter((c) => c.program?.id === programId);
// One catalog card per program with at least one published level, plus courses without a program.
const cards = computed(() => [
    ...props.programs
        .map((p) => ({ ...p, levels: levelsOf(p.id) }))
        .filter((p) => p.levels.length)
        .map((p) => ({
            key: `p${p.id}`,
            program: p,
            title: p.name,
            category: p.category ?? 'Program',
            description: p.description ?? p.levels[0].description,
            color: p.color,
            subject: p.name,
            meta: [`${p.levels.length} ${p.levels.length === 1 ? 'Level' : 'Levels'}`, `${p.levels.reduce((n, l) => n + l.lessons_count, 0)} Lessons`, p.levels[0].level_label],
            mine: p.levels.some((l) => l.mine),
        })),
    ...props.courses.filter((c) => !c.program).map((c) => ({
        key: `c${c.id}`,
        course: c,
        title: c.title,
        category: c.subject,
        description: c.description,
        color: c.color,
        subject: c.subject,
        meta: [`${c.lessons_count} Lessons`, c.level_label],
        mine: c.mine,
    })),
]);

const openProgram = ref(null);
const program = computed(() => cards.value.find((c) => c.key === openProgram.value)?.program ?? null);

// Own courses open the course map; the rest open the read-only overview.
const hrefFor = (c) => (c.mine ? route('learning.courses.index', { course: c.id }) : route('learning.courses.show', c.id));
const hours = (m) => (m >= 60 ? `${Math.round(m / 60)} h` : `${m} min`);
</script>

<template>
    <Head title="Course catalog" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] lg:items-start">
        <!-- Selected course (client prototype): the course the student is taking now -->
        <aside class="lg:sticky lg:top-28">
            <section v-if="current" class="rounded-2xl border border-accent-200 bg-accent-50 p-6">
                <p class="text-[11px] font-extrabold tracking-[0.12em] text-accent-700 uppercase">Selected course</p>
                <span class="mt-3 grid size-12 place-items-center rounded-xl bg-accent-100 font-display text-lg font-extrabold text-brand-700">{{ (current.subject ?? current.title).slice(0, 2) }}</span>
                <h2 class="mt-3 font-display text-xl leading-tight font-extrabold text-ink">{{ current.title }}</h2>
                <p v-if="current.description" class="mt-2 line-clamp-3 text-sm text-muted">{{ current.description }}</p>
                <div class="mt-4 flex items-center justify-between border-t border-accent-200 pt-3 text-sm">
                    <span class="text-muted">Progress</span>
                    <span class="font-bold text-accent-700">{{ current.percent }}%</span>
                </div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white" role="progressbar" :aria-valuenow="current.percent" aria-valuemin="0" aria-valuemax="100" aria-label="Course progress">
                    <div class="h-full rounded-full bg-accent-500" :style="{ width: `${current.percent}%` }" />
                </div>
                <Link :href="route('learning.courses.index', { course: current.id })" class="btn-accent mt-5 w-full">Continue course <Icon name="arrow-right" :size="16" /></Link>
            </section>
            <section v-else class="card p-5 text-sm text-muted">
                <p class="font-bold text-ink">No course yet</p>
                <p class="mt-1">When your teacher adds a course to your class, it shows up here. Meanwhile, have a look around.</p>
            </section>
        </aside>

        <div class="min-w-0">
            <!-- Catalog: one card per program -->
            <template v-if="!program">
                <header class="mb-6">
                    <p class="eyebrow mb-1">Learning catalog</p>
                    <h1 class="font-display text-4xl leading-tight font-extrabold tracking-tight text-ink sm:text-5xl">
                        Find courses <span class="block text-accent-600">to help you grow.</span>
                    </h1>
                    <p class="mt-3 max-w-2xl text-muted">Pick the course you want to explore. Like one you don't take yet? Tap <b class="text-ink">I'm interested</b> and we will tell your teacher and parents.</p>
                </header>

                <ul v-if="cards.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <li v-for="card in cards" :key="card.key">
                        <component
                            :is="card.program ? 'button' : Link"
                            v-bind="card.program ? { type: 'button' } : { href: hrefFor(card.course) }"
                            class="card group flex h-full w-full flex-col overflow-hidden text-left transition hover:-translate-y-1 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-brand-300"
                            @click="card.program && (openProgram = card.key)"
                        >
                            <CourseCover :course="card">
                                <span v-if="card.mine" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-700"><Icon name="check" :size="12" /> Your course</span>
                            </CourseCover>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-[11px] font-extrabold tracking-[0.12em] text-accent-700 uppercase">{{ card.category }}</p>
                                <h2 class="mt-1 font-display text-lg font-extrabold text-ink group-hover:text-brand-600">{{ card.title }}</h2>
                                <p v-if="card.description" class="mt-1 line-clamp-2 text-sm text-muted">{{ card.description }}</p>
                                <p class="mt-auto pt-4 text-xs font-semibold text-muted">{{ card.meta.filter(Boolean).join(' • ') }}</p>
                            </div>
                        </component>
                    </li>
                </ul>
                <div v-else class="card">
                    <EmptyState icon="compass" title="No courses to show yet" description="New courses appear here as soon as they are published." />
                </div>
            </template>

            <!-- One program: its levels in order -->
            <template v-else>
                <button type="button" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600" @click="openProgram = null">
                    <Icon name="arrow-left" :size="16" /> Course catalog
                </button>
                <header class="mb-6">
                    <p class="eyebrow mb-1">{{ program.category ?? 'Program' }}</p>
                    <h1 class="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-4xl">{{ program.name }}</h1>
                    <p v-if="program.description" class="mt-2 max-w-2xl text-muted">{{ program.description }}</p>
                </header>
                <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <li v-for="(c, i) in program.levels" :key="c.id">
                        <Link :href="hrefFor(c)" class="card group flex h-full flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-brand-300">
                            <CourseCover :course="c">
                                <span v-if="c.mine" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-emerald-700"><Icon name="check" :size="12" /> Your course</span>
                                <span v-else-if="c.unlock_after" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-ink"><Icon name="lock" :size="12" /> Locked</span>
                                <span v-else-if="c.interested" class="absolute top-3 right-3 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-0.5 text-xs font-bold text-accent-700"><Icon name="sparkles" :size="12" /> Interested</span>
                            </CourseCover>
                            <div class="flex flex-1 flex-col p-5">
                                <p class="text-xs font-bold tracking-wide text-muted uppercase">Level {{ i + 1 }}</p>
                                <h2 class="font-display text-lg font-extrabold text-ink group-hover:text-brand-600">{{ c.title }}</h2>
                                <p v-if="c.unlock_after" class="mt-1 text-xs font-semibold text-accent-700">Finish {{ c.unlock_after }} to unlock</p>
                                <p v-if="c.description" class="mt-1 line-clamp-2 text-sm text-muted">{{ c.description }}</p>
                                <div v-if="c.mine" class="mt-4">
                                    <div class="h-1.5 overflow-hidden rounded-full bg-chip"><div class="h-full rounded-full bg-success" :style="{ width: `${c.percent}%` }" /></div>
                                    <p class="mt-1 text-xs font-semibold text-muted">{{ c.percent }}% done</p>
                                </div>
                                <p class="mt-auto pt-4 text-xs font-semibold text-muted">{{ [`${c.lessons_count} ${c.lessons_count === 1 ? 'lesson' : 'lessons'}`, c.minutes ? hours(c.minutes) : null, c.level_label].filter(Boolean).join(' • ') }}</p>
                            </div>
                        </Link>
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>
