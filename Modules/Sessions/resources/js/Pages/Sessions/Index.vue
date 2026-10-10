<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';

const props = defineProps({ sessions: Object, tab: String, counts: Object });

const tabs = [
    ['needs_report', 'Perlu diisi'],
    ['upcoming', 'Akan datang'],
    ['completed', 'Selesai'],
    ['flagged', 'Ditandai'],
    ['cancelled', 'Batal'],
];
const empty = {
    needs_report: ['Semua absensi sudah diisi', 'Mantap! Tidak ada sesi yang tertinggal.'],
    upcoming: ['Belum ada sesi terjadwal', 'Sesi dibuat otomatis dari jadwal mingguan kelas.'],
    completed: ['Belum ada sesi selesai', null],
    flagged: ['Tidak ada sesi yang ditandai', 'Guru bisa menandai sesi bermasalah saat mengisi absensi.'],
    cancelled: ['Tidak ada sesi batal', null],
};
</script>

<template>
    <Head title="Sesi & Absensi" />

    <PageHeader eyebrow="Akademik" title="Sesi & absensi" description="Isi absensi dan catatan setiap sesi dalam satu layar. Kuota siswa dan honor tutor ikut tercatat otomatis." />

    <div class="mb-4 flex gap-2 overflow-x-auto pb-1">
        <Link
            v-for="[key, label] in tabs"
            :key="key"
            :href="route('sessions.index', { tab: key })"
            preserve-state
            :class="tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="inline-flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
        >
            {{ label }}
            <span v-if="counts[key]" :class="tab === key ? 'bg-white/25' : 'bg-amber-100 text-amber-800'" class="rounded-full px-2 text-xs">{{ counts[key] }}</span>
        </Link>
    </div>

    <div class="card overflow-hidden">
        <div v-if="sessions.data.length" class="space-y-2 p-4">
            <SessionItem v-for="s in sessions.data" :key="s.id" :session="s" show-date />
        </div>
        <EmptyState v-else icon="clipboard-check" :title="empty[tab][0]" :description="empty[tab][1]" />
        <Pagination :meta="sessions" />
    </div>
</template>
