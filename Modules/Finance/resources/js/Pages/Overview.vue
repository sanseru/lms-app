<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { compactRupiah, rupiah } from '@/lib/format';

const props = defineProps({ stats: Object, months: Array, pending: Array });

const max = computed(() => Math.max(1, ...props.months.flatMap((m) => [m.billed, m.received])));
</script>

<template>
    <Head title="Ringkasan Keuangan" />

    <PageHeader eyebrow="Keuangan" title="Ringkasan keuangan" description="Pembayaran via transfer manual dari orang tua, tercatat dan terverifikasi.">
        <template #actions>
            <Link :href="route('finance.invoices.index')" class="btn-ghost"><Icon name="receipt" :size="18" /> Semua tagihan</Link>
            <Link :href="route('finance.invoices.create')" class="btn-accent"><Icon name="plus" :size="18" /> Buat tagihan</Link>
        </template>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Diterima bulan ini" :value="rupiah(stats.received_month)" icon="wallet" tone="green" />
        <StatCard label="Belum terbayar" :value="rupiah(stats.outstanding)" icon="money" tone="amber" />
        <StatCard label="Tagihan terlambat" :value="stats.overdue_count" icon="alert-triangle" tone="red" />
        <StatCard label="Menunggu verifikasi" :value="stats.pending_count" icon="badge-check" tone="brand" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="card p-6 lg:col-span-3">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="font-display font-bold text-ink">6 bulan terakhir</h2>
                <div class="flex gap-4 text-xs text-muted">
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand-200" /> Ditagihkan</span>
                    <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-brand-600" /> Diterima</span>
                </div>
            </div>
            <div class="flex h-56 items-end gap-3 sm:gap-6">
                <div v-for="m in months" :key="m.label" class="flex h-full flex-1 flex-col items-center gap-2">
                    <div class="flex w-full flex-1 items-end justify-center gap-1">
                        <div class="w-1/2 max-w-6 rounded-t-md bg-brand-200 transition-all" :style="{ height: `${(m.billed / max) * 100}%` }" :title="`Ditagihkan ${rupiah(m.billed)}`" />
                        <div class="w-1/2 max-w-6 rounded-t-md bg-brand-600 transition-all" :style="{ height: `${(m.received / max) * 100}%` }" :title="`Diterima ${rupiah(m.received)}`" />
                    </div>
                    <p class="text-xs font-semibold text-muted">{{ m.label }}</p>
                    <p class="hidden text-[11px] text-muted sm:block">{{ compactRupiah(m.received) }}</p>
                </div>
            </div>
        </div>

        <div class="card overflow-hidden lg:col-span-2">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-display font-bold text-ink">Perlu diverifikasi</h2>
                <p class="text-sm text-muted">Bukti transfer dari orang tua.</p>
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
            <EmptyState v-else icon="badge-check" title="Semua beres" description="Tidak ada bukti transfer yang menunggu." />
        </div>
    </div>
</template>
