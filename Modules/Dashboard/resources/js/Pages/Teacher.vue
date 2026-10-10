<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ClassCard from '@/Components/ui/ClassCard.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { greeting, rupiah } from '@/lib/format';

defineProps({ stats: Object, today: Array, needsReport: Array, classes: Array });
const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Beranda" />

    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-accent-500 via-rose-400 to-brand-600 p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -right-4 -bottom-12 font-mono text-[10rem] leading-none font-black opacity-15 select-none">&lt;/&gt;</span>
        <p class="text-xs font-extrabold tracking-[0.12em] text-white/80 uppercase">Ruang guru</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ greeting() }}, {{ user.name.split(' ')[0] }}!</h1>
        <p class="mt-1 text-white/85">Kamu punya {{ today.length }} sesi hari ini. Semangat mengajar! 🚀</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Kelas aktif" :value="stats.classes" icon="school" tone="brand" />
        <StatCard label="Total siswa" :value="stats.students" icon="users" tone="accent" />
        <StatCard label="Sesi selesai bulan ini" :value="stats.sessions_month" icon="clipboard-check" tone="sky" />
        <StatCard label="Honor belum direkap" :value="rupiah(stats.fee_pending)" icon="hand-coins" tone="green" />
    </div>

    <div v-if="needsReport.length" class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
        <div class="mb-3 flex items-center gap-2">
            <Icon name="clipboard-check" class="text-amber-600" />
            <h2 class="font-display font-bold text-ink">{{ needsReport.length }} sesi belum diisi absensinya</h2>
        </div>
        <p class="mb-4 text-sm text-muted">Isi absensi dan catatan agar kuota siswa terpotong, orang tua menerima catatan, dan honormu tercatat.</p>
        <div class="space-y-2">
            <SessionItem v-for="s in needsReport" :key="s.id" :session="s" :show-teacher="false" show-date />
        </div>
    </div>

    <div class="mt-6 card p-5">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-display font-bold text-ink">Jadwal hari ini</h2>
            <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Jadwal mingguan →</Link>
        </div>
        <div v-if="today.length" class="space-y-2">
            <SessionItem v-for="s in today" :key="s.id" :session="s" :show-teacher="false" />
        </div>
        <EmptyState v-else icon="calendar-days" title="Tidak ada sesi hari ini" />
    </div>

    <h2 class="mt-8 mb-4 font-display text-lg font-bold text-ink">Kelas yang kamu ajar</h2>
    <div v-if="classes.length" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        <ClassCard v-for="c in classes" :key="c.id" :class-room="c" />
    </div>
    <div v-else class="card"><EmptyState icon="school" title="Belum ada kelas" description="Admin akan menugaskan kelas untukmu." /></div>
</template>
