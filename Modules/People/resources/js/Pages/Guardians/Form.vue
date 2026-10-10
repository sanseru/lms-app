<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import AccountFields from '../../Components/AccountFields.vue';
import CheckList from '@/Components/ui/CheckList.vue';

const props = defineProps({ guardian: Object, students: Array });

const g = props.guardian ?? {};
const form = useForm({
    name: g.name ?? '',
    username: g.username ?? '',
    email: g.email ?? '',
    phone: g.phone ?? '',
    status: g.status ?? 'active',
    password: '',
    relationship: g.relationship ?? 'ibu',
    student_ids: g.student_ids ?? [],
});

const submit = () =>
    props.guardian ? form.put(route('people.guardians.update', props.guardian.id)) : form.post(route('people.guardians.store'));
</script>

<template>
    <Head :title="guardian ? 'Ubah orang tua' : 'Tambah orang tua'" />

    <PageHeader eyebrow="Orang tua" :title="guardian ? guardian.name : 'Tambah orang tua'">
        <template #back>
            <Link :href="route('people.guardians.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Daftar orang tua
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Akun login" description="Nomor HP wajib diisi untuk konfirmasi pembayaran.">
            <AccountFields :form="form" :editing="!!guardian" />
        </FormSection>

        <FormSection title="Anak" description="Pilih siswa yang menjadi tanggungan orang tua ini.">
            <Field id="relationship" label="Hubungan" :error="form.errors.relationship">
                <select id="relationship" v-model="form.relationship" class="field">
                    <option value="ayah">Ayah</option>
                    <option value="ibu">Ibu</option>
                    <option value="wali">Wali</option>
                </select>
            </Field>
            <div class="sm:col-span-2">
                <CheckList v-model="form.student_ids" :options="students" :subtitle="(s) => s.code" empty="Belum ada data siswa." />
            </div>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="route('people.guardians.index')" class="btn-ghost">Batal</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan</button>
        </div>
    </form>
</template>
