<script setup>
import LandingIcon from './LandingIcon.vue';
import { features, site } from '../site.js';

const tones = {
    brand: 'text-brand-600',
    accent: 'text-accent-600',
    success: 'text-success-ink',
};
</script>

<template>
    <section class="mx-auto max-w-6xl px-4 pb-20 pt-10 sm:px-6" aria-labelledby="h-unggul">
        <h2 id="h-unggul" class="max-w-2xl text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Kenapa orang tua memilih {{ site.brand }}</h2>
        <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <article v-for="f in features" :key="f.title" class="card landing-lift p-6">
                <h3 class="flex items-start gap-3 text-xl font-bold">
                    <LandingIcon :name="f.icon" class="mt-0.5 shrink-0" :class="tones[f.tone]" />
                    {{ f.title }}
                </h3>
                <p class="mt-2 leading-relaxed text-muted">{{ f.body }}</p>
            </article>
        </div>
    </section>
</template>
