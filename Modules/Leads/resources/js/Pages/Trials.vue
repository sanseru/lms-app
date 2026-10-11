<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';

defineProps({ upcoming: Array, done: Array, classes: Array });

const current = ref(null);
const form = useForm({ trial_result: '', trial_interest: 4, recommended_class_id: '' });
const openResult = (t) => {
    current.value = t;
    form.defaults({ trial_result: t.trial_result ?? '', trial_interest: t.trial_interest ?? 4, recommended_class_id: t.recommended_class_id ?? '' });
    form.reset();
    form.clearErrors();
};
const save = () => form.post(route('leads.result', current.value.id), { preserveScroll: true, onSuccess: () => (current.value = null) });
</script>

<template>
    <Head title="Kelas Trial" />

    <PageHeader eyebrow="Akademik" title="Kelas trial" description="Calon siswa yang dijadwalkan mencoba kelas bersama Anda. Setelah sesi, isi hasilnya supaya admin bisa menawarkan kelas yang tepat." />

    <div class="space-y-3">
        <div v-for="t in upcoming" :key="t.id" class="card flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
            <div class="grid size-14 shrink-0 place-items-center rounded-2xl bg-amber-50 text-amber-800"><Icon name="calendar-days" :size="24" /></div>
            <div class="min-w-0 flex-1">
                <p class="font-display text-lg font-bold text-ink">{{ t.child_name }}<span v-if="t.child_age" class="font-normal text-muted"> · {{ t.child_age }} tahun</span><span v-if="t.child_grade" class="font-normal text-muted">, kelas {{ t.child_grade }}</span></p>
                <p class="text-sm font-semibold text-ink">{{ t.trial_at }}<span class="font-normal text-muted"> · minat {{ t.interest ?? 'belum tahu' }}</span></p>
                <p v-if="t.message" class="mt-1 text-sm text-muted">"{{ t.message }}"</p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <a v-if="t.zoom_url" :href="t.zoom_url" target="_blank" rel="noopener" class="btn-ghost"><Icon name="video" :size="16" /> Zoom</a>
                <button v-if="t.is_past" class="btn-primary" @click="openResult(t)">Isi hasil</button>
                <p v-else class="self-center text-xs text-muted">Hasil bisa diisi setelah trial dimulai.</p>
            </div>
        </div>
        <EmptyState v-if="!upcoming.length" icon="calendar-days" title="Tidak ada trial terjadwal" description="Admin akan memberi tahu lewat notifikasi saat ada trial untuk Anda." class="card" />
    </div>

    <div v-if="done.length" class="card mt-8 overflow-hidden">
        <h2 class="border-b border-line px-5 py-3 font-display font-bold text-ink">Hasil trial terakhir</h2>
        <ul class="divide-y divide-line">
            <li v-for="t in done" :key="t.id" class="flex flex-wrap items-start gap-3 px-5 py-4">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ t.child_name }} <span class="text-sm font-normal text-muted">· {{ t.trial_at }} · antusiasme {{ t.trial_interest }}/5<span v-if="t.recommended_class"> · {{ t.recommended_class }}</span></span></p>
                    <p class="text-sm text-muted">{{ t.trial_result }}</p>
                </div>
                <button v-if="t.status === 'trial_done'" class="text-sm font-semibold text-brand-600 hover:underline" @click="openResult(t)">Ubah</button>
            </li>
        </ul>
    </div>

    <Modal :show="!!current" max-width="lg" @close="current = null">
        <form v-if="current" class="space-y-4 p-6" @submit.prevent="save">
            <h3 class="font-display text-lg font-bold text-ink">Hasil trial {{ current.child_name }}</h3>
            <Field id="r_result" label="Bagaimana trialnya?" required :error="form.errors.trial_result">
                <textarea id="r_result" v-model="form.trial_result" rows="4" class="field" placeholder="Apa yang dibuat anak, seberapa cepat menangkap, apa yang disukai." />
            </Field>
            <Field id="r_interest" label="Antusiasme anak (1–5)" required :error="form.errors.trial_interest">
                <div class="flex gap-2">
                    <button v-for="n in 5" :key="n" type="button" class="grid size-11 place-items-center rounded-xl border font-bold" :class="form.trial_interest === n ? 'border-brand-600 bg-brand-600 text-white' : 'border-line text-ink hover:border-brand-300'" :aria-pressed="form.trial_interest === n" @click="form.trial_interest = n">{{ n }}</button>
                </div>
            </Field>
            <Field id="r_class" label="Kelas yang disarankan" :error="form.errors.recommended_class_id">
                <select id="r_class" v-model="form.recommended_class_id" class="field"><option value="">Belum ada</option><option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option></select>
            </Field>
            <div class="flex justify-end gap-2"><button type="button" class="btn-ghost" @click="current = null">Batal</button><button class="btn-primary" :disabled="form.processing">Simpan hasil</button></div>
        </form>
    </Modal>
</template>
