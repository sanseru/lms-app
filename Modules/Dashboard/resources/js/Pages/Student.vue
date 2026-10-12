<script setup>
import { greetName } from '@/lib/format';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import LastSessionCard from '@/Components/ui/LastSessionCard.vue';
import QuotaCard from '@/Components/ui/QuotaCard.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import LearningCard from '@/Components/ui/LearningCard.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import LevelCard from '@/Components/ui/LevelCard.vue';
import Icon from '@/Components/ui/Icon.vue';
import ProjectCard from '../../../../Lab/resources/js/Components/ProjectCard.vue';

defineProps({ creator: { type: Object, default: null }, popular: { type: Array, default: () => [] }, upcoming: Array, lastSession: Object, classes: Array, packages: Array, stats: Object, profile: Object, learning: Array, projects: Array, achievements: Object });
const user = usePage().props.auth.user;

// The six numbers from the client prototype's student home.
const creatorTiles = (c) => [
    ['Projects', c.projects], ['Likes received', c.likes], ['Views', c.views],
    ['Comments', c.comments], ['Lessons done', c.lessons], ['Questions solved', c.solved],
];
const hasLab = route().has('community.show');

</script>

<template>
    <Head title="Home" />

    <div class="panel-brand p-6 sm:p-8">
        <div class="relative flex items-center gap-4">
            <Avatar :initials="user.initials" :seed="user.id" size="lg" class="!bg-accent-500 !text-ink ring-4 ring-white/30" />
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Hi, {{ greetName(user.name) }}!</h1>
                <p class="text-white/85">Ready to code today?</p>
            </div>
        </div>
        <div class="relative mt-6 flex flex-wrap gap-3">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-white/10"><Icon name="school" :size="16" class="text-accent-300" /> {{ classes.length }} {{ classes.length === 1 ? 'class' : 'classes' }}</span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-white/10"><Icon name="circle-check" :size="16" class="text-accent-300" /> {{ stats.present }} {{ stats.present === 1 ? 'session' : 'sessions' }} attended</span>
            <span v-if="stats.rate !== null" class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold ring-1 ring-white/10"><Icon name="target" :size="16" class="text-accent-300" /> Attendance {{ stats.rate }}%</span>
            <span v-if="profile.code" class="rounded-full bg-white/15 px-3 py-1 font-mono text-sm ring-1 ring-white/10">{{ profile.code }}</span>
        </div>
    </div>

    <!-- Client prototype: six stat tiles under the banner -->
    <ul v-if="creator" class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <li v-for="[label, value] in creatorTiles(creator)" :key="label" class="card px-3 py-4 text-center">
            <p class="font-display text-3xl font-extrabold text-brand-600 tabular-nums">{{ value }}</p>
            <p class="mt-1 text-sm text-muted">{{ label }}</p>
        </li>
    </ul>

    <div v-if="achievements" class="mt-6 grid gap-4 lg:grid-cols-3">
        <Link :href="route('gamification.me')" class="lg:col-span-2"><LevelCard :level="achievements.level" compact class="h-full transition hover:shadow-lg" /></Link>
        <Link :href="route('gamification.me')" class="card flex flex-col justify-center p-5 transition hover:shadow-md">
            <p class="font-display font-bold text-ink">Badges <span class="tabular-nums text-muted">{{ achievements.badges_earned }}/{{ achievements.badges_total }}</span></p>
            <p v-if="achievements.badges.length" class="mt-2 flex flex-wrap gap-1 text-3xl"><span v-for="b in achievements.badges" :key="b.name" :title="b.name" role="img" :aria-label="b.name">{{ b.emoji }}</span></p>
            <p v-else class="mt-2 text-sm text-muted">Finish your first lesson to earn your first badge.</p>
        </Link>
    </div>

    <div v-if="learning.length" class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold text-ink">Keep learning</h2>
            <Link :href="route('learning.courses.index')" class="text-sm font-semibold text-brand-600 hover:underline">All courses →</Link>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <LearningCard v-for="c in learning" :key="c.id" :course="c" />
        </div>
    </div>

    <div v-if="projects.length" class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold text-ink">My projects</h2>
            <Link :href="route('kanban.projects.index')" class="text-sm font-semibold text-brand-600 hover:underline">All projects →</Link>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <Link v-for="p in projects" :key="p.board.id" :href="route('kanban.boards.show', p.board.id)" class="card flex items-center gap-4 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
                <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand-50 text-3xl">{{ p.emoji }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate font-display font-bold text-ink">{{ p.title }}</span>
                    <span class="mb-2 block text-xs text-muted">{{ p.board.progress.done }} of {{ p.board.progress.total }} steps<template v-if="p.due_date"> · due {{ p.due_date }}</template></span>
                    <ProgressBar :value="p.board.progress.percent" size="sm" :tone="p.board.completed ? 'green' : 'brand'" />
                </span>
            </Link>
        </div>
    </div>

    <div v-if="hasLab" class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold text-ink">Popular in Community</h2>
            <Link :href="route('community.index')" class="text-sm font-semibold text-brand-600 hover:underline">See all →</Link>
        </div>
        <div v-if="popular.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <ProjectCard v-for="p in popular" :key="p.id" :project="p" :href="route('community.show', p.id)" />
        </div>
        <div v-else class="card flex items-center gap-4 p-5">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-accent-50 text-accent-700"><Icon name="flask" /></span>
            <p class="flex-1 text-sm text-ink">Nothing here yet. Be the first to share a project from the Laboratory!</p>
            <Link :href="route('lab.projects.index')" class="btn-ghost">Open Laboratory</Link>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-bold text-ink">Next sessions</h2>
                <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Online Lesson →</Link>
            </div>
            <div v-if="upcoming.length" class="space-y-2">
                <SessionItem v-for="s in upcoming" :key="s.id" :session="s" show-date />
            </div>
            <div v-else class="flex items-center gap-4 rounded-xl bg-sky-soft p-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-white text-sky-ink"><Icon name="calendar-days" /></span>
                <p class="text-sm text-ink">No sessions scheduled yet. Take a break or review your last lesson!</p>
            </div>
        </div>
        <div class="space-y-4">
            <div v-if="lastSession">
                <h2 class="mb-2 font-display font-bold text-ink">Last session</h2>
                <LastSessionCard :session="lastSession" />
            </div>
            <div v-if="packages.length">
                <h2 class="mb-2 font-display font-bold text-ink">Learning quota</h2>
                <div class="space-y-3"><QuotaCard v-for="p in packages" :key="p.id" :pkg="p" /></div>
            </div>
        </div>
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">My classes</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="You don't have any classes yet" description="Your admin will add you to a class soon." /></div>

</template>
