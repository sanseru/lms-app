<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { greeting } from '@/lib/format';

defineProps({ stats: Object, today: Array, classes: Array });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Beranda" />

    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-accent-500 via-rose-400 to-brand-600 p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -right-4 -bottom-12 font-mono text-[10rem] leading-none font-black opacity-15 select-none">&lt;/&gt;</span>
        <p class="text-xs font-extrabold tracking-[0.12em] text-white/80 uppercase">Ruang guru</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ greeting() }}, {{ user.name.split(' ')[0] }}!</h1>
        <p class="mt-1 text-white/85">Kamu punya {{ today.length }} kelas hari ini. Semangat mengajar! 🚀</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <StatCard label="Kelas aktif" :value="stats.classes" icon="school" tone="brand" />
        <StatCard label="Total siswa" :value="stats.students" icon="users" tone="accent" />
        <StatCard label="Sesi per minggu" :value="stats.sessions_week" icon="calendar-days" tone="sky" />
    </div>

    <div class="mt-6 card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display font-bold text-ink">Jadwal hari ini</h2>
            <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Jadwal mingguan →</Link>
        </div>
        <div v-if="today.length" class="space-y-2">
            <SessionItem v-for="s in today" :key="s.id" :session="s" :show-teacher="false" />
        </div>
        <EmptyState v-else icon="calendar-days" title="Tidak ada kelas hari ini" />
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Kelas yang kamu ajar</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="Belum ada kelas" description="Admin akan menugaskan kelas untukmu." /></div>
</template>
