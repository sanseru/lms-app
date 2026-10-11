<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';

const props = defineProps({ sessions: Object, tab: String, counts: Object });

const tabs = [
    ['needs_report', 'Needs report'],
    ['upcoming', 'Upcoming'],
    ['completed', 'Completed'],
    ['flagged', 'Flagged'],
    ['cancelled', 'Cancelled'],
];
const empty = {
    needs_report: ['All attendance is filled in', 'Great job! No sessions left behind.'],
    upcoming: ['No scheduled sessions yet', 'Sessions are created automatically from the weekly class schedule.'],
    completed: ['No completed sessions yet', null],
    flagged: ['No flagged sessions', 'Teachers can flag a session with problems while filling in attendance.'],
    cancelled: ['No cancelled sessions', null],
};
</script>

<template>
    <Head title="Sessions & attendance" />

    <PageHeader eyebrow="Academic" title="Sessions & attendance" description="Fill in attendance and notes for each session on one screen. Student quotas and tutor fees are recorded automatically." />

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <Link
            v-for="[key, label] in tabs"
            :key="key"
            :href="route('sessions.index', { tab: key })"
            preserve-state
            :class="tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
        >
            {{ label }}
            <span v-if="counts[key]" :class="tab === key ? 'bg-white/25' : 'bg-amber-100 text-amber-800'" class="rounded-full px-2 text-xs">{{ counts[key] }}</span>
        </Link>
    </div>

    <div class="card overflow-hidden">
        <div v-if="sessions.data.length" class="space-y-2 p-4">
            <SessionItem v-for="s in sessions.data" :key="s.id" :session="s" show-date />
        </div>
        <EmptyState v-else icon="clipboard-check" :title="empty[tab][0]" :description="empty[tab][1]" />
        <Pagination :meta="sessions" />
    </div>
</template>
