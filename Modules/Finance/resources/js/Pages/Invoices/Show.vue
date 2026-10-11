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
        <Icon name="arrow-left" :size="16" /> Daftar tagihan
    </Link>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <!-- Ringkasan -->
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
                        <p class="text-sm text-muted">Sisa tagihan</p>
                        <p class="font-display text-3xl font-extrabold tracking-tight" :class="invoice.remaining ? 'text-ink' : 'text-emerald-600'">{{ rupiah(invoice.remaining) }}</p>
                    </div>
                </div>

                <div class="mt-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-muted">Terbayar {{ rupiah(invoice.paid_amount) }}</span>
                        <span class="font-semibold text-ink">dari {{ rupiah(invoice.amount) }}</span>
                    </div>
                    <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-chip">
                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-amber-300 transition-all" :style="{ width: `${progress}%` }" />
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 border-t border-line pt-6 text-sm sm:grid-cols-3">
                    <div><dt class="text-muted">Jatuh tempo</dt><dd :class="invoice.is_overdue ? 'text-rose-600' : 'text-ink'" class="font-semibold">{{ invoice.due_date }}</dd></div>
                    <div v-if="invoice.class"><dt class="text-muted">Kelas</dt><dd class="font-semibold text-ink">{{ invoice.class.name }}</dd></div>
                    <div v-if="invoice.notes" class="sm:col-span-3"><dt class="text-muted">Catatan</dt><dd class="text-ink">{{ invoice.notes }}</dd></div>
                </dl>

                <div v-if="can.pay || can.receipt || (can.manage && invoice.status === 'unpaid' && !invoice.paid_amount)" class="mt-6 flex flex-wrap gap-2 border-t border-line pt-6">
                    <button v-if="can.pay" class="btn-accent" @click="paying = true">
                        <Icon :name="can.manage ? 'plus' : 'upload'" :size="18" /> {{ can.manage ? 'Catat pembayaran' : 'Kirim bukti transfer' }}
                    </button>
                    <Link v-if="can.receipt" :href="route('finance.invoices.receipt', invoice.id)" class="btn-ghost">
                        <Icon name="printer" :size="16" /> Kuitansi
                    </Link>
                    <button
                        v-if="can.manage && invoice.status === 'unpaid' && !invoice.paid_amount"
                        class="btn-ghost text-rose-600"
                        @click="confirm.ask({ title: 'Batalkan tagihan?', message: 'Tagihan tidak akan bisa dibayar lagi.', confirmText: 'Ya, batalkan', method: 'post', url: route('finance.invoices.cancel', invoice.id) })"
                    >
                        <Icon name="ban" :size="16" /> Batalkan
                    </button>
                </div>
            </div>

            <!-- Riwayat pembayaran -->
            <div class="card overflow-hidden">
                <div class="border-b border-line px-6 py-4">
                    <h2 class="font-display font-bold text-ink">Riwayat pembayaran</h2>
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
                                    {{ p.paid_at }} · {{ [p.method === 'cash' ? 'Tunai' : 'Transfer', p.bank_name, p.account_name && `a.n. ${p.account_name}`].filter(Boolean).join(' ') }}
                                </p>
                                <p class="text-xs text-muted">
                                    Dikirim oleh {{ p.submitted_by }}<template v-if="p.verified_by"> · diproses {{ p.verified_by }} {{ p.verified_at }}</template>
                                </p>
                                <p v-if="p.notes" class="mt-1 text-sm text-ink/80">“{{ p.notes }}”</p>
                                <p v-if="p.rejection_reason" class="mt-1 text-sm font-medium text-rose-600">Alasan ditolak: {{ p.rejection_reason }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a v-if="p.has_proof" :href="route('finance.payments.proof', p.id)" target="_blank" class="btn-ghost px-3 py-2"><Icon name="eye" :size="16" /> Bukti</a>
                                <template v-if="can.manage && p.status === 'pending'">
                                    <button class="btn-ghost px-3 py-2 text-rose-600" @click="rejecting = p.id">Tolak</button>
                                    <button class="btn-primary px-3 py-2" @click="verify(p.id)"><Icon name="check" :size="16" /> Verifikasi</button>
                                </template>
                            </div>
                        </div>
                    </li>
                </ul>
                <p v-else class="px-6 py-8 text-center text-sm text-muted">Belum ada pembayaran.</p>
            </div>
        </div>

        <!-- Samping -->
        <div class="space-y-6">
            <div class="card p-5">
                <p class="eyebrow mb-3">Siswa</p>
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

            <div v-if="!can.manage" class="rounded-2xl bg-gradient-to-br from-brand-600 to-violet-500 p-5 text-white">
                <p class="text-xs font-extrabold tracking-[0.12em] text-white/70 uppercase">Transfer ke</p>
                <p class="mt-2 text-sm text-white/80">{{ bank.name }}</p>
                <p class="font-mono text-2xl font-medium tracking-wider">{{ bank.account_number }}</p>
                <p class="text-sm">a.n. {{ bank.account_name }}</p>
                <button class="mt-4 w-full rounded-xl bg-white/20 py-2 text-sm font-semibold hover:bg-white/30" @click="copyAccount">
                    {{ copied ? 'Nomor rekening tersalin ✓' : 'Salin nomor rekening' }}
                </button>
                <p class="mt-3 text-xs text-white/70">Setelah transfer, kirim bukti agar admin bisa memverifikasi.</p>
            </div>
        </div>
    </div>

    <!-- Modal pembayaran -->
    <Modal :show="paying" max-width="lg" @close="paying = false">
        <form class="p-6" @submit.prevent="submitPayment">
            <h3 class="font-display text-lg font-bold text-ink">{{ can.manage ? 'Catat pembayaran' : 'Kirim bukti transfer' }}</h3>
            <p class="mb-5 text-sm text-muted">Sisa tagihan {{ rupiah(invoice.remaining) }}.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <Field id="pay_amount" label="Nominal" :error="payForm.errors.amount" required>
                    <input id="pay_amount" v-model.number="payForm.amount" type="number" step="1000" class="field" />
                </Field>
                <Field id="pay_date" label="Tanggal transfer" :error="payForm.errors.paid_at" required>
                    <input id="pay_date" v-model="payForm.paid_at" type="date" class="field" />
                </Field>
                <Field v-if="can.manage" id="pay_method" label="Metode" :error="payForm.errors.method" class="sm:col-span-2">
                    <select id="pay_method" v-model="payForm.method" class="field">
                        <option value="transfer">Transfer bank</option>
                        <option value="cash">Tunai</option>
                    </select>
                </Field>
                <Field id="pay_bank" label="Bank pengirim" :error="payForm.errors.bank_name">
                    <input id="pay_bank" v-model="payForm.bank_name" type="text" class="field" placeholder="mis. BCA" />
                </Field>
                <Field id="pay_holder" label="Nama pemilik rekening" :error="payForm.errors.account_name">
                    <input id="pay_holder" v-model="payForm.account_name" type="text" class="field" />
                </Field>
                <Field id="pay_proof" label="Bukti transfer" :error="payForm.errors.proof" :required="!can.manage" hint="JPG, PNG, atau PDF, maks. 4 MB." class="sm:col-span-2">
                    <input
                        id="pay_proof"
                        type="file"
                        accept="image/*,application/pdf"
                        class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100"
                        @input="payForm.proof = $event.target.files[0]"
                    />
                </Field>
                <Field id="pay_notes" label="Catatan" :error="payForm.errors.notes" class="sm:col-span-2">
                    <textarea id="pay_notes" v-model="payForm.notes" rows="2" class="field" />
                </Field>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="paying = false">Batal</button>
                <button type="submit" class="btn-primary" :disabled="payForm.processing">{{ can.manage ? 'Simpan' : 'Kirim' }}</button>
            </div>
        </form>
    </Modal>

    <!-- Modal tolak -->
    <Modal :show="!!rejecting" max-width="md" @close="rejecting = null">
        <form class="p-6" @submit.prevent="submitReject">
            <h3 class="font-display text-lg font-bold text-ink">Tolak pembayaran</h3>
            <p class="mb-4 text-sm text-muted">Alasan akan terlihat oleh orang tua.</p>
            <Field id="reason" label="Alasan" :error="rejectForm.errors.reason" required>
                <input id="reason" v-model="rejectForm.reason" type="text" class="field" placeholder="mis. Nominal tidak sesuai mutasi" />
            </Field>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="rejecting = null">Batal</button>
                <button type="submit" class="btn-danger" :disabled="rejectForm.processing">Tolak</button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
