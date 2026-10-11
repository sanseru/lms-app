<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import LastSessionCard from '@/Components/ui/LastSessionCard.vue';
import QuotaCard from '@/Components/ui/QuotaCard.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import LearningCard from '@/Components/ui/LearningCard.vue';

defineProps({ upcoming: Array, lastSession: Object, classes: Array, packages: Array, stats: Object, profile: Object, learning: Array });
const user = usePage().props.auth.user;

const soon = [
    { icon: '🗺️', title: 'Peta belajar', text: 'Jalur pelajaran ala game, buka level satu per satu.' },
    { icon: '🏆', title: 'XP & lencana', text: 'Kumpulkan poin dari tugas dan proyek.' },
];
</script>

<template>
    <Head title="Beranda" />

    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-600 via-violet-500 to-accent-500 p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -top-6 -right-6 text-[9rem] opacity-20 select-none">🚀</span>
        <div class="relative flex items-center gap-4">
            <Avatar :initials="user.initials" :seed="user.id" size="lg" class="!bg-white !text-brand-700 ring-4 ring-white/30" />
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">Hai, {{ user.name.split(' ')[0] }}! 👋</h1>
                <p class="text-white/85">Siap ngoding hari ini?</p>
            </div>
        </div>
        <div class="relative mt-6 flex flex-wrap gap-3">
            <span class="rounded-full bg-white/20 px-3 py-1 text-sm font-semibold backdrop-blur-sm">🎒 {{ classes.length }} kelas</span>
            <span class="rounded-full bg-white/20 px-3 py-1 text-sm font-semibold backdrop-blur-sm">✅ {{ stats.present }} sesi dihadiri</span>
            <span v-if="stats.rate !== null" class="rounded-full bg-white/20 px-3 py-1 text-sm font-semibold backdrop-blur-sm">🔥 Kehadiran {{ stats.rate }}%</span>
            <span v-if="profile.code" class="rounded-full bg-white/20 px-3 py-1 font-mono text-sm backdrop-blur-sm">{{ profile.code }}</span>
        </div>
    </div>

    <div v-if="learning.length" class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold text-ink">Lanjutkan belajar</h2>
            <Link :href="route('learning.courses.index')" class="text-sm font-semibold text-brand-600 hover:underline">Semua materi →</Link>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <LearningCard v-for="c in learning" :key="c.id" :course="c" />
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-display text-lg font-bold text-ink">Sesi berikutnya</h2>
                <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Jadwalku →</Link>
            </div>
            <div v-if="upcoming.length" class="space-y-2">
                <SessionItem v-for="s in upcoming" :key="s.id" :session="s" show-date />
            </div>
            <div v-else class="flex items-center gap-4 rounded-xl bg-sky-soft p-4">
                <span class="text-3xl">🌤️</span>
                <p class="text-sm text-ink">Belum ada sesi terjadwal. Istirahat dulu atau ulangi materi kemarin, ya!</p>
            </div>
        </div>
        <div class="space-y-4">
            <div v-if="lastSession">
                <h2 class="mb-2 font-display font-bold text-ink">Pertemuan terakhir</h2>
                <LastSessionCard :session="lastSession" />
            </div>
            <div v-if="packages.length">
                <h2 class="mb-2 font-display font-bold text-ink">Kuota belajar</h2>
                <div class="space-y-3"><QuotaCard v-for="p in packages" :key="p.id" :pkg="p" /></div>
            </div>
        </div>
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Kelasku</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="Kamu belum punya kelas" description="Admin akan segera mendaftarkanmu ke kelas." /></div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Segera hadir</h2>
    <div class="grid gap-4 sm:grid-cols-2">
        <div v-for="item in soon" :key="item.title" class="rounded-2xl border border-dashed border-brand-200 bg-brand-50/50 p-5">
            <p class="text-3xl">{{ item.icon }}</p>
            <p class="mt-3 font-display font-bold text-ink">{{ item.title }}</p>
            <p class="mt-1 text-sm text-muted">{{ item.text }}</p>
        </div>
    </div>
</template>
