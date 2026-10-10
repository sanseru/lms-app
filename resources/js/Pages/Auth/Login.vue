<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Masuk" />

        <p class="eyebrow">Selamat datang kembali</p>
        <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-ink">Masuk ke ruang belajarmu.</h1>
        <p class="mt-1 text-sm text-muted">Gunakan username atau email yang diberikan admin.</p>

        <div v-if="status" class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ status }}</div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <Field id="login" label="Username atau email" :error="form.errors.login">
                <input id="login" v-model="form.login" type="text" class="field" autocomplete="username" autofocus required />
            </Field>

            <Field id="password" label="Kata sandi" :error="form.errors.password">
                <input id="password" v-model="form.password" type="password" class="field" autocomplete="current-password" required />
            </Field>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    Ingat saya
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-brand-600 hover:text-brand-700">
                    Lupa kata sandi?
                </Link>
            </div>

            <button type="submit" class="btn-accent w-full py-3" :disabled="form.processing">
                Masuk <Icon name="arrow-right" :size="18" />
            </button>
        </form>
    </GuestLayout>
</template>
