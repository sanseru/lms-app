<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';

defineProps({ today: Array, classes: Array, profile: Object });
const user = usePage().props.auth.user;

const upcoming = [
    { icon: '🗺️', title: 'Peta belajar', text: 'Jalur pelajaran ala game, buka level satu per satu.' },
    { icon: '🏆', title: 'XP & lencana', text: 'Kumpulkan poin dari tugas dan proyek.' },
    { icon: '💬', title: 'Tanya jawab', text: 'Tanya guru langsung di setiap pelajaran.' },
];
</script>

<template>
    <Head title="Beranda" />

    <!-- Kartu profil -->
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
            <span v-if="profile.code" class="rounded-full bg-white/20 px-3 py-1 font-mono text-sm backdrop-blur-sm">{{ profile.code }}</span>
            <span v-if="profile.grade" class="rounded-full bg-white/20 px-3 py-1 text-sm font-semibold backdrop-blur-sm">Kelas {{ profile.grade }}</span>
        </div>
    </div>

    <!-- Hari ini -->
    <div class="mt-6 card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display text-lg font-bold text-ink">Kelas hari ini</h2>
            <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Jadwalku →</Link>
        </div>
        <div v-if="today.length" class="space-y-2">
            <SessionItem v-for="s in today" :key="s.id" :session="s" />
        </div>
        <div v-else class="flex items-center gap-4 rounded-xl bg-sky-soft p-4">
            <span class="text-3xl">🌤️</span>
            <p class="text-sm text-ink">Tidak ada kelas hari ini. Istirahat dulu atau ulangi materi kemarin, ya!</p>
        </div>
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Kelasku</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="Kamu belum punya kelas" description="Admin akan segera mendaftarkanmu ke kelas." /></div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Segera hadir</h2>
    <div class="grid gap-4 sm:grid-cols-3">
        <div v-for="item in upcoming" :key="item.title" class="rounded-2xl border border-dashed border-brand-200 bg-brand-50/50 p-5">
            <p class="text-3xl">{{ item.icon }}</p>
            <p class="mt-3 font-display font-bold text-ink">{{ item.title }}</p>
            <p class="mt-1 text-sm text-muted">{{ item.text }}</p>
        </div>
    </div>
</template>
