<script setup>
const points = [
    ['Session notes', 'covering the lesson, assignments and a message from the teacher, sent the same day.'],
    ['Level reports', 'assessing logic, creativity and independence.'],
    ['Attendance history & sessions left', 'in your package, plus a reminder before it runs out.'],
];
const tutorFacts = [
    ['3-step', 'selection: technical test, teaching demo, interview'],
    ['Degree', 'in Computer Science, Engineering or Education'],
    ['Monthly', 'training in teaching children'],
];
</script>

<template>
    <section id="reports" class="bg-brand-900 text-white" aria-labelledby="h-reports">
        <div class="mx-auto grid max-w-6xl items-center gap-12 px-4 py-20 sm:px-6 lg:grid-cols-2">
            <div class="min-w-0">
                <h2 id="h-reports" class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">You always know what your child is learning</h2>
                <p class="mt-4 max-w-xl text-lg leading-relaxed text-white/75">No more guesswork. Every session is recorded, and you can check how many sessions are left anytime in the parent portal.</p>
                <ul class="mt-8 grid gap-4">
                    <li v-for="[b, t] in points" :key="b" class="flex gap-3">
                        <span class="mt-1 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-success/25 text-sm font-bold text-emerald-300">✓</span>
                        <span><b>{{ b }}</b> {{ t }}</span>
                    </li>
                </ul>
            </div>
            <div class="min-w-0 rounded-3xl border border-white/10 bg-white/[.06] p-6 sm:p-8">
                <h3 class="text-2xl font-bold">Teachers who understand coding and understand kids</h3>
                <div class="mt-6 grid gap-4 text-center sm:grid-cols-3">
                    <div v-for="[n, t] in tutorFacts" :key="n" class="rounded-2xl bg-white/[.06] p-4">
                        <p class="font-display text-3xl font-extrabold text-amber-300">{{ n }}</p>
                        <p class="mt-1 text-sm text-white/70">{{ t }}</p>
                    </div>
                </div>
                <p class="mt-6 leading-relaxed text-white/75">The same teacher stays with your child from the start of each level, so your child feels comfortable asking questions and the teacher gets to know how they learn.</p>
            </div>
        </div>
    </section>
</template>
