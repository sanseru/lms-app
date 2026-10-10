<script setup>
import LandingIcon from './LandingIcon.vue';
import { trialText, waLink } from '../site.js';
</script>

<template>
    <section class="relative overflow-hidden">
        <div class="landing-dots absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pb-20 pt-12 sm:px-6 md:pb-24 md:pt-20 lg:grid-cols-[1.05fr_.95fr]">
            <div class="min-w-0">
                <p class="inline-flex items-center gap-2 rounded-full border border-line bg-white px-3 py-1.5 text-sm font-semibold text-muted">
                    <span class="h-2 w-2 rounded-full bg-success"></span> Kelas online via Zoom · usia 6–17 tahun
                </p>
                <h1 class="mt-5 text-[2.6rem] font-extrabold leading-[1.04] tracking-tight text-balance sm:text-6xl">
                    Anak Anda tidak cuma main game.<br />
                    <span class="text-brand-600">Ia bisa membuatnya.</span>
                </h1>
                <p class="mt-5 max-w-[34rem] text-lg leading-relaxed text-muted">
                    Kelas coding privat dan kelompok kecil bersama tutor yang sabar. Dari Scratch sampai Python, anak belajar logika, kreativitas, dan percaya diri. Orang tua menerima laporan setiap selesai sesi.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a :href="waLink(trialText)" target="_blank" rel="noopener" class="btn-accent rounded-2xl px-6 py-4 text-lg">
                        <LandingIcon name="whatsapp" :size="22" /> Coba Kelas Gratis
                    </a>
                    <a href="#program" class="btn-ghost rounded-2xl border-2 px-6 py-4 text-lg">Lihat Program</a>
                </div>
                <dl class="mt-10 grid max-w-md grid-cols-3 gap-4">
                    <div><dt class="text-sm text-muted">Durasi sesi</dt><dd class="font-display text-2xl font-extrabold">60 menit</dd></div>
                    <div><dt class="text-sm text-muted">Kelompok</dt><dd class="font-display text-2xl font-extrabold">maks. 5</dd></div>
                    <div><dt class="text-sm text-muted">Kelas coba</dt><dd class="font-display text-2xl font-extrabold text-success">Gratis</dd></div>
                </dl>
            </div>

            <!-- Editor kode karya siswa + kartu laporan ke orang tua -->
            <div class="relative min-w-0">
                <div class="-rotate-[1.5deg] overflow-hidden rounded-3xl bg-brand-900 text-white shadow-2xl shadow-brand-900/30">
                    <div class="flex items-center gap-2 border-b border-white/10 px-5 py-3">
                        <span class="h-3 w-3 rounded-full bg-[#ff5f57]"></span><span class="h-3 w-3 rounded-full bg-[#febc2e]"></span><span class="h-3 w-3 rounded-full bg-[#28c840]"></span>
                        <span class="ml-3 font-mono text-xs text-white/60">game_tebak_angka.py · karya Raka, 10 th</span>
                    </div>
                    <pre class="overflow-x-auto px-5 pb-44 pt-5 font-mono text-[13px] leading-7 [font-variant-ligatures:none] sm:pb-32 sm:text-sm"><code><span class="text-sky-300">import</span> random

angka = random.randint(<span class="text-amber-300">1</span>, <span class="text-amber-300">10</span>)
tebakan = <span class="text-amber-300">0</span>

<span class="text-sky-300">while</span> tebakan != angka:
    tebakan = int(input(<span class="text-emerald-300">"Tebak 1-10: "</span>))
    <span class="text-sky-300">if</span> tebakan &lt; angka:
        print(<span class="text-emerald-300">"Terlalu kecil!"</span>)
    <span class="text-sky-300">elif</span> tebakan &gt; angka:
        print(<span class="text-emerald-300">"Terlalu besar!"</span>)

print(<span class="text-emerald-300">"Hore, kamu menang! 🎉"</span>)<span class="landing-caret"></span></code></pre>
                </div>
                <div class="absolute -bottom-10 -left-2 w-[15.5rem] rotate-2 lg:-right-6 lg:left-auto rounded-2xl border border-line bg-white p-4 text-ink shadow-xl sm:-left-8">
                    <p class="text-[11px] font-bold uppercase tracking-[.12em] text-muted">Laporan sesi #8 · ke WhatsApp ortu</p>
                    <p class="mt-1.5 font-display font-bold leading-snug">Raka menyelesaikan game pertamanya</p>
                    <div class="mt-3 flex items-center justify-between text-sm"><span class="text-muted">Kehadiran</span><span class="font-bold text-success">Hadir ✓</span></div>
                    <div class="mt-1.5 flex items-center justify-between text-sm"><span class="text-muted">Pemahaman loop</span><span class="font-bold">4/5</span></div>
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
