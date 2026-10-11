<script setup>
import { faqs } from '../site.js';
</script>

<template>
    <section id="faq" class="mx-auto max-w-3xl px-4 py-20 sm:px-6" aria-labelledby="h-faq">
        <h2 id="h-faq" class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Frequently asked questions</h2>
        <div class="mt-8 grid gap-3">
            <details v-for="(f, i) in faqs" :key="f.q" class="card group px-5 py-4" :open="i === 0">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold [&::-webkit-details-marker]:hidden">
                    {{ f.q }}<span class="text-2xl text-brand-600 transition-transform group-open:rotate-45">+</span>
                </summary>
                <p class="mt-3 leading-relaxed text-muted">{{ f.a }}</p>
            </details>
        </div>
    </section>
</template>
