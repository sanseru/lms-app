<script setup>
import { Link } from '@inertiajs/vue3';
import { trialText, waLink } from '../site.js';
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 pb-20 sm:px-6">
        <div class="relative grid items-center gap-8 overflow-hidden rounded-3xl bg-accent-500 p-8 text-ink sm:p-12 md:grid-cols-[1fr_auto]">
            <div class="pointer-events-none absolute -right-10 -top-10 select-none font-mono text-[12rem] font-bold leading-none text-accent-700/25" aria-hidden="true">{ }</div>
            <div class="relative min-w-0">
                <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Coba satu kelas dulu, gratis.</h2>
                <p class="mt-3 max-w-xl text-lg">Lihat sendiri bagaimana anak Anda belajar. Tidak ada kewajiban mendaftar setelahnya.</p>
            </div>
            <div class="relative flex flex-col gap-2">
                <Link :href="route('leads.apply')" class="btn rounded-2xl bg-brand-900 px-7 py-4 text-lg text-white hover:bg-brand-800">Daftar coba gratis →</Link>
                <a :href="waLink(trialText)" target="_blank" rel="noopener" class="text-center text-sm font-semibold underline underline-offset-4">atau tanya dulu via WhatsApp</a>
            </div>
        </div>
    </section>
</template>
