<script setup>
import { Link } from '@inertiajs/vue3';
import { site } from '../site.js';

const year = new Date().getFullYear();
</script>

<template>
    <footer class="border-t border-line bg-white">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4">
            <div class="min-w-0 lg:col-span-2">
                <img src="/images/logo-header.png" :alt="site.brand" class="h-12 w-auto" />
                <p class="mt-3 max-w-sm text-muted">Kursus coding online untuk anak dan remaja Indonesia. Belajar dari rumah, berkarya untuk masa depan.</p>
            </div>
            <div>
                <p class="font-bold">Kontak</p>
                <ul class="mt-3 grid gap-2 text-muted">
                    <li>WhatsApp: <span class="font-semibold text-ink select-all">{{ site.waDisplay }}</span></li>
                    <li>Email: <span class="font-semibold text-ink select-all">{{ site.email }}</span></li>
                    <li>{{ site.hours }}</li>
                </ul>
            </div>
            <div>
                <p class="font-bold">Jelajahi</p>
                <ul class="mt-3 grid gap-2 text-muted">
                    <li><a href="#program" class="hover:text-ink">Program &amp; level</a></li>
                    <li><a href="#harga" class="hover:text-ink">Paket belajar</a></li>
                    <li><a href="#faq" class="hover:text-ink">FAQ</a></li>
                    <li><Link :href="route('login')" class="hover:text-ink">Masuk ke portal</Link></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-line">
            <p class="mx-auto max-w-6xl px-4 py-5 text-sm text-muted sm:px-6">© {{ year }} {{ site.brand }}. Semua hak dilindungi.</p>
        </div>
    </footer>
</template>
