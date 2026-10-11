<script setup>
import { computed, ref } from 'vue';
import { ageGroups, programs } from '../site.js';

const age = ref('all');
const shown = computed(() => (age.value === 'all' ? programs : programs.filter((p) => p.ages.includes(age.value))));
</script>

<template>
    <section id="program" class="border-y border-line bg-white" aria-labelledby="h-program">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="h-program" class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">Mulai sesuai usia, naik sesuai kemampuan</h2>
                </div>
                <div class="flex flex-wrap gap-2" role="group" aria-label="Filter usia">
                    <button
                        v-for="g in ageGroups"
                        :key="g.key"
                        type="button"
                        class="min-h-11 rounded-full border px-4 py-2 text-sm font-bold transition"
                        :class="age === g.key ? 'border-brand-600 bg-brand-600 text-white' : 'border-line bg-paper hover:border-brand-300'"
                        :aria-pressed="age === g.key"
                        @click="age = g.key"
                    >
                        {{ g.label }}
                    </button>
                </div>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article v-for="p in shown" :key="p.title" class="landing-lift flex flex-col rounded-2xl border border-line bg-paper p-6">
                    <div class="flex items-center justify-between">
                        <span class="grid h-12 w-12 place-items-center rounded-xl font-mono text-lg font-bold" :class="p.bg">{{ p.mark }}</span>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-600">{{ p.range }}</span>
                    </div>
                    <h3 class="mt-4 text-xl font-bold">{{ p.title }}</h3>
                    <p class="mt-2 flex-1 leading-relaxed text-muted">{{ p.body }}</p>
                    <p class="mt-4 text-sm font-semibold">{{ p.meta }}</p>
                </article>
            </div>
            <p class="mt-6 text-sm text-muted">Belum yakin level yang pas? Tutor akan menilai kemampuan anak di kelas coba gratis.</p>
        </div>
    </section>
</template>
