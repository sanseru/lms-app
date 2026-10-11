<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Avatar from '@/Components/ui/Avatar.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';

const props = defineProps({ month: Object, months: Array, filters: Object, staff: Boolean, classOptions: Array, students: Object });

const filters = useFilters({ bulan: props.month.key, search: props.filters.search ?? '', class: props.filters.class ?? '' }, 'reports.index');
const open = (s) => router.visit(route('reports.show', [s.id, props.month.key]));
</script>

<template>
    <Head title="Laporan Bulanan" />

    <PageHeader
        :eyebrow="staff ? 'Akademik' : 'Laporan'"
        title="Laporan belajar bulanan"
        :description="staff
            ? 'Rangkuman kehadiran, materi, nilai, dan prestasi tiap siswa. Tulis catatan singkat per kelas, lalu orang tua bisa membaca atau menyimpannya sebagai PDF.'
            : 'Rangkuman sebulan: kehadiran, catatan guru, nilai, dan prestasi. Bisa dicetak atau disimpan sebagai PDF.'"
    >
        <template #actions>
            <select v-model="filters.bulan" class="field w-48" aria-label="Bulan">
                <option v-for="m in months" :key="m.key" :value="m.key">{{ m.label }}</option>
            </select>
        </template>
    </PageHeader>

    <!-- Orang tua & siswa: kartu per anak -->
    <div v-if="!staff" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <Link v-for="s in students.data" :key="s.id" :href="route('reports.show', [s.id, month.key])" class="card group flex flex-col p-5 transition hover:border-brand-300">
            <div class="flex items-center gap-3">
                <Avatar :initials="s.initials" :seed="s.id" />
                <div class="min-w-0">
                    <p class="truncate font-display font-bold text-ink">{{ s.name }}</p>
                    <p class="truncate text-sm text-muted">{{ s.classes.join(', ') || 'Belum ada kelas' }}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-muted">{{ month.label }}</p>
            <p class="font-semibold text-ink">{{ s.sessions ? `Hadir ${s.present} dari ${s.sessions} sesi` : 'Belum ada sesi selesai' }}</p>
            <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-600 group-hover:underline">Buka laporan <Icon name="arrow-right" :size="15" /></span>
        </Link>
        <EmptyState v-if="!students.data.length" icon="file-text" title="Belum ada laporan" description="Laporan muncul setelah anak terdaftar di kelas." class="card sm:col-span-2 xl:col-span-3" />
    </div>

    <!-- Guru & admin: tabel siswa -->
    <div v-else class="card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center">
            <SearchInput v-model="filters.search" placeholder="Cari nama siswa…" />
            <select v-model="filters.class" class="field sm:w-60" aria-label="Kelas">
                <option value="">Semua kelas</option>
                <option v-for="c in classOptions" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
        </div>
        <div v-if="students.data.length" class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-chip/60 text-xs font-bold tracking-wide text-muted uppercase">
                    <tr>
                        <th class="px-5 py-3">Siswa</th>
                        <th class="px-5 py-3">Kelas</th>
                        <th class="px-5 py-3">Kehadiran {{ month.label }}</th>
                        <th class="px-5 py-3">Catatan guru</th>
                        <th class="px-5 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    <tr v-for="s in students.data" :key="s.id" class="cursor-pointer hover:bg-chip/40" @click="open(s)">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <Avatar :initials="s.initials" :seed="s.id" size="sm" />
                                <div><p class="font-semibold text-ink">{{ s.name }}</p><p v-if="s.code" class="text-xs text-muted">{{ s.code }}</p></div>
                            </div>
                        </td>
                        <td class="px-5 py-3 text-muted">{{ s.classes.join(', ') || '–' }}</td>
                        <td class="px-5 py-3">
                            <span v-if="s.sessions" class="font-semibold text-ink">{{ s.present }}/{{ s.sessions }}</span>
                            <span v-else class="text-muted">Belum ada sesi</span>
                        </td>
                        <td class="px-5 py-3">
                            <span v-if="s.notes_missing" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-bold text-amber-800 ring-1 ring-amber-200">{{ s.notes_missing }} belum ditulis</span>
                            <span v-else-if="s.classes.length" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200"><Icon name="check" :size="13" /> Lengkap</span>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <Link :href="route('reports.show', [s.id, month.key])" class="text-xs font-semibold text-brand-600 hover:underline" @click.stop>Buka</Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <EmptyState v-else icon="file-text" title="Tidak ada siswa" description="Coba ubah pencarian atau pilihan kelas." />
        <Pagination :meta="students" />
    </div>
</template>
