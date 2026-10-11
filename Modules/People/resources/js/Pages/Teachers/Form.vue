<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Field from '@/Components/ui/Field.vue';
import FormSection from '@/Components/ui/FormSection.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { rupiah } from '@/lib/format';
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
    rate_per_session: t.rate_per_session ?? 100000,
    bank_name: t.bank_name ?? '',
    bank_account: t.bank_account ?? '',
    bio: t.bio ?? '',
    joined_at: t.joined_at ?? new Date().toISOString().slice(0, 10),
});

const submit = () =>
    props.teacher ? form.put(route('people.teachers.update', props.teacher.id)) : form.post(route('people.teachers.store'));
</script>

<template>
    <Head :title="teacher ? 'Edit teacher' : 'Add teacher'" />

    <PageHeader :eyebrow="teacher ? teacher.code : 'New teacher'" :title="teacher ? teacher.name : 'Add teacher'">
        <template #back>
            <Link :href="route('people.teachers.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Teacher list
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Login account">
            <AccountFields :form="form" :editing="!!teacher" />
        </FormSection>

        <FormSection title="Teacher profile" description="Shown to students and parents on the class page.">
            <Field id="expertise" label="Expertise" :error="form.errors.expertise" class="sm:col-span-2" hint="Separate with commas, e.g. Python, Scratch, Robotics">
                <input id="expertise" v-model="form.expertise" type="text" class="field" />
            </Field>
            <Field id="joined_at" label="Join date" :error="form.errors.joined_at">
                <input id="joined_at" v-model="form.joined_at" type="date" class="field" />
            </Field>
            <Field id="bio" label="Short bio" :error="form.errors.bio" class="sm:col-span-2">
                <textarea id="bio" v-model="form.bio" rows="3" class="field" />
            </Field>
        </FormSection>

        <FormSection title="Tutor fees" description="Fees are calculated automatically from every session taught.">
            <Field id="rate_per_session" label="Fee per session (Rp)" :error="form.errors.rate_per_session" required :hint="rupiah(form.rate_per_session)">
                <input id="rate_per_session" v-model.number="form.rate_per_session" type="number" min="0" step="5000" class="field" />
            </Field>
            <div class="hidden sm:block" />
            <Field id="bank_name" label="Bank" :error="form.errors.bank_name">
                <input id="bank_name" v-model="form.bank_name" type="text" class="field" placeholder="e.g. BCA" />
            </Field>
            <Field id="bank_account" label="Account number" :error="form.errors.bank_account">
                <input id="bank_account" v-model="form.bank_account" type="text" class="field font-mono" />
            </Field>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="route('people.teachers.index')" class="btn-ghost">Cancel</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Save</button>
        </div>
    </form>
</template>
