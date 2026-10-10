<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { rupiah } from '@/lib/format';

const props = defineProps({ students: Array, classes: Array });

const now = new Date();
const month = now.toISOString().slice(0, 7);
const monthName = now.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
const due = new Date(now.getFullYear(), now.getMonth(), 10).toLocaleDateString('sv-SE');

const form = useForm({
    target: 'student',
    student_id: '',
    class_room_id: '',
    title: `SPP ${monthName}`,
    period: month,
    amount: 350000,
    due_date: due,
    notes: '',
});

const selectedClass = computed(() => props.classes.find((c) => c.id === Number(form.class_room_id)));
const count = computed(() => (form.target === 'class' ? selectedClass.value?.students_count ?? 0 : form.student_id ? 1 : 0));
</script>

<template>
    <Head title="Buat tagihan" />

    <PageHeader eyebrow="Keuangan" title="Buat tagihan">
        <template #back>
            <Link :href="route('finance.invoices.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Daftar tagihan
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="form.post(route('finance.invoices.store'))">
        <FormSection title="Ditagihkan ke" description="Satu siswa, atau semua siswa aktif dalam satu kelas sekaligus.">
            <div class="grid grid-cols-2 gap-3 sm:col-span-2">
                <label
                    v-for="opt in [{ v: 'student', t: 'Satu siswa', d: 'Tagihan individual', i: 'user' }, { v: 'class', t: 'Satu kelas', d: 'Massal untuk semua siswa', i: 'users' }]"
                    :key="opt.v"
                    :class="form.target === opt.v ? 'border-brand-400 bg-brand-50 ring-2 ring-brand-100' : 'border-line hover:border-brand-200'"
                    class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition"
                >
                    <input v-model="form.target" type="radio" :value="opt.v" class="sr-only" />
                    <span class="grid size-9 place-items-center rounded-lg bg-white text-brand-600 ring-1 ring-line"><Icon :name="opt.i" :size="18" /></span>
                    <span><span class="block font-semibold text-ink">{{ opt.t }}</span><span class="text-xs text-muted">{{ opt.d }}</span></span>
                </label>
            </div>
            <Field v-if="form.target === 'student'" id="student_id" label="Siswa" :error="form.errors.student_id" required class="sm:col-span-2">
                <select id="student_id" v-model="form.student_id" class="field">
                    <option value="">Pilih siswa…</option>
                    <option v-for="s in students" :key="s.id" :value="s.id">{{ s.name }} ({{ s.code }})</option>
                </select>
            </Field>
            <Field v-else id="class_room_id" label="Kelas" :error="form.errors.class_room_id" required class="sm:col-span-2">
                <select id="class_room_id" v-model="form.class_room_id" class="field">
                    <option value="">Pilih kelas…</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }} · {{ c.students_count }} siswa</option>
                </select>
            </Field>
        </FormSection>

        <FormSection title="Rincian">
            <Field id="title" label="Judul tagihan" :error="form.errors.title" required class="sm:col-span-2">
                <input id="title" v-model="form.title" type="text" class="field" />
            </Field>
            <Field id="amount" label="Nominal (Rp)" :error="form.errors.amount" required :hint="rupiah(form.amount)">
                <input id="amount" v-model.number="form.amount" type="number" min="1000" step="1000" class="field" />
            </Field>
            <Field id="period" label="Periode" :error="form.errors.period">
                <input id="period" v-model="form.period" type="month" class="field" />
            </Field>
            <Field id="due_date" label="Jatuh tempo" :error="form.errors.due_date" required>
                <input id="due_date" v-model="form.due_date" type="date" class="field" />
            </Field>
            <Field id="notes" label="Catatan untuk orang tua" :error="form.errors.notes" class="sm:col-span-2">
                <textarea id="notes" v-model="form.notes" rows="2" class="field" />
            </Field>
        </FormSection>

        <div class="flex flex-col-reverse items-stretch gap-3 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-muted">
                Akan membuat <b class="text-ink">{{ count }}</b> tagihan, total <b class="text-ink">{{ rupiah(count * (form.amount || 0)) }}</b>
            </p>
            <div class="flex justify-end gap-2">
                <Link :href="route('finance.invoices.index')" class="btn-ghost">Batal</Link>
                <button type="submit" class="btn-primary" :disabled="form.processing || !count"><Icon name="check" :size="18" /> Buat tagihan</button>
            </div>
        </div>
    </form>
</template>
