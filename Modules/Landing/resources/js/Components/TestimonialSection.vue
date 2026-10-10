<script setup>
import { testimonials } from '../site.js';

const avatarTones = ['bg-accent-50 text-accent-600', 'bg-brand-50 text-brand-600', 'bg-success-soft text-success'];
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6" aria-labelledby="h-testi">
        <div class="flex flex-wrap items-center gap-3">
            <h2 id="h-testi" class="text-3xl font-extrabold tracking-tight sm:text-4xl">Kata orang tua</h2>
            <span class="rounded-full border border-dashed border-accent-500 px-3 py-1 text-xs font-bold text-accent-600">Contoh teks, ganti dengan testimoni asli</span>
        </div>
        <div class="mt-10 grid gap-5 md:grid-cols-3">
            <figure v-for="(t, i) in testimonials" :key="i" class="card flex flex-col p-6">
                <blockquote class="flex-1 leading-relaxed">“{{ t.quote }}”</blockquote>
                <figcaption class="mt-5 flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-full font-bold" :class="avatarTones[i % 3]">{{ t.initial }}</span>
                    <span><b class="block">{{ t.name }}</b><span class="text-sm text-muted">{{ t.role }}</span></span>
                </figcaption>
            </figure>
        </div>
    </section>
</template>
