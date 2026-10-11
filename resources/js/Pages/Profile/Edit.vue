<script setup>
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/Components/ui/PageHeader.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});
</script>

<template>
    <Head title="Profile" />
    <PageHeader eyebrow="Account" title="My profile" description="Update your name, email and password." />

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card p-6 sm:p-8">
            <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
        </div>
        <div class="card p-6 sm:p-8">
            <UpdatePasswordForm />
        </div>
    </div>
</template>
