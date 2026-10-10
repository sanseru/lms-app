<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import { greeting, rupiah } from '@/lib/format';

defineProps({ stats: Object, today: Array, pendingPayments: Array, recentStudents: Array });
const user = usePage().props.auth.user;
const todayLabel = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });
</script>

<template>
    <Head title="Beranda" />

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">{{ todayLabel }}</p>
            <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-ink">{{ greeting() }}, {{ user.name.split(' ')[0] }} 👋</h1>
            <p class="mt-1 text-muted">Ini ringkasan operasional kursus hari ini.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <Link :href="route('people.students.create')" class="btn-ghost"><Icon name="user-plus" :size="18" /> Siswa baru</Link>
            <Link :href="route('classes.create')" class="btn-accent"><Icon name="plus" :size="18" /> Buat kelas</Link>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Siswa aktif" :value="stats.students" icon="graduation-cap" tone="brand" />
        <StatCard label="Guru aktif" :value="stats.teachers" icon="presentation" tone="accent" />
        <StatCard label="Kelas berjalan" :value="stats.classes" icon="school" tone="sky" />
        <StatCard label="Tagihan belum lunas" :value="rupiah(stats.outstanding)" icon="wallet" tone="amber" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card p-5 lg:col-span-2">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="font-display font-bold text-ink">Kelas hari ini</h2>
                    <p class="text-sm text-muted">{{ today.length }} sesi terjadwal</p>
                </div>
                <Link :href="route('schedule.index')" class="text-sm font-semibold text-brand-600 hover:underline">Lihat jadwal →</Link>
            </div>
            <div v-if="today.length" class="space-y-2">
                <SessionItem v-for="s in today" :key="s.id" :session="s" />
            </div>
            <EmptyState v-else icon="calendar-days" title="Tidak ada kelas hari ini" description="Waktunya menyiapkan materi atau merapikan data." />
        </div>

        <div class="space-y-6">
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-display font-bold text-ink">Bukti transfer baru</h2>
                    <Link :href="route('finance.overview')" class="text-sm font-semibold text-brand-600 hover:underline">Keuangan →</Link>
                </div>
                <ul v-if="pendingPayments.length" class="divide-y divide-line">
                    <li v-for="p in pendingPayments" :key="p.id">
                        <Link :href="route('finance.invoices.show', p.invoice_id)" class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-chip/50">
                            <span class="min-w-0"><span class="block truncate font-semibold text-ink">{{ p.student }}</span><span class="text-xs text-muted">{{ p.paid_at }}</span></span>
                            <span class="font-display text-sm font-bold text-ink">{{ rupiah(p.amount) }}</span>
                        </Link>
                    </li>
                </ul>
                <p v-else class="px-5 py-6 text-center text-sm text-muted">Tidak ada yang perlu diverifikasi.</p>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4"><h2 class="font-display font-bold text-ink">Siswa terbaru</h2></div>
                <ul class="divide-y divide-line">
                    <li v-for="s in recentStudents" :key="s.id" class="flex items-center gap-3 px-5 py-3">
                        <Avatar :initials="s.initials" :seed="s.id" size="sm" />
                        <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-ink">{{ s.name }}</span><span class="font-mono text-xs text-muted">{{ s.code }}</span></span>
                        <span class="text-xs text-muted">{{ s.joined }}</span>
                    </li>
                    <li v-if="!recentStudents.length" class="px-5 py-6 text-center text-sm text-muted">Belum ada siswa.</li>
                </ul>
            </div>
        </div>
    </div>
</template>
