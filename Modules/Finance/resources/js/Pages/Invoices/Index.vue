<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useFilters } from '@/composables/useFilters';
import { rupiah } from '@/lib/format';

const props = defineProps({ invoices: Object, filters: Object, statuses: Object, can: Object });

const filters = useFilters({ search: props.filters.search ?? '', status: props.filters.status ?? '' }, 'finance.invoices.index');
const tabs = { '': 'Semua', unpaid: 'Belum bayar', partial: 'Sebagian', overdue: 'Terlambat', pending: 'Perlu verifikasi', paid: 'Lunas', cancelled: 'Dibatalkan' };
</script>

<template>
    <Head title="Tagihan" />

    <PageHeader eyebrow="Keuangan" :title="can.manage ? 'Tagihan' : 'Tagihan anak'" :description="can.manage ? 'Tagihan kursus per siswa.' : 'Lihat tagihan dan kirim bukti transfer.'">
        <template v-if="can.manage" #actions>
            <Link :href="route('finance.invoices.create')" class="btn-accent"><Icon name="plus" :size="18" /> Buat tagihan</Link>
        </template>
    </PageHeader>

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <button
            v-for="(label, key) in tabs"
            :key="key"
            :class="filters.status === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition"
            @click="filters.status = key"
        >
            {{ label }}
        </button>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-line p-4">
            <SearchInput v-model="filters.search" placeholder="Cari nomor, judul, atau nama siswa…" />
        </div>

        <div v-if="invoices.data.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-chip/60 text-xs font-bold uppercase tracking-wide text-muted">
                    <tr>
                        <th class="px-5 py-3">Tagihan</th>
                        <th class="px-5 py-3">Siswa</th>
                        <th class="px-5 py-3 text-right">Nominal</th>
                        <th class="px-5 py-3 text-right">Sisa</th>
                        <th class="px-5 py-3">Jatuh tempo</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="inv in invoices.data" :key="inv.id" class="cursor-pointer hover:bg-chip/40" @click="$inertia.visit(route('finance.invoices.show', inv.id))">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-ink">{{ inv.title }}</p>
                            <p class="font-mono text-xs text-muted">{{ inv.number }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <Avatar :initials="inv.student.initials" :seed="inv.student.id" size="sm" />
                                <span class="text-ink">{{ inv.student.name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right font-medium text-ink">{{ rupiah(inv.amount) }}</td>
                        <td class="px-5 py-3 text-right font-display font-bold" :class="inv.remaining ? 'text-ink' : 'text-emerald-700'">{{ rupiah(inv.remaining) }}</td>
                        <td class="px-5 py-3" :class="inv.is_overdue ? 'font-semibold text-rose-600' : 'text-muted'">{{ inv.due_date }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                <StatusBadge :status="inv.is_overdue ? 'overdue' : inv.status" />
                                <StatusBadge v-if="inv.pending_payments" status="pending" :label="`${inv.pending_payments} bukti baru`" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <EmptyState v-else icon="receipt" title="Tidak ada tagihan" :description="filters.status || filters.search ? 'Coba ubah filter.' : 'Tagihan yang dibuat akan muncul di sini.'" />

        <Pagination :meta="invoices" />
    </div>
</template>
