<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { compactRupiah, rupiah } from '@/lib/format';

const props = defineProps({ stats: Object, months: Array, pending: Array, lowQuota: Array });

const max = computed(() => Math.max(1, ...props.months.flatMap((m) => [m.billed, m.received])));
</script>

<template>
    <Head title="Finance overview" />

    <PageHeader eyebrow="Finance" title="Finance overview" description="Manual bank transfers from parents, recorded and verified.">
        <template #actions>
            <Link :href="route('finance.invoices.index')" class="btn-ghost"><Icon name="receipt" :size="18" /> All invoices</Link>
            <Link :href="route('finance.invoices.create')" class="btn-accent"><Icon name="plus" :size="18" /> New invoice</Link>
        </template>
    </PageHeader>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
        <StatCard label="Received this month" :value="rupiah(stats.received_month)" icon="wallet" tone="green" />
        <StatCard label="Outstanding" :value="rupiah(stats.outstanding)" icon="money" tone="amber" />
        <StatCard label="Overdue invoices" :value="stats.overdue_count" icon="alert-triangle" tone="red" />
        <StatCard label="Awaiting verification" :value="stats.pending_count" icon="badge-check" tone="brand" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="card p-6 lg:col-span-3">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-display font-bold text-ink">Last 6 months</h2>
                <div class="flex gap-4 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand-200" /> Billed</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand-600" /> Received</span>
                </div>
            </div>
            <div class="flex h-56 items-end gap-3 sm:gap-6">
                <div v-for="m in months" :key="m.label" class="flex h-full flex-1 flex-col items-center gap-2">
                    <div class="flex w-full flex-1 items-end justify-center gap-1">
                        <div class="w-1/2 max-w-6 rounded-t-md bg-brand-200 transition-all" :style="{ height: `${(m.billed / max) * 100}%` }" :title="`Billed ${rupiah(m.billed)}`" />
                        <div class="w-1/2 max-w-6 rounded-t-md bg-brand-600 transition-all" :style="{ height: `${(m.received / max) * 100}%` }" :title="`Received ${rupiah(m.received)}`" />
                    </div>
                    <p class="text-xs font-semibold text-muted">{{ m.label }}</p>
                    <p class="hidden text-xs text-muted sm:block">{{ compactRupiah(m.received) }}</p>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-display font-bold text-ink">Needs verification</h2>
                <p class="text-sm text-muted">Transfer proofs from parents.</p>
            </div>
            <ul v-if="pending.length" class="divide-y divide-line">
                <li v-for="p in pending" :key="p.id">
                    <Link :href="route('finance.invoices.show', p.invoice_id)" class="flex items-center gap-3 px-5 py-3 hover:bg-chip/50">
                        <span class="grid size-9 place-items-center rounded-lg bg-amber-50 text-amber-600"><Icon name="upload" :size="16" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ p.student }}</p>
                            <p class="text-xs text-muted">{{ p.invoice_number }} · {{ p.paid_at }}<template v-if="p.bank_name"> · {{ p.bank_name }}</template></p>
                        </div>
                        <p class="font-display text-sm font-bold text-ink">{{ rupiah(p.amount) }}</p>
                    </Link>
                </li>
            </ul>
            <EmptyState v-else icon="badge-check" title="All caught up" description="No transfer proofs waiting." />
        </div>
    </div>

    <div class="card mt-6 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line px-5 py-4">
            <div>
                <h2 class="font-display font-bold text-ink">Packages running low</h2>
                <p class="text-sm text-muted">2 sessions or fewer left, or expiring within 14 days. Time to offer a renewal.</p>
            </div>
            <Link :href="route('finance.packages.index')" class="btn-ghost px-3 py-2"><Icon name="ticket" :size="16" /> Manage packages</Link>
        </div>
        <ul v-if="lowQuota.length" class="divide-y divide-line">
            <li v-for="p in lowQuota" :key="p.id" class="flex flex-wrap items-center gap-3 px-5 py-3">
                <span
                    :class="p.remaining === 0 ? 'bg-rose-50 text-rose-600' : 'bg-amber-50 text-amber-600'"
                    class="grid size-10 place-items-center rounded-xl font-display text-lg font-extrabold"
                    :title="`${p.remaining} session(s) left`"
                >
                    {{ p.remaining }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-semibold text-ink">{{ p.student?.name }}</p>
                    <p class="text-xs text-muted">{{ p.name }}<template v-if="p.class"> · {{ p.class }}</template> · used {{ p.used }}/{{ p.total }}</p>
                </div>
                <span v-if="p.expires_at" :class="p.expiring ? 'text-rose-600' : 'text-muted'" class="text-xs font-semibold">Valid until {{ p.expires_at }}</span>
            </li>
        </ul>
        <EmptyState v-else icon="ticket" title="Quota looks good" description="No student packages are running low." />
    </div>
</template>
