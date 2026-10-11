<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { rupiah } from '@/lib/format';

const props = defineProps({ invoice: Object, bank: Object, can: Object });

const progress = computed(() => Math.min(100, Math.round((props.invoice.paid_amount / props.invoice.amount) * 100)));
const confirm = useConfirm();

const paying = ref(false);
const payForm = useForm({
    amount: props.invoice.remaining,
    paid_at: new Date().toLocaleDateString('sv-SE'),
    method: 'transfer',
    bank_name: '',
    account_name: '',
    proof: null,
    notes: '',
});
const submitPayment = () =>
    payForm.post(route('finance.invoices.payments.store', props.invoice.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            paying.value = false;
            payForm.reset();
        },
    });

const rejecting = ref(null);
const rejectForm = useForm({ reason: '' });
const submitReject = () =>
    rejectForm.post(route('finance.payments.reject', rejecting.value), {
        preserveScroll: true,
        onSuccess: () => {
            rejecting.value = null;
            rejectForm.reset();
        },
    });

const verify = (id) => router.post(route('finance.payments.verify', id), {}, { preserveScroll: true });

const copied = ref(false);
const copyAccount = async () => {
    await navigator.clipboard?.writeText(props.bank.account_number);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};
</script>

<template>
    <Head :title="invoice.number" />

    <Link :href="route('finance.invoices.index')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> All invoices
    </Link>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <!-- Summary -->
            <div class="card p-6 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="font-mono text-sm text-muted">{{ invoice.number }}</p>
                        <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-ink">{{ invoice.title }}</h1>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <StatusBadge :status="invoice.is_overdue ? 'overdue' : invoice.status" />
                            <span v-if="invoice.period" class="rounded-full bg-chip px-2.5 py-0.5 text-xs font-semibold text-muted">{{ invoice.period }}</span>
                        </div>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-sm text-muted">Remaining balance</p>
                        <p class="font-display text-3xl font-extrabold tracking-tight" :class="invoice.remaining ? 'text-ink' : 'text-emerald-700'">{{ rupiah(invoice.remaining) }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Paid {{ rupiah(invoice.paid_amount) }}</span>
                        <span class="font-semibold text-ink">of {{ rupiah(invoice.amount) }}</span>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-chip">
                        <div class="h-full rounded-full bg-emerald-500 transition-[width]" :style="{ width: `${progress}%` }" />
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 border-t border-line pt-6 text-sm sm:grid-cols-3">
                    <div><dt class="text-muted">Due date</dt><dd :class="invoice.is_overdue ? 'text-rose-600' : 'text-ink'" class="font-semibold">{{ invoice.due_date }}</dd></div>
                    <div v-if="invoice.class"><dt class="text-muted">Class</dt><dd class="font-semibold text-ink">{{ invoice.class.name }}</dd></div>
                    <div v-if="invoice.notes" class="sm:col-span-3"><dt class="text-muted">Notes</dt><dd class="text-ink">{{ invoice.notes }}</dd></div>
                </dl>

                <div v-if="can.pay || can.receipt || (can.manage && invoice.status === 'unpaid' && !invoice.paid_amount)" class="mt-6 flex flex-wrap gap-2 border-t border-line pt-6">
                    <button v-if="can.pay" class="btn-accent" @click="paying = true">
                        <Icon :name="can.manage ? 'plus' : 'upload'" :size="18" /> {{ can.manage ? 'Record payment' : 'Send transfer proof' }}
                    </button>
                    <Link v-if="can.receipt" :href="route('finance.invoices.receipt', invoice.id)" class="btn-ghost">
                        <Icon name="printer" :size="16" /> Receipt
                    </Link>
                    <button
                        v-if="can.manage && invoice.status === 'unpaid' && !invoice.paid_amount"
                        class="btn-ghost text-rose-600"
                        @click="confirm.ask({ title: 'Cancel this invoice?', message: 'The invoice can no longer be paid.', confirmText: 'Yes, cancel it', method: 'post', url: route('finance.invoices.cancel', invoice.id) })"
                    >
                        <Icon name="ban" :size="16" /> Cancel invoice
                    </button>
                </div>
            </div>

            <!-- Payment history -->
            <div class="card overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="font-display font-bold text-ink">Payment history</h2>
                </div>
                <ul v-if="invoice.payments.length" class="divide-y divide-line">
                    <li v-for="p in invoice.payments" :key="p.id" class="px-6 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-display font-bold text-ink">{{ rupiah(p.amount) }}</p>
                                    <StatusBadge :status="p.status" :label="p.status_label" />
                                </div>
                                <p class="mt-0.5 text-sm text-muted">
                                    {{ p.paid_at }} · {{ [p.method === 'cash' ? 'Cash' : 'Transfer', p.bank_name, p.account_name && `from ${p.account_name}`].filter(Boolean).join(' ') }}
                                </p>
                                <p class="text-xs text-muted">
                                    Sent by {{ p.submitted_by }}<template v-if="p.verified_by"> · processed by {{ p.verified_by }} {{ p.verified_at }}</template>
                                </p>
                                <p v-if="p.notes" class="mt-1 text-sm text-ink/80">“{{ p.notes }}”</p>
                                <p v-if="p.rejection_reason" class="mt-1 text-sm font-medium text-rose-600">Rejection reason: {{ p.rejection_reason }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a v-if="p.has_proof" :href="route('finance.payments.proof', p.id)" target="_blank" class="btn-ghost px-3 py-2"><Icon name="eye" :size="16" /> Proof</a>
                                <template v-if="can.manage && p.status === 'pending'">
                                    <button class="btn-ghost px-3 py-2 text-rose-600" @click="rejecting = p.id">Reject</button>
                                    <button class="btn-primary px-3 py-2" @click="verify(p.id)"><Icon name="check" :size="16" /> Verify</button>
                                </template>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="px-6 py-8 text-center text-sm text-muted">No payments yet.</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="card p-5">
                <p class="eyebrow mb-3">Student</p>
                <div class="flex items-center gap-3">
                    <Avatar :initials="invoice.student.initials" :seed="invoice.student.id" size="lg" />
                    <div>
                        <p class="font-display font-bold text-ink">{{ invoice.student.name }}</p>
                        <p class="font-mono text-xs text-muted">{{ invoice.student.code }}</p>
                    </div>
                </div>
                <div v-if="invoice.guardians.length" class="mt-4 space-y-1 border-t border-line pt-4 text-sm">
                    <p v-for="g in invoice.guardians" :key="g.name" class="flex justify-between gap-2"><span class="text-ink">{{ g.name }}</span><span class="text-muted">{{ g.phone }}</span></p>
                </div>
            </div>

            <div v-if="!can.manage" class="panel-brand !rounded-2xl p-5">
                <p class="font-display font-bold">Transfer to</p>
                <p class="mt-2 text-sm text-brand-100">{{ bank.name }}</p>
                <p class="font-mono text-2xl font-medium tracking-wider">{{ bank.account_number }}</p>
                <p class="text-sm">Account name: {{ bank.account_name }}</p>
                <button class="mt-4 min-h-11 w-full rounded-xl bg-white py-2 text-sm font-bold text-brand-700 hover:bg-brand-50" @click="copyAccount">
                    {{ copied ? 'Account number copied ✓' : 'Copy account number' }}
                </button>
                <p class="mt-3 text-xs text-brand-100">After you transfer, send the proof so an admin can verify it.</p>
            </div>
        </div>
    </div>

    <!-- Payment modal -->
    <Modal :show="paying" max-width="lg" @close="paying = false">
        <form class="p-6" @submit.prevent="submitPayment">
            <h3 class="font-display text-lg font-bold text-ink">{{ can.manage ? 'Record payment' : 'Send transfer proof' }}</h3>
            <p class="mb-5 text-sm text-muted">Remaining balance: {{ rupiah(invoice.remaining) }}.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field id="pay_amount" label="Amount" :error="payForm.errors.amount" required>
                    <input id="pay_amount" v-model.number="payForm.amount" type="number" step="1000" class="field" />
                </Field>
                <Field id="pay_date" label="Transfer date" :error="payForm.errors.paid_at" required>
                    <input id="pay_date" v-model="payForm.paid_at" type="date" class="field" />
                </Field>
                <Field v-if="can.manage" id="pay_method" label="Method" :error="payForm.errors.method" class="sm:col-span-2">
                    <select id="pay_method" v-model="payForm.method" class="field">
                        <option value="transfer">Bank transfer</option>
                        <option value="cash">Cash</option>
                    </select>
                </Field>
                <Field id="pay_bank" label="Sending bank" :error="payForm.errors.bank_name">
                    <input id="pay_bank" v-model="payForm.bank_name" type="text" class="field" placeholder="e.g. BCA" />
                </Field>
                <Field id="pay_holder" label="Account holder name" :error="payForm.errors.account_name">
                    <input id="pay_holder" v-model="payForm.account_name" type="text" class="field" />
                </Field>
                <Field id="pay_proof" label="Transfer proof" :error="payForm.errors.proof" :required="!can.manage" hint="JPG, PNG, or PDF, max 4 MB." class="sm:col-span-2">
                    <input
                        id="pay_proof"
                        type="file"
                        accept="image/*,application/pdf"
                        class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
                        @input="payForm.proof = $event.target.files[0]"
                    />
                </Field>
                <Field id="pay_notes" label="Notes" :error="payForm.errors.notes" class="sm:col-span-2">
                    <textarea id="pay_notes" v-model="payForm.notes" rows="2" class="field" />
                </Field>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="paying = false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="payForm.processing">{{ can.manage ? 'Save' : 'Send' }}</button>
            </div>
        </form>
    </Modal>

    <!-- Reject modal -->
    <Modal :show="!!rejecting" max-width="md" @close="rejecting = null">
        <form class="p-6" @submit.prevent="submitReject">
            <h3 class="font-display text-lg font-bold text-ink">Reject payment</h3>
            <p class="mb-4 text-sm text-muted">Parents will see the reason.</p>
            <Field id="reason" label="Reason" :error="rejectForm.errors.reason" required>
                <input id="reason" v-model="rejectForm.reason" type="text" class="field" placeholder="e.g. Amount does not match the bank statement" />
            </Field>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="rejecting = null">Cancel</button>
                <button type="submit" class="btn-danger" :disabled="rejectForm.processing">Reject</button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
