<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { greeting, rupiah, greetName } from '@/lib/format';

defineProps({ stats: Object, today: Array, needsReport: Array, classes: Array });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Home" />

    <div class="panel-brand mb-2 p-6 sm:p-8">
        <h1 class="text-3xl font-extrabold tracking-tight">{{ greeting() }}, {{ greetName(user.name) }}!</h1>
        <p class="mt-1 text-brand-100">{{ today.length ? `You have ${today.length} ${today.length === 1 ? 'session' : 'sessions'} today. Happy teaching!` : 'No sessions today. A good time to prepare lessons.' }}</p>
    </div>

    <Link v-if="stats.to_grade" :href="route('learning.grading.index')" class="mt-6 flex items-center gap-4 rounded-2xl border border-violet-200 bg-violet-50 p-5 transition hover:shadow-md">
        <span class="grid size-11 place-items-center rounded-xl bg-violet-100 text-violet-700"><Icon name="file-pen" /></span>
        <span class="flex-1">
            <span class="block font-display font-bold text-ink">{{ stats.to_grade }} {{ stats.to_grade === 1 ? 'assignment' : 'assignments' }} waiting to be graded</span>
            <span class="text-sm text-muted">Grade them or ask for a revision so students and parents can see the result.</span>
        </span>
        <Icon name="arrow-right" class="text-violet-700" />
    </Link>

    <Link v-if="stats.questions_waiting" :href="route('forum.questions.index', { tab: 'unanswered' })" class="mt-4 flex items-center gap-4 rounded-2xl border border-accent-200 bg-accent-50 p-5 transition hover:shadow-md">
        <span class="grid size-11 place-items-center rounded-xl bg-accent-100 text-accent-700"><Icon name="message-question" /></span>
        <span class="flex-1">
            <span class="block font-display font-bold text-ink">{{ stats.questions_waiting }} student {{ stats.questions_waiting === 1 ? 'question' : 'questions' }} not answered yet</span>
            <span class="text-sm text-muted">Answer them in Q&A. Teacher answers show at the top and can be pinned for the whole class.</span>
        </span>
        <Icon name="arrow-right" class="text-accent-700" />
    </Link>

    <Link v-if="stats.cards_to_review" :href="route('kanban.projects.index')" class="mt-4 flex items-center gap-4 rounded-2xl border border-sky-200 bg-sky-50 p-5 transition hover:shadow-md">
        <span class="grid size-11 place-items-center rounded-xl bg-sky-100 text-sky-700"><Icon name="kanban" /></span>
        <span class="flex-1">
            <span class="block font-display font-bold text-ink">{{ stats.cards_to_review }} project {{ stats.cards_to_review === 1 ? 'step' : 'steps' }} waiting for review</span>
            <span class="text-sm text-muted">Approve or send back with a note so students can keep going.</span>
        </span>
        <Icon name="arrow-right" class="text-sky-700" />
    </Link>

    <div v-if="needsReport.length" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <div class="mb-3 flex items-center gap-2">
            <Icon name="clipboard-check" class="text-amber-600" />
            <h2 class="font-display font-bold text-ink">{{ needsReport.length }} {{ needsReport.length === 1 ? 'session' : 'sessions' }} without attendance yet</h2>
        </div>
        <p class="mb-4 text-sm text-muted">Fill in attendance and notes so student quotas are deducted, parents get your notes and your fees are recorded.</p>
        <div class="space-y-2">
            <SessionItem v-for="s in needsReport" :key="s.id" :session="s" :show-teacher="false" show-date />
        </div>
    </div>

    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Active classes" :value="stats.classes" icon="school" tone="brand" />
        <StatCard label="Total students" :value="stats.students" icon="users" tone="accent" />
        <StatCard label="Sessions done this month" :value="stats.sessions_month" icon="clipboard-check" tone="sky" />
        <StatCard label="Fees not yet paid out" :value="rupiah(stats.fee_pending)" icon="hand-coins" tone="green" />
    </div>

    <div class="mt-6 card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display font-bold text-ink">Today's schedule</h2>
            <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Weekly schedule →</Link>
        </div>
        <div v-if="today.length" class="space-y-2">
            <SessionItem v-for="s in today" :key="s.id" :session="s" :show-teacher="false" />
        </div>
        <EmptyState v-else icon="calendar-days" title="No sessions today" />
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Classes you teach</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="No classes yet" description="Your admin will assign classes to you." /></div>
</template>
