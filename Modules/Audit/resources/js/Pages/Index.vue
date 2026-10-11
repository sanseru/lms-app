<script setup>
import { Head } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import { useFilters } from '@/composables/useFilters';
import { rupiah } from '@/lib/format';

const props = defineProps({ activities: Object, filters: Object, categories: Array, events: Object, users: Array, failedLogins: Number });

const filters = useFilters(
    {
        search: props.filters.search ?? '',
        category: props.filters.category ?? '',
        event: props.filters.event ?? '',
        user: props.filters.user ?? '',
        from: props.filters.from ?? '',
        to: props.filters.to ?? '',
    },
    'audit.index',
);

const fieldLabels = {
    name: 'Nama', username: 'Username', email: 'Email', phone: 'Telepon', status: 'Status', password: 'Kata sandi',
    rate_per_session: 'Honor per sesi', bank_name: 'Bank', bank_account: 'No. rekening',
    amount: 'Jumlah', due_date: 'Jatuh tempo', title: 'Judul', rejection_reason: 'Alasan ditolak',
    price: 'Harga', sessions_count: 'Jumlah sesi', valid_days: 'Masa berlaku (hari)', is_active: 'Dijual',
    sessions_total: 'Kuota sesi', expires_at: 'Berlaku s/d', total: 'Total', bonus: 'Bonus', deduction: 'Potongan',
    score: 'Nilai', starts_at: 'Mulai', teacher_id: 'Guru', cancel_reason: 'Alasan batal',
    schedule_days: 'Hari', start_time: 'Jam mulai', end_time: 'Jam selesai', revoked_at: 'Dicabut',
};

const show = (change, value) => {
    if (value === null || value === undefined || value === '') return '–';
    if (change.money && !isNaN(value)) return rupiah(Number(value));
    if (Array.isArray(value)) return value.join(', ');
    const date = String(value).match(/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2}))?$/);
    if (date) {
        const [, y, m, d, hh, mm] = date;
        const text = new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        return hh ? `${text} ${hh}:${mm}` : text;
    }
    return String(value);
};

const eventTone = {
    created: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    updated: 'bg-sky-50 text-sky-700 ring-sky-200',
    deleted: 'bg-rose-50 text-rose-700 ring-rose-200',
    login: 'bg-chip text-muted ring-line',
    login_failed: 'bg-amber-50 text-amber-800 ring-amber-200',
};

const reset = () => Object.assign(filters, { search: '', category: '', event: '', user: '', from: '', to: '' });
</script>

<template>
    <Head title="Jejak Audit" />

    <PageHeader eyebrow="Lainnya" title="Jejak audit" description="Catatan siapa mengubah apa dan kapan untuk tagihan, pembayaran, honor, nilai, kehadiran, jadwal, dan akun. Catatan tidak bisa diubah atau dihapus." />

    <div v-if="failedLogins >= 5" class="mb-5 flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <Icon name="alert-triangle" :size="18" class="mt-0.5 shrink-0" />
        <p><strong>{{ failedLogins }} percobaan masuk gagal</strong> dalam 24 jam terakhir. Periksa apakah ada yang mencoba menebak kata sandi.</p>
    </div>

    <div class="card overflow-hidden">
        <div class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2 [&>label]:max-w-none"><SearchInput v-model="filters.search" placeholder="Cari objek, mis. nomor tagihan…" /></div>
            <select v-model="filters.category" class="field" aria-label="Kategori">
                <option value="">Semua kategori</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>
            <select v-model="filters.event" class="field" aria-label="Kejadian">
                <option value="">Semua kejadian</option>
                <option v-for="(label, key) in events" :key="key" :value="key">{{ label }}</option>
            </select>
            <select v-model="filters.user" class="field" aria-label="Pelaku">
                <option value="">Semua pelaku</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <label class="flex items-center gap-2 text-sm text-muted">
                <span class="w-12 shrink-0">Dari</span>
                <input v-model="filters.from" type="date" class="field min-w-0" />
            </label>
            <label class="flex items-center gap-2 text-sm text-muted">
                <span class="w-12 shrink-0">Sampai</span>
                <input v-model="filters.to" type="date" class="field min-w-0" />
            </label>
        </div>

        <ol v-if="activities.data.length" class="divide-y divide-line">
            <li v-for="a in activities.data" :key="a.id" class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:gap-5">
                <div class="shrink-0 sm:w-36">
                    <p class="text-sm font-semibold text-ink" :title="a.at">{{ a.ago }}</p>
                    <p class="text-xs text-muted">{{ a.at }}</p>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-bold ring-1" :class="eventTone[a.event]">{{ a.event_label }}</span>
                        <span class="text-xs font-semibold tracking-wide text-muted uppercase">{{ a.category }}</span>
                    </div>
                    <p class="mt-1 font-semibold break-words text-ink">{{ a.label ?? '–' }}</p>
                    <p class="text-sm text-muted">
                        oleh <span class="font-semibold text-ink">{{ a.causer ?? 'Sistem' }}</span>
                        <span v-if="a.ip"> · {{ a.ip }}</span>
                    </p>
                    <dl v-if="a.changes.length" class="mt-2 grid gap-1 rounded-xl bg-chip/60 p-3 text-sm">
                        <div v-for="c in a.changes" :key="c.field" class="flex flex-wrap gap-x-2">
                            <dt class="text-muted">{{ fieldLabels[c.field] ?? c.field }}:</dt>
                            <dd class="text-ink">
                                <template v-if="a.event === 'updated'">
                                    <span class="text-muted line-through">{{ show(c, c.from) }}</span>
                                    <span class="mx-1 text-muted" aria-label="menjadi">→</span>
                                </template>
                                <span class="font-semibold">{{ show(c, c.to) }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </li>
        </ol>
        <EmptyState v-else icon="shield-check" title="Belum ada catatan" description="Perubahan pada data penting akan muncul di sini.">
            <button v-if="Object.values(filters).some(Boolean)" class="btn-ghost" @click="reset">Hapus filter</button>
        </EmptyState>
        <Pagination :meta="activities" />
    </div>
</template>
