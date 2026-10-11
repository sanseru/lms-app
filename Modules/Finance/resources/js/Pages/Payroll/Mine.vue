<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { rupiah } from '@/lib/format';

const props = defineProps({ rate: Number, pending: Array, payouts: Array });
const pendingTotal = computed(() => props.pending.reduce((n, i) => n + i.amount, 0));
const paidTotal = computed(() => props.payouts.filter((p) => p.status === 'paid').reduce((n, p) => n + p.total, 0));
</script>

<template>
    <Head title="My fees" />

    <PageHeader eyebrow="Finance" title="My fees" :description="`You earn ${rupiah(rate)} per session, recorded automatically each time you complete a session's attendance.`" />

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Not yet bundled" :value="rupiah(pendingTotal)" :hint="`${pending.length} sessions`" icon="clipboard-check" tone="amber" />
        <StatCard label="Unpaid pay slips" :value="rupiah(payouts.filter((p) => p.status === 'unpaid').reduce((n, p) => n + p.total, 0))" icon="receipt" tone="brand" />
        <StatCard label="Total paid" :value="rupiah(paidTotal)" icon="hand-coins" tone="green" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="font-display font-bold text-ink">Pay slips</h2></div>
            <ul v-if="payouts.length" class="divide-y divide-line">
                <li v-for="p in payouts" :key="p.id">
                    <Link :href="route('finance.payroll.show', p.id)" class="flex items-center gap-3 px-5 py-3 hover:bg-chip/50">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink">{{ p.period }}</p>
                            <p class="font-mono text-xs text-muted">{{ p.number }} · {{ p.sessions_count }} sessions</p>
                        </div>
                        <p class="font-display font-bold text-ink">{{ rupiah(p.total) }}</p>
                        <StatusBadge :status="p.status === 'paid' ? 'paid' : 'unpaid'" :label="p.status_label" />
                    </Link>
                </li>
            </ul>
            <EmptyState v-else icon="receipt" title="No pay slips yet" description="The admin bundles fees at the end of each month." />
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4"><h2 class="font-display font-bold text-ink">Sessions not yet bundled</h2></div>
            <ul v-if="pending.length" class="divide-y divide-line">
                <li v-for="i in pending" :key="i.id" class="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                    <span><span class="block text-ink">{{ i.description }}</span><span class="text-xs text-muted">{{ i.date }}</span></span>
                    <span class="font-semibold text-ink">{{ rupiah(i.amount) }}</span>
                </li>
            </ul>
            <EmptyState v-else icon="badge-check" title="All bundled" />
        </div>
    </div>
</template>
