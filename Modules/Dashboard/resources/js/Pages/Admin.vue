<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { greeting, rupiah, greetName } from '@/lib/format';

defineProps({ stats: Object, today: Array, alerts: Object, pendingPayments: Array, recentStudents: Array });
const user = usePage().props.auth.user;
const todayLabel = new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
    <Head title="Home" />

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">{{ todayLabel }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink">{{ greeting() }}, {{ greetName(user.name) }}</h1>
            <p class="mt-1 text-muted">Here's today's overview of the school.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Link :href="route('people.students.create')" class="btn-ghost"><Icon name="user-plus" :size="18" /> New student</Link>
            <Link :href="route('classes.create')" class="btn-accent"><Icon name="plus" :size="18" /> New class</Link>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Active students" :value="stats.students" icon="graduation-cap" tone="brand" />
        <StatCard label="Active teachers" :value="stats.teachers" icon="presentation" tone="accent" />
        <StatCard label="Running classes" :value="stats.classes" icon="school" tone="sky" />
        <StatCard label="Unpaid invoices" :value="rupiah(stats.outstanding)" icon="wallet" tone="amber" />
    </div>

    <div v-if="alerts.needs_report || alerts.flagged || alerts.low_quota" class="mt-6 grid gap-3 sm:grid-cols-3">
        <Link v-if="alerts.needs_report" :href="route('sessions.index', { tab: 'needs_report' })" class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100/70">
            <span class="grid size-10 place-items-center rounded-xl bg-white text-amber-600"><Icon name="clipboard-check" /></span>
            <span><b class="font-display text-ink">{{ alerts.needs_report }} {{ alerts.needs_report === 1 ? 'session' : 'sessions' }}</b><span class="block text-sm text-muted">without attendance yet</span></span>
        </Link>
        <Link v-if="alerts.flagged" :href="route('sessions.index', { tab: 'flagged' })" class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 hover:bg-rose-100/70">
            <span class="grid size-10 place-items-center rounded-xl bg-white text-rose-600"><Icon name="flag" /></span>
            <span><b class="font-display text-ink">{{ alerts.flagged }} flagged {{ alerts.flagged === 1 ? 'session' : 'sessions' }}</b><span class="block text-sm text-muted">need follow-up</span></span>
        </Link>
        <Link v-if="alerts.low_quota" :href="route('finance.packages.index', { status: 'low' })" class="flex items-center gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 hover:bg-brand-100/70">
            <span class="grid size-10 place-items-center rounded-xl bg-white text-brand-600"><Icon name="ticket" /></span>
            <span><b class="font-display text-ink">{{ alerts.low_quota }} {{ alerts.low_quota === 1 ? 'package' : 'packages' }}</b><span class="block text-sm text-muted">with 2 or fewer sessions left</span></span>
        </Link>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-ink">Today's classes</h2>
                    <p class="text-sm text-muted">{{ today.length }} {{ today.length === 1 ? 'session' : 'sessions' }} scheduled</p>
                </div>
                <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">View schedule →</Link>
            </div>
            <div v-if="today.length" class="space-y-2">
                <SessionItem v-for="s in today" :key="s.id" :session="s" />
            </div>
            <EmptyState v-else icon="calendar-days" title="No classes today" description="A good time to prepare lessons or tidy up data." />
        </div>

        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-display font-bold text-ink">New transfer proofs</h2>
                    <Link :href="route('finance.overview')" class="text-sm font-semibold text-brand-600 hover:underline">Finance →</Link>
                </div>
                <ul v-if="pendingPayments.length" class="divide-y divide-line">
                    <li v-for="p in pendingPayments" :key="p.id">
                        <Link :href="route('finance.invoices.show', p.invoice_id)" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-chip/50">
                            <span class="min-w-0"><span class="block truncate font-semibold text-ink">{{ p.student }}</span><span class="text-xs text-muted">{{ p.paid_at }}</span></span>
                            <span class="font-display text-sm font-bold text-ink">{{ rupiah(p.amount) }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-5 py-6 text-center text-sm text-muted">Nothing to verify.</p>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4"><h2 class="font-display font-bold text-ink">Newest students</h2></div>
                <ul class="divide-y divide-line">
                    <li v-for="s in recentStudents" :key="s.id" class="flex items-center gap-3 px-5 py-3">
                        <Avatar :initials="s.initials" :seed="s.id" size="sm" />
                        <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink">{{ s.name }}</span><span class="font-mono text-xs text-muted">{{ s.code }}</span></span>
                        <span class="text-xs text-muted">{{ s.joined }}</span>
                    </li>
                    <li v-if="!recentStudents.length" class="px-5 py-6 text-center text-sm text-muted">No students yet.</li>
                </ul>
            </div>
        </div>
    </div>
</template>
