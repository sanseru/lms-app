<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import AccountFields from '../../Components/AccountFields.vue';

const props = defineProps({ teacher: Object });

const t = props.teacher ?? {};
const form = useForm({
    name: t.name ?? '',
    username: t.username ?? '',
    email: t.email ?? '',
    phone: t.phone ?? '',
    status: t.status ?? 'active',
    password: '',
    expertise: t.expertise ?? '',
    bio: t.bio ?? '',
    joined_at: t.joined_at ?? new Date().toISOString().slice(0, 10),
});

const submit = () =>
    props.teacher ? form.put(route('people.teachers.update', props.teacher.id)) : form.post(route('people.teachers.store'));
</script>

<template>
    <Head :title="teacher ? 'Ubah guru' : 'Tambah guru'" />

    <PageHeader :eyebrow="teacher ? teacher.code : 'Guru baru'" :title="teacher ? teacher.name : 'Tambah guru'">
        <template #back>
            <Link :href="route('people.teachers.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Daftar guru
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Akun login">
            <AccountFields :form="form" :editing="!!teacher" />
        </FormSection>

        <FormSection title="Profil pengajar" description="Ditampilkan ke siswa dan orang tua di halaman kelas.">
            <Field id="expertise" label="Keahlian" :error="form.errors.expertise" class="sm:col-span-2" hint="Pisahkan dengan koma, mis. Python, Scratch, Robotika">
                <input id="expertise" v-model="form.expertise" type="text" class="field" />
            </Field>
            <Field id="joined_at" label="Tanggal bergabung" :error="form.errors.joined_at">
                <input id="joined_at" v-model="form.joined_at" type="date" class="field" />
            </Field>
            <Field id="bio" label="Bio singkat" :error="form.errors.bio" class="sm:col-span-2">
                <textarea id="bio" v-model="form.bio" rows="3" class="field" />
            </Field>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="route('people.teachers.index')" class="btn-ghost">Batal</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan</button>
        </div>
    </form>
</template>
