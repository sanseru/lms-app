<script setup>
// Placeholder: isi landing page dikerjakan thread landing.
import { Head, Link, usePage } from '@inertiajs/vue3';

defineOptions({ layout: null });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Belajar coding untuk anak" />
    <div class="grid min-h-screen place-items-center bg-paper px-4 text-center">
        <div>
            <img src="/images/logo-header.png" alt="Alprogmatics" class="mx-auto h-16" />
            <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-ink">Belajar coding, seru dan terarah.</h1>
            <Link :href="user ? route('dashboard') : route('login')" class="btn-accent mt-8">{{ user ? 'Ke dashboard' : 'Masuk' }}</Link>
        </div>
    </div>
</template>
