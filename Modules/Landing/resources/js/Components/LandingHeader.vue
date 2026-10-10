<script setup>
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import LandingIcon from './LandingIcon.vue';
import { navLinks, site, trialText, waLink } from '../site.js';

defineProps({ user: { type: Object, default: null } });
const open = ref(false);
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-line bg-paper/90 backdrop-blur">
        <div class="mx-auto flex h-[72px] max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
            <a href="#top" class="shrink-0"><img src="/images/logo-header.png" :alt="site.brand" class="h-11 w-auto" /></a>
            <nav class="hidden items-center gap-1 text-[15px] font-semibold text-muted md:flex" aria-label="Navigasi utama">
                <a v-for="l in navLinks" :key="l.href" :href="l.href" class="rounded-lg px-3 py-2 hover:bg-brand-50 hover:text-brand-700">{{ l.label }}</a>
            </nav>
            <div class="flex items-center gap-2">
                <Link :href="user ? route('dashboard') : route('login')" class="btn-ghost hidden text-[15px] sm:inline-flex">
                    <LandingIcon name="login" :size="18" />{{ user ? 'Dashboard' : 'Masuk' }}
                </Link>
                <a :href="waLink(trialText)" target="_blank" rel="noopener" class="btn-accent hidden text-[15px] sm:inline-flex">Coba Gratis</a>
                <button type="button" class="grid h-11 w-11 place-items-center rounded-xl border border-line bg-white md:hidden" :aria-expanded="open" aria-controls="landing-mobile-nav" :aria-label="open ? 'Tutup menu' : 'Buka menu'" @click="open = !open">
                    <LandingIcon :name="open ? 'close' : 'menu'" :size="22" />
                </button>
            </div>
        </div>
        <nav v-show="open" id="landing-mobile-nav" class="grid gap-1 border-t border-line bg-paper px-4 pb-4 pt-2 font-semibold md:hidden" aria-label="Navigasi seluler">
            <a v-for="l in navLinks" :key="l.href" :href="l.href" class="rounded-lg px-3 py-3 hover:bg-brand-50" @click="open = false">{{ l.label }}</a>
            <Link :href="user ? route('dashboard') : route('login')" class="btn-ghost mt-2 py-3 text-base">
                <LandingIcon name="login" :size="18" />{{ user ? 'Ke dashboard' : 'Masuk (siswa, orang tua, tutor)' }}
            </Link>
            <a :href="waLink(trialText)" target="_blank" rel="noopener" class="btn-accent py-3 text-base">Coba Kelas Gratis via WhatsApp</a>
        </nav>
    </header>
</template>
