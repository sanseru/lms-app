<script setup>
import { steps } from '../site.js';
</script>

<template>
    <section id="how-it-works" class="mx-auto max-w-6xl px-4 py-20 sm:px-6" aria-labelledby="h-how">
        <h2 id="h-how" class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Four steps from first chat to first project</h2>
        <ol class="mt-10 grid gap-6 md:grid-cols-4">
            <li v-for="(s, i) in steps" :key="s.title">
                <span class="font-mono text-sm font-bold text-brand-600">step_{{ i + 1 }}()</span>
                <h3 class="mt-2 text-xl font-bold">{{ s.title }}</h3>
                <p class="mt-2 leading-relaxed text-muted">{{ s.body }}</p>
            </li>
        </ol>
    </section>
</template>
