<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import AccountFields from '../../Components/AccountFields.vue';
import CheckList from '@/Components/ui/CheckList.vue';

const props = defineProps({
    student: Object,
    guardians: Array,
});

const s = props.student ?? {};
const form = useForm({
    name: s.name ?? '',
    username: s.username ?? '',
    email: s.email ?? '',
    phone: s.phone ?? '',
    status: s.status ?? 'active',
    password: '',
    birth_date: s.birth_date ?? '',
    gender: s.gender ?? '',
    school: s.school ?? '',
    grade: s.grade ?? '',
    address: s.address ?? '',
    notes: s.notes ?? '',
    joined_at: s.joined_at ?? new Date().toISOString().slice(0, 10),
    guardian_ids: s.guardian_ids ?? [],
});

const submit = () =>
    props.student ? form.put(route('people.students.update', props.student.id)) : form.post(route('people.students.store'));
</script>

<template>
    <Head :title="student ? 'Ubah siswa' : 'Tambah siswa'" />

    <PageHeader :eyebrow="student ? student.code : 'Siswa baru'" :title="student ? student.name : 'Tambah siswa'">
        <template #back>
            <Link :href="route('people.students.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Daftar siswa
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Akun login" description="Anak bisa login dengan username tanpa email.">
            <AccountFields :form="form" :editing="!!student" />
        </FormSection>

        <FormSection title="Data siswa" description="Informasi untuk admin dan guru.">
            <Field id="birth_date" label="Tanggal lahir" :error="form.errors.birth_date">
                <input id="birth_date" v-model="form.birth_date" type="date" class="field" />
            </Field>
            <Field id="gender" label="Jenis kelamin" :error="form.errors.gender">
                <select id="gender" v-model="form.gender" class="field">
                    <option value="">–</option>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                </select>
            </Field>
            <Field id="school" label="Asal sekolah" :error="form.errors.school">
                <input id="school" v-model="form.school" type="text" class="field" />
            </Field>
            <Field id="grade" label="Kelas di sekolah" :error="form.errors.grade">
                <input id="grade" v-model="form.grade" type="text" class="field" placeholder="mis. 5 SD" />
            </Field>
            <Field id="joined_at" label="Tanggal bergabung" :error="form.errors.joined_at">
                <input id="joined_at" v-model="form.joined_at" type="date" class="field" />
            </Field>
            <Field id="address" label="Alamat" :error="form.errors.address" class="sm:col-span-2">
                <textarea id="address" v-model="form.address" rows="2" class="field" />
            </Field>
            <Field id="notes" label="Catatan" :error="form.errors.notes" class="sm:col-span-2" hint="Minat, kebutuhan khusus, atau catatan lain.">
                <textarea id="notes" v-model="form.notes" rows="3" class="field" />
            </Field>
        </FormSection>

        <FormSection title="Orang tua / wali" description="Orang tua bisa melihat jadwal dan tagihan anaknya.">
            <div class="sm:col-span-2">
                <CheckList v-model="form.guardian_ids" :options="guardians" :subtitle="(g) => g.phone" empty="Belum ada akun orang tua." />
                <p class="mt-2 text-sm text-muted">
                    Orang tua belum terdaftar?
                    <Link :href="route('people.guardians.create')" class="font-semibold text-brand-600 hover:underline">Tambah orang tua</Link>
                </p>
            </div>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="route('people.students.index')" class="btn-ghost">Batal</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan</button>
        </div>
    </form>
</template>
