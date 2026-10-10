<script setup>
import { steps } from '../site.js';
</script>

<template>
    <section id="cara" class="mx-auto max-w-6xl px-4 py-20 sm:px-6" aria-labelledby="h-cara">
        <p class="eyebrow">Cara mendaftar &amp; belajar</p>
        <h2 id="h-cara" class="mt-2 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Empat langkah dari chat pertama sampai karya pertama</h2>
        <ol class="mt-10 grid gap-6 md:grid-cols-4">
            <li v-for="(s, i) in steps" :key="s.title">
                <span class="font-mono text-sm font-bold text-brand-600">langkah_{{ i + 1 }}()</span>
                <h3 class="mt-2 text-xl font-bold">{{ s.title }}</h3>
                <p class="mt-2 leading-relaxed text-muted">{{ s.body }}</p>
            </li>
        </ol>
    </section>
</template>
