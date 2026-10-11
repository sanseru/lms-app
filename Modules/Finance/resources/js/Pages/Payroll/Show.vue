<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { rupiah } from '@/lib/format';

const props = defineProps({ payout: Object, can: Object });
const confirm = useConfirm();

const paying = ref(false);
const payForm = useForm({ paid_at: new Date().toLocaleDateString('sv-SE'), proof: null });
const pay = () => payForm.post(route('finance.payroll.paid', props.payout.id), { forceFormData: true, onSuccess: () => (paying.value = false) });
</script>

<template>
    <Head :title="payout.number" />

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <Link :href="can.manage ? route('finance.payroll.index') : route('finance.my-fees')" class="inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
            <Icon name="arrow-left" :size="16" /> Kembali
        </Link>
        <div class="flex gap-2">
            <button class="btn-ghost" onclick="window.print()"><Icon name="printer" :size="16" /> Cetak</button>
            <template v-if="can.manage && payout.status === 'unpaid'">
                <button class="btn-ghost text-rose-600" @click="confirm.ask({ title: 'Hapus slip?', message: 'Sesi di slip ini kembali ke daftar belum direkap.', url: route('finance.payroll.destroy', payout.id) })">Hapus</button>
                <button class="btn-accent" @click="paying = true"><Icon name="check" :size="16" /> Tandai dibayar</button>
            </template>
        </div>
    </div>

    <div class="card mx-auto max-w-3xl p-6 sm:p-10 print:border-0 print:p-0">
        <div class="flex flex-col gap-6 border-b border-line pb-6 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <img src="/images/logo-header.png" alt="Alprogmatics" class="h-10" />
                <p class="eyebrow mt-4">Slip honor tutor</p>
                <p class="font-mono text-sm text-muted">{{ payout.number }}</p>
            </div>
            <div class="sm:text-right">
                <StatusBadge :status="payout.status === 'paid' ? 'paid' : 'unpaid'" :label="payout.status_label" />
                <p class="mt-2 text-sm text-muted">Periode</p>
                <p class="font-semibold text-ink">{{ payout.period }}</p>
                <p v-if="payout.paid_at" class="mt-1 text-sm text-muted">Dibayar {{ payout.paid_at }}</p>
            </div>
        </div>

        <div class="grid gap-4 border-b border-line py-6 sm:grid-cols-2">
            <div>
                <p class="text-sm text-muted">Tutor</p>
                <p class="font-display text-lg font-bold text-ink">{{ payout.teacher.name }}</p>
                <p class="font-mono text-xs text-muted">{{ payout.teacher.code }}</p>
            </div>
            <div v-if="payout.teacher.bank_account" class="sm:text-right">
                <p class="text-sm text-muted">Rekening</p>
                <p class="font-semibold text-ink">{{ payout.teacher.bank_name }}</p>
                <p class="font-mono text-ink">{{ payout.teacher.bank_account }}</p>
            </div>
        </div>

        <table class="mt-6 w-full text-sm">
            <thead class="text-left text-xs font-bold tracking-wide text-muted uppercase">
                <tr><th class="pb-2">Tanggal</th><th class="pb-2">Sesi</th><th class="pb-2 text-right">Honor</th></tr>
            </thead>
            <tbody class="divide-y divide-line">
                <tr v-for="item in payout.items" :key="item.id">
                    <td class="py-2 whitespace-nowrap text-muted">{{ item.date }}</td>
                    <td class="py-2 text-ink">{{ item.description }}</td>
                    <td class="py-2 text-right text-ink">{{ rupiah(item.amount) }}</td>
                </tr>
            </tbody>
        </table>

        <dl class="mt-6 ml-auto max-w-xs space-y-1.5 border-t border-line pt-4 text-sm">
            <div class="flex justify-between"><dt class="text-muted">Subtotal ({{ payout.sessions_count }} sesi)</dt><dd class="text-ink">{{ rupiah(payout.subtotal) }}</dd></div>
            <div v-if="payout.bonus" class="flex justify-between"><dt class="text-muted">Bonus</dt><dd class="text-emerald-700">+ {{ rupiah(payout.bonus) }}</dd></div>
            <div v-if="payout.deduction" class="flex justify-between"><dt class="text-muted">Potongan</dt><dd class="text-rose-600">− {{ rupiah(payout.deduction) }}</dd></div>
            <div class="flex justify-between border-t border-line pt-2"><dt class="font-bold text-ink">Total</dt><dd class="font-display text-lg font-extrabold text-ink">{{ rupiah(payout.total) }}</dd></div>
        </dl>

        <p v-if="payout.notes" class="mt-6 rounded-xl bg-chip px-4 py-3 text-sm text-ink">{{ payout.notes }}</p>
        <a v-if="payout.has_proof" :href="route('finance.payroll.proof', payout.id)" target="_blank" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:underline print:hidden">
            <Icon name="eye" :size="16" /> Lihat bukti transfer
        </a>
    </div>

    <Modal :show="paying" max-width="md" @close="paying = false">
        <form class="p-6" @submit.prevent="pay">
            <h3 class="mb-5 font-display text-lg font-bold text-ink">Tandai honor sudah dibayar</h3>
            <div class="grid gap-4">
                <Field id="paid_at" label="Tanggal transfer" :error="payForm.errors.paid_at" required>
                    <input id="paid_at" v-model="payForm.paid_at" type="date" class="field" />
                </Field>
                <Field id="proof" label="Bukti transfer" :error="payForm.errors.proof" hint="Opsional. JPG, PNG, atau PDF.">
                    <input
                        id="proof"
                        type="file"
                        accept="image/*,application/pdf"
                        class="block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:font-semibold file:text-brand-700"
                        @input="payForm.proof = $event.target.files[0]"
                    />
                </Field>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="paying = false">Batal</button>
                <button type="submit" class="btn-primary" :disabled="payForm.processing">Simpan</button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
