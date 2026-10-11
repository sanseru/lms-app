<script setup>
import { ref } from 'vue';
import LandingIcon from './LandingIcon.vue';
import { plans, waLink } from '../site.js';

const type = ref('privat');
const rupiah = (n) => new Intl.NumberFormat('id-ID').format(n);
</script>

<template>
    <section id="harga" class="border-y border-line bg-white" aria-labelledby="h-harga">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="flex flex-wrap items-center gap-3">
                <h2 id="h-harga" class="text-3xl font-extrabold tracking-tight sm:text-4xl">Paket belajar</h2>
                <span class="rounded-full border border-dashed border-accent-500 px-3 py-1 text-xs font-bold text-accent-600">Harga contoh, sesuaikan sebelum dipakai</span>
            </div>
            <p class="mt-3 max-w-2xl text-muted">Paket berupa kuota sesi. Satu sesi = 60 menit via Zoom. Pembayaran melalui transfer bank dan dikonfirmasi admin.</p>

            <div class="mt-8 inline-flex rounded-xl border border-line bg-chip p-1" role="group" aria-label="Pilih tipe kelas">
                <button
                    v-for="[key, label] in [['privat', 'Privat'], ['kelompok', 'Kelompok (3–5 anak)']]"
                    :key="key"
                    type="button"
                    class="min-h-11 rounded-lg px-5 py-2.5 font-bold"
                    :class="type === key ? 'bg-white shadow-sm' : 'text-muted'"
                    :aria-pressed="type === key"
                    @click="type = key"
                >
                    {{ label }}
                </button>
            </div>

            <div class="mt-8 grid gap-5 md:grid-cols-3">
                <article
                    v-for="p in plans"
                    :key="p.name"
                    class="relative flex flex-col rounded-2xl bg-paper p-7"
                    :class="p.featured ? 'border-2 border-accent-500' : 'border border-line'"
                >
                    <span v-if="p.featured" class="absolute -top-3.5 left-7 rounded-full bg-accent-500 px-3 py-1 text-xs font-bold text-ink">Paling banyak dipilih</span>
                    <h3 class="text-xl font-bold">{{ p.name }}</h3>
                    <p class="mt-1 text-sm text-muted">{{ p.sub }}</p>
                    <p class="mt-5 font-display text-4xl font-extrabold tabular-nums">{{ rupiah(p.price[type]) }}</p>
                    <p class="text-sm text-muted">Rupiah per paket · Rp{{ rupiah(p.price[type] / p.sessions) }}/sesi</p>
                    <ul class="mt-6 grid flex-1 content-start gap-2.5 text-[15px]">
                        <li v-for="perk in p.perks" :key="perk" class="flex gap-2"><LandingIcon name="check" :size="18" class="mt-0.5 shrink-0 text-success-ink" />{{ perk }}</li>
                    </ul>
                    <a
                        :href="waLink(`Halo, saya tertarik paket ${p.name} (${type}).`)"
                        target="_blank"
                        rel="noopener"
                        class="mt-7 py-3 text-base"
                        :class="p.featured ? 'btn-accent' : 'btn-ghost border-2 border-ink/80'"
                    >
                        Pilih {{ p.name }}
                    </a>
                </article>
            </div>
        </div>
    </section>
</template>
