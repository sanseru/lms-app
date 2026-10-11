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
    <Head :title="student ? 'Edit student' : 'Add student'" />

    <PageHeader :eyebrow="student ? student.code : 'New student'" :title="student ? student.name : 'Add student'">
        <template #back>
            <Link :href="route('people.students.index')" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
                <Icon name="arrow-left" :size="16" /> Student list
            </Link>
        </template>
    </PageHeader>

    <form class="card p-6 sm:p-8" @submit.prevent="submit">
        <FormSection title="Login account" description="Kids can log in with a username, no email needed.">
            <AccountFields :form="form" :editing="!!student" />
        </FormSection>

        <FormSection title="Student details" description="Information for admins and teachers.">
            <Field id="birth_date" label="Date of birth" :error="form.errors.birth_date">
                <input id="birth_date" v-model="form.birth_date" type="date" class="field" />
            </Field>
            <Field id="gender" label="Gender" :error="form.errors.gender">
                <select id="gender" v-model="form.gender" class="field">
                    <option value="">–</option>
                    <option value="L">Male</option>
                    <option value="P">Female</option>
                </select>
            </Field>
            <Field id="school" label="School" :error="form.errors.school">
                <input id="school" v-model="form.school" type="text" class="field" />
            </Field>
            <Field id="grade" label="School grade" :error="form.errors.grade">
                <input id="grade" v-model="form.grade" type="text" class="field" placeholder="e.g. Grade 5" />
            </Field>
            <Field id="joined_at" label="Join date" :error="form.errors.joined_at">
                <input id="joined_at" v-model="form.joined_at" type="date" class="field" />
            </Field>
            <Field id="address" label="Address" :error="form.errors.address" class="sm:col-span-2">
                <textarea id="address" v-model="form.address" rows="2" class="field" />
            </Field>
            <Field id="notes" label="Notes" :error="form.errors.notes" class="sm:col-span-2" hint="Interests, special needs or other notes.">
                <textarea id="notes" v-model="form.notes" rows="3" class="field" />
            </Field>
        </FormSection>

        <FormSection title="Parents / guardians" description="Parents can see their child's schedule and invoices.">
            <div class="sm:col-span-2">
                <CheckList v-model="form.guardian_ids" :options="guardians" :subtitle="(g) => g.phone" empty="No parent accounts yet." />
                <p class="mt-2 text-sm text-muted">
                    Parent not registered yet?
                    <Link :href="route('people.guardians.create')" class="font-semibold text-brand-600 hover:underline">Add parent</Link>
                </p>
            </div>
        </FormSection>

        <div class="flex justify-end gap-2 border-t border-line pt-6">
            <Link :href="route('people.students.index')" class="btn-ghost">Cancel</Link>
            <button type="submit" class="btn-primary" :disabled="form.processing"><Icon name="check" :size="18" /> Save</button>
        </div>
    </form>
</template>
