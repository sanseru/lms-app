<script setup>
import LandingIcon from './LandingIcon.vue';
import { features, site } from '../site.js';

const tones = {
    brand: 'bg-brand-50 text-brand-600',
    accent: 'bg-accent-50 text-accent-600',
    success: 'bg-success-soft text-success',
};
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 pb-20 pt-10 sm:px-6" aria-labelledby="h-unggul">
        <h2 id="h-unggul" class="max-w-2xl text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Kenapa orang tua memilih {{ site.brand }}</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article v-for="f in features" :key="f.title" class="card landing-lift p-6">
                <div class="grid h-12 w-12 place-items-center rounded-xl" :class="tones[f.tone]"><LandingIcon :name="f.icon" /></div>
                <h3 class="mt-4 text-xl font-bold">{{ f.title }}</h3>
                <p class="mt-2 leading-relaxed text-muted">{{ f.body }}</p>
            </article>
        </div>
    </section>
</template>
