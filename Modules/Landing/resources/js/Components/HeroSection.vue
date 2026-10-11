<script setup>
import { Link } from '@inertiajs/vue3';
import LandingIcon from './LandingIcon.vue';
import { trialText, waLink } from '../site.js';
</script>

<template>
    <section class="relative overflow-hidden">
        <div class="landing-dots absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-12 sm:px-6 md:pb-24 md:pt-20 lg:grid-cols-[1.05fr_.95fr]">
            <div class="min-w-0">
                <p class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 text-sm font-semibold text-muted">
                    <span class="h-2 w-2 rounded-full bg-success"></span> Live online classes on Zoom · ages 6–17
                </p>
                <h1 class="mt-5 text-[2.6rem] font-extrabold leading-[1.04] tracking-tight text-balance sm:text-6xl">
                    Your child doesn’t just play games.<br />
                    <span class="text-brand-600">They can make them.</span>
                </h1>
                <p class="mt-5 max-w-[34rem] text-lg leading-relaxed text-muted">
                    Private and small-group coding classes with patient teachers. From Scratch to Python, your child builds logic, creativity and confidence. You get a report after every session.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <Link :href="route('leads.apply')" class="btn-accent rounded-2xl px-6 py-4 text-lg">Try a free class</Link>
                    <a :href="waLink(trialText)" target="_blank" rel="noopener" class="btn-ghost rounded-2xl border-2 px-6 py-4 text-lg">
                        <LandingIcon name="whatsapp" :size="22" /> Ask on WhatsApp
                    </a>
                </div>
                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4">
                    <div><dt class="text-sm text-muted">Session length</dt><dd class="font-display text-2xl font-extrabold">60 min</dd></div>
                    <div><dt class="text-sm text-muted">Group size</dt><dd class="font-display text-2xl font-extrabold">max. 5</dd></div>
                    <div><dt class="text-sm text-muted">Trial class</dt><dd class="font-display text-2xl font-extrabold text-success-ink">Free</dd></div>
                </dl>
            </div>

            <!-- Code editor with a student project + report card for parents -->
            <div class="relative min-w-0">
                <div class="-rotate-[1.5deg] overflow-hidden rounded-3xl bg-brand-900 text-white shadow-2xl shadow-brand-900/30">
                    <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3">
                        <span class="h-3 w-3 rounded-full bg-[#ff5f57]"></span><span class="h-3 w-3 rounded-full bg-[#febc2e]"></span><span class="h-3 w-3 rounded-full bg-[#28c840]"></span>
                        <span class="ml-3 font-mono text-xs text-white/60">guess_the_number.py · by Raka, age 10</span>
                    </div>
                    <pre class="overflow-x-auto px-5 pb-44 pt-5 font-mono text-[13px] leading-7 [font-variant-ligatures:none] sm:pb-32 sm:text-sm"><code><span class="text-sky-300">import</span> random

number = random.randint(<span class="text-amber-300">1</span>, <span class="text-amber-300">10</span>)
guess = <span class="text-amber-300">0</span>

<span class="text-sky-300">while</span> guess != number:
    guess = int(input(<span class="text-emerald-300">"Guess 1-10: "</span>))
    <span class="text-sky-300">if</span> guess &lt; number:
        print(<span class="text-emerald-300">"Too small!"</span>)
    <span class="text-sky-300">elif</span> guess &gt; number:
        print(<span class="text-emerald-300">"Too big!"</span>)

print(<span class="text-emerald-300">"Hooray, you win! 🎉"</span>)<span class="landing-caret"></span></code></pre>
                </div>
                <div class="absolute -bottom-10 -left-2 w-[15.5rem] rotate-2 lg:-right-6 lg:left-auto rounded-2xl border border-line bg-white p-4 text-ink shadow-xl sm:-left-8">
                    <p class="text-xs font-semibold text-muted">Session #8 report · sent to parent’s WhatsApp</p>
                    <p class="mt-1.5 font-display font-bold leading-snug">Raka finished his first game</p>
                    <div class="mt-3 flex items-center justify-between text-sm"><span class="text-muted">Attendance</span><span class="font-bold text-success-ink">Present</span></div>
                    <div class="mt-1.5 flex items-center justify-between text-sm"><span class="text-muted">Understanding loops</span><span class="font-bold">4/5</span></div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-50"><div class="h-full w-4/5 rounded-full bg-brand-600"></div></div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.landing-dots {
    background-image: radial-gradient(var(--color-line) 1.2px, transparent 1.2px);
    background-size: 22px 22px;
}
.landing-caret::after {
    content: '▍';
    color: var(--color-amber-300);
    animation: landing-blink 1s steps(1) infinite;
}
@keyframes landing-blink {
    50% { opacity: 0; }
}
@media (prefers-reduced-motion: reduce) {
    .landing-caret::after { animation: none; }
}
</style>
