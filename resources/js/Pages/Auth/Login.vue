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
        <Head title="Sign in" />

        <h1 class="text-2xl font-extrabold tracking-tight text-ink">Sign in to your learning space.</h1>
        <p class="mt-1 text-sm text-muted">Use the username or email your admin gave you.</p>

        <div v-if="status" class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ status }}</div>

        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <Field id="login" label="Username or email" :error="form.errors.login">
                <input id="login" v-model="form.login" type="text" class="field" autocomplete="username" autofocus required />
            </Field>

            <Field id="password" label="Password" :error="form.errors.password">
                <input id="password" v-model="form.password" type="password" class="field" autocomplete="current-password" required />
            </Field>

            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-muted">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    Remember me
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-brand-600 hover:text-brand-700">
                    Forgot password?
                </Link>
            </div>

            <button type="submit" class="btn-accent w-full py-3" :disabled="form.processing">
                Sign in <Icon name="arrow-right" :size="18" />
            </button>
        </form>
    </GuestLayout>
</template>
