<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { rupiah } from '@/lib/format';

const props = defineProps({ month: String, monthLabel: String, unpaid: Array, payouts: Array, totals: Object });

const changeMonth = (value) => router.get(route('finance.payroll.index'), { month: value }, { preserveState: true });

const creating = ref(null);
const form = useForm({ teacher_id: null, month: props.month, bonus: 0, deduction: 0, notes: '' });
const openCreate = (row) => {
    form.reset();
    Object.assign(form, { teacher_id: row.teacher.id, month: props.month });
    creating.value = row;
};
const create = () => form.post(route('finance.payroll.store'), { onSuccess: () => (creating.value = null) });
</script>

<template>
    <Head title="Tutor fees" />

    <PageHeader eyebrow="Finance" title="Tutor fees" description="Fees are recorded automatically from every completed session. Bundle each month into a pay slip, then mark it once transferred.">
        <template #actions>
            <input :value="month" type="month" class="field w-44" @change="changeMonth($event.target.value)" />
        </template>
    </PageHeader>

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Not yet bundled" :value="rupiah(totals.unpaid)" icon="clipboard-check" tone="amber" />
        <StatCard label="Unpaid pay slips" :value="rupiah(totals.payouts_unpaid)" icon="receipt" tone="red" />
        <StatCard label="Paid" :value="rupiah(totals.payouts_paid)" icon="hand-coins" tone="green" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-display font-bold text-ink">Not yet bundled · {{ monthLabel }}</h2>
                <p class="text-sm text-muted">Completed sessions not on a pay slip yet.</p>
            </div>
            <ul v-if="unpaid.length" class="divide-y divide-line">
                <li v-for="row in unpaid" :key="row.teacher.id" class="flex items-center gap-3 px-5 py-3">
                    <Avatar :initials="row.teacher.initials" :seed="row.teacher.id" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-ink">{{ row.teacher.name }}</p>
                        <p class="text-sm text-muted">{{ row.sessions_count }} sessions</p>
                    </div>
                    <p class="font-display font-bold text-ink">{{ rupiah(row.total) }}</p>
                    <button class="btn-primary px-3 py-2" @click="openCreate(row)">Create pay slip</button>
                </li>
            </ul>
            <EmptyState v-else icon="badge-check" title="All bundled" description="No pending fees this month." />
        </div>

        <div class="card overflow-hidden">
            <div class="border-b border-line px-5 py-4">
                <h2 class="font-display font-bold text-ink">Pay slips · {{ monthLabel }}</h2>
            </div>
            <ul v-if="payouts.length" class="divide-y divide-line">
                <li v-for="p in payouts" :key="p.id">
                    <Link :href="route('finance.payroll.show', p.id)" class="flex items-center gap-3 px-5 py-3 hover:bg-chip/50">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink">{{ p.teacher_name }}</p>
                            <p class="font-mono text-xs text-muted">{{ p.number }} · {{ p.sessions_count }} sessions</p>
                        </div>
                        <p class="font-display font-bold text-ink">{{ rupiah(p.total) }}</p>
                        <StatusBadge :status="p.status === 'paid' ? 'paid' : 'unpaid'" :label="p.status_label" />
                    </Link>
                </li>
            </ul>
            <EmptyState v-else icon="receipt" title="No pay slips yet" />
        </div>
    </div>

    <Modal :show="!!creating" max-width="md" @close="creating = null">
        <form v-if="creating" class="p-6" @submit.prevent="create">
            <h3 class="font-display text-lg font-bold text-ink">Pay slip for {{ creating.teacher.name }}</h3>
            <p class="mb-5 text-sm text-muted">{{ creating.sessions_count }} sessions · {{ rupiah(creating.total) }} ({{ monthLabel }})</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field id="bonus" label="Bonus (Rp)" :error="form.errors.bonus">
                    <input id="bonus" v-model.number="form.bonus" type="number" min="0" step="1000" class="field" />
                </Field>
                <Field id="deduction" label="Deduction (Rp)" :error="form.errors.deduction">
                    <input id="deduction" v-model.number="form.deduction" type="number" min="0" step="1000" class="field" />
                </Field>
                <Field id="notes" label="Notes" :error="form.errors.notes" class="sm:col-span-2">
                    <input id="notes" v-model="form.notes" type="text" class="field" />
                </Field>
            </div>
            <p class="mt-4 rounded-xl bg-chip px-4 py-3 text-sm">
                Total to pay: <b class="font-display text-ink">{{ rupiah(creating.total + (form.bonus || 0) - (form.deduction || 0)) }}</b>
            </p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="creating = null">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="form.processing">Create pay slip</button>
            </div>
        </form>
    </Modal>
</template>
