<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ days: Array, week: Object, calendarUrl: String, highlights: { type: Object, default: null } });

// Client prototype cards: LIVE NOW, the next class, and the latest recording (real sessions).
const cards = computed(() => {
    const h = props.highlights;
    if (!h) return [];
    return [
        h.live && { key: 'live', tag: 'Live now', tone: 'bg-accent-50 text-accent-700', session: h.live, meta: `Today, ${h.live.start_time}–${h.live.end_time} WIB`, href: h.live.zoom_url, external: !!h.live.zoom_url, action: h.live.zoom_url ? 'Join on Zoom' : 'See details' },
        h.next && { key: 'next', tag: h.next.this_week ? 'This week' : 'Coming up', tone: 'bg-sky-50 text-sky-800', session: h.next, meta: `${h.next.date_label}, ${h.next.start_time} WIB`, action: 'See details' },
        h.recording && { key: 'rec', tag: 'Recording', tone: 'bg-emerald-50 text-emerald-800', session: h.recording, meta: `${h.recording.minutes} minutes · ${h.recording.date_label}`, href: h.recording.recording_url, external: true, action: 'Watch lesson' },
    ].filter(Boolean);
});

const copied = ref(false);
const copyCalendar = async () => {
    try {
        await navigator.clipboard.writeText(props.calendarUrl);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* clipboard not available */
    }
};
const makeCalendar = () => router.post(route('calendar.regenerate'), {}, { preserveScroll: true });
const total = computed(() => props.days.reduce((n, d) => n + d.sessions.filter((s) => !['cancelled', 'rescheduled'].includes(s.status)).length, 0));
const isStudent = usePage().props.auth.user?.role === 'student';
</script>

<template>
    <Head :title="isStudent ? 'Online Lesson' : 'Schedule'" />

    <!-- Students know this page as "Online Lesson" (client prototype): their live Zoom classes. -->
    <PageHeader
        :eyebrow="isStudent ? 'Learn live' : 'Academic'"
        :title="isStudent ? 'Online Lesson' : 'Weekly schedule'"
        :description="isStudent ? `Your live classes on Zoom this week (${total}). Join from here when it is time. All times in WIB.` : `${total} class sessions this week. All times in WIB.`"
    >
        <template #actions>
            <div class="flex items-center gap-1 rounded-xl border border-line bg-white p-1">
                <Link :href="route('schedule.index', { week: week.prev })" class="rounded-lg p-2 text-muted hover:bg-chip hover:text-ink" preserve-scroll aria-label="Previous week">
                    <Icon name="chevron-left" />
                </Link>
                <span class="px-2 text-sm font-semibold text-ink">{{ week.label }}</span>
                <Link :href="route('schedule.index', { week: week.next })" class="rounded-lg p-2 text-muted hover:bg-chip hover:text-ink" preserve-scroll aria-label="Next week">
                    <Icon name="chevron-right" />
                </Link>
            </div>
            <Link :href="route('schedule.index')" class="btn-ghost">This week</Link>
        </template>
    </PageHeader>

    <div v-if="cards.length" class="mb-6 grid gap-4 md:grid-cols-3">
        <article v-for="c in cards" :key="c.key" :class="c.key === 'live' && 'ring-2 ring-accent-200'" class="card flex flex-col p-5">
            <span :class="c.tone" class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-extrabold tracking-[0.12em] uppercase">
                <span v-if="c.key === 'live'" class="size-2 animate-pulse rounded-full bg-accent-500" aria-hidden="true" />{{ c.tag }}
            </span>
            <h2 class="mt-3 font-display text-lg leading-tight font-extrabold text-ink">{{ c.session.class.name }}</h2>
            <p class="mt-1 text-sm text-muted">{{ c.session.topic || (c.session.teacher ? `With ${c.session.teacher.name}` : c.session.class.subject) }}</p>
            <div class="mt-auto flex flex-wrap items-center justify-between gap-2 border-t border-line pt-3 text-sm">
                <span class="text-muted">{{ c.meta }}</span>
                <a v-if="c.external" :href="c.href" target="_blank" rel="noopener" class="font-bold text-brand-600 hover:underline">{{ c.action }} →</a>
                <Link v-else :href="route('sessions.show', c.session.id)" class="font-bold text-brand-600 hover:underline">{{ c.action }} →</Link>
            </div>
        </article>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
        <section
            v-for="day in days"
            :key="day.date"
            :class="day.is_today ? 'border-brand-300 ring-2 ring-brand-100' : 'border-line'"
            class="flex min-h-40 flex-col rounded-2xl border bg-white"
        >
            <header class="flex flex-wrap items-baseline justify-between gap-x-2 border-b border-line px-4 py-3">
                <p :class="day.is_today ? 'text-brand-600' : 'text-ink'" class="font-display font-bold">{{ day.day }}</p>
                <p class="text-xs text-muted">{{ day.label }}</p>
                <p v-if="day.is_today" class="w-full text-xs font-extrabold tracking-wider text-accent-600 uppercase">Today</p>
            </header>
            <div class="flex flex-1 flex-col gap-2 p-3">
                <Link
                    v-for="s in day.sessions"
                    :key="s.id"
                    :href="route('sessions.show', s.id)"
                    :class="[
                        ['cancelled', 'rescheduled'].includes(s.status) ? 'bg-chip ring-line opacity-60' : [classColors[s.class.color]?.bg, classColors[s.class.color]?.ring],
                    ]"
                    class="block rounded-xl p-3 ring-1 transition ring-inset hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between gap-1">
                        <p :class="classColors[s.class.color]?.text" class="font-mono text-xs font-medium">{{ s.start_time }}–{{ s.end_time }}</p>
                        <span v-if="s.status === 'completed'" class="text-emerald-700" title="Completed"><Icon name="check" :size="14" /></span>
                        <span v-else-if="s.needs_report" class="size-2 rounded-full bg-amber-500" title="Attendance not filled in yet" />
                    </div>
                    <p class="mt-0.5 text-sm leading-snug font-bold text-ink" :class="['cancelled', 'rescheduled'].includes(s.status) && 'line-through'">{{ s.class.name }}</p>
                    <p v-if="s.teacher" class="mt-1 truncate text-xs text-muted">{{ s.teacher.name }}</p>
                </Link>
                <p v-if="!day.sessions.length" class="m-auto text-xs text-muted">No classes</p>
            </div>
        </section>
    </div>

    <div class="card mt-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><Icon name="calendar-days" /></span>
        <div class="min-w-0 flex-1">
            <h2 class="font-display font-bold text-ink">Show the schedule in your phone calendar</h2>
            <p class="text-sm text-muted">
                Subscribe to this link in Google Calendar (Add calendar → From URL), Apple Calendar or Outlook. The schedule and Zoom links update automatically.
            </p>
            <p v-if="calendarUrl" class="mt-2 truncate rounded-lg bg-chip px-3 py-1.5 font-mono text-xs text-ink">{{ calendarUrl }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <template v-if="calendarUrl">
                <button class="btn-primary" @click="copyCalendar"><Icon name="link" :size="16" /> {{ copied ? 'Copied' : 'Copy link' }}</button>
                <button class="btn-ghost" title="The old link will stop working" @click="makeCalendar">Regenerate</button>
            </template>
            <button v-else class="btn-primary" @click="makeCalendar"><Icon name="plus" :size="16" /> Create calendar link</button>
        </div>
    </div>
</template>
