<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';
import { rupiah } from '@/lib/format';

defineProps({ receipt: Object, company: String });
const print = () => window.print();
</script>

<template>
    <Head :title="`Receipt ${receipt.invoice_number}`" />

    <div class="mb-5 flex flex-wrap items-center gap-2">
        <Link :href="route('finance.invoices.show', receipt.invoice_id)" class="mr-auto inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-ink">
            <Icon name="arrow-left" :size="15" /> Back to invoice
        </Link>
        <button class="btn-primary" @click="print"><Icon name="printer" :size="16" /> Print / save as PDF</button>
    </div>

    <div class="print-area mx-auto max-w-4xl">
        <article class="relative overflow-hidden rounded-3xl border border-line bg-white p-8 shadow-sm sm:p-10">
            <div class="absolute inset-x-0 top-0 h-2 bg-brand-600" />

            <header class="flex flex-col gap-4 border-b border-dashed border-line pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <img src="/images/logo-header.png" alt="Alprogmatics" class="h-12 w-auto" />
                </div>
                <div class="sm:text-right">
                    <p class="eyebrow">Receipt</p>
                    <p class="font-mono text-lg font-semibold text-ink">{{ receipt.number }}</p>
                    <p class="text-sm text-muted">{{ receipt.date }}</p>
                </div>
            </header>

            <dl class="mt-6 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-[11rem_1fr]">
                <dt class="text-muted">Received from</dt>
                <dd class="font-semibold text-ink">{{ receipt.payer }}</dd>

                <dt class="text-muted">The sum of</dt>
                <dd class="rounded-xl bg-brand-50 px-4 py-2 font-semibold text-brand-700 italic">{{ receipt.in_words }}</dd>

                <dt class="text-muted">In payment of</dt>
                <dd class="text-ink">
                    <span class="font-semibold">{{ receipt.title }}</span>
                    <span v-if="receipt.period"> · {{ receipt.period }}</span>
                    <span class="block text-muted">
                        Student: {{ receipt.student }}<template v-if="receipt.student_code"> ({{ receipt.student_code }})</template>
                        <template v-if="receipt.class"> · Class {{ receipt.class }}</template>
                        · Invoice {{ receipt.invoice_number }}
                    </span>
                </dd>
            </dl>

            <table class="mt-6 w-full text-sm">
                <thead>
                    <tr class="border-b border-line text-left text-xs tracking-wider text-muted uppercase">
                        <th class="py-2 font-bold">Payment date</th>
                        <th class="py-2 font-bold">Method</th>
                        <th class="py-2 text-right font-bold">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="p in receipt.payments" :key="p.id">
                        <td class="py-2 text-ink">{{ p.paid_at }}</td>
                        <td class="py-2 text-ink">{{ p.method }}</td>
                        <td class="py-2 text-right font-semibold text-ink">{{ rupiah(p.amount) }}</td>
                    </tr>
                </tbody>
                <tfoot class="border-t-2 border-ink/80">
                    <tr>
                        <td colspan="2" class="pt-3 font-bold text-ink">Total received</td>
                        <td class="pt-3 text-right font-display text-xl font-extrabold text-ink">{{ rupiah(receipt.paid_amount) }}</td>
                    </tr>
                    <tr v-if="receipt.remaining">
                        <td colspan="2" class="text-muted">Remaining balance</td>
                        <td class="text-right font-semibold text-rose-600">{{ rupiah(receipt.remaining) }}</td>
                    </tr>
                </tfoot>
            </table>

            <footer class="mt-10 flex items-end justify-between gap-6">
                <div
                    :class="receipt.paid_in_full ? 'border-emerald-500 text-emerald-600' : 'border-amber-500 text-amber-600'"
                    class="-rotate-6 rounded-xl border-4 px-5 py-2 font-display text-2xl font-extrabold tracking-widest uppercase"
                >
                    {{ receipt.paid_in_full ? 'Paid' : 'Partially paid' }}
                </div>
                <div class="text-center text-sm">
                    <p class="text-muted">Received by,</p>
                    <div class="h-14" />
                    <p class="border-t border-ink/60 px-6 pt-1 font-semibold text-ink">{{ receipt.verifier ?? 'Admin' }}</p>
                    <p class="text-xs text-muted">{{ company }}</p>
                </div>
            </footer>
        </article>
    </div>

    <p class="mx-auto mt-4 max-w-4xl text-center text-xs text-muted">This receipt was generated automatically from payments verified by an admin.</p>
</template>
