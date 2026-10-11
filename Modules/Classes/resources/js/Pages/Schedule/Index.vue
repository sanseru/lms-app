<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ days: Array, week: Object, calendarUrl: String });

const copied = ref(false);
const copyCalendar = async () => {
    try {
        await navigator.clipboard.writeText(props.calendarUrl);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch {
        /* clipboard tidak tersedia */
    }
};
const makeCalendar = () => router.post(route('calendar.regenerate'), {}, { preserveScroll: true });
const total = computed(() => props.days.reduce((n, d) => n + d.sessions.filter((s) => !['cancelled', 'rescheduled'].includes(s.status)).length, 0));
</script>

<template>
    <Head title="Jadwal" />

    <PageHeader eyebrow="Akademik" title="Jadwal mingguan" :description="`${total} sesi kelas minggu ini. Semua waktu dalam WIB.`">
        <template #actions>
            <div class="flex items-center gap-1 rounded-xl border border-line bg-white p-1">
                <Link :href="route('schedule.index', { week: week.prev })" class="rounded-lg p-2 text-muted hover:bg-chip hover:text-ink" preserve-scroll aria-label="Minggu sebelumnya">
                    <Icon name="chevron-left" />
                </Link>
                <span class="px-2 text-sm font-semibold text-ink">{{ week.label }}</span>
                <Link :href="route('schedule.index', { week: week.next })" class="rounded-lg p-2 text-muted hover:bg-chip hover:text-ink" preserve-scroll aria-label="Minggu berikutnya">
                    <Icon name="chevron-right" />
                </Link>
            </div>
            <Link :href="route('schedule.index')" class="btn-ghost">Minggu ini</Link>
        </template>
    </PageHeader>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-7">
        <section
            v-for="day in days"
            :key="day.date"
            :class="day.is_today ? 'border-brand-300 ring-2 ring-brand-100' : 'border-line'"
            class="flex min-h-40 flex-col rounded-2xl border bg-white"
        >
            <header class="flex flex-wrap items-baseline justify-between gap-x-2 border-b border-line px-4 py-3">
                <p :class="day.is_today ? 'text-brand-600' : 'text-ink'" class="font-display font-bold">{{ day.day }}</p>
                <p class="text-xs text-muted">{{ day.label }}</p>
                <p v-if="day.is_today" class="w-full text-xs font-extrabold tracking-wider text-accent-600 uppercase">Hari ini</p>
            </header>
            <div class="flex flex-1 flex-col gap-2 p-3">
                <Link
                    v-for="s in day.sessions"
                    :key="s.id"
                    :href="route('sessions.show', s.id)"
                    :class="[
                        ['cancelled', 'rescheduled'].includes(s.status) ? 'bg-chip ring-line opacity-60' : [classColors[s.class.color]?.bg, classColors[s.class.color]?.ring],
                    ]"
                    class="block rounded-xl p-3 ring-1 transition ring-inset hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between gap-1">
                        <p :class="classColors[s.class.color]?.text" class="font-mono text-xs font-medium">{{ s.start_time }}–{{ s.end_time }}</p>
                        <span v-if="s.status === 'completed'" class="text-emerald-700" title="Selesai"><Icon name="check" :size="14" /></span>
                        <span v-else-if="s.needs_report" class="size-2 rounded-full bg-amber-500" title="Absensi belum diisi" />
                    </div>
                    <p class="mt-0.5 text-sm leading-snug font-bold text-ink" :class="['cancelled', 'rescheduled'].includes(s.status) && 'line-through'">{{ s.class.name }}</p>
                    <p v-if="s.teacher" class="mt-1 truncate text-xs text-muted">{{ s.teacher.name }}</p>
                </Link>
                <p v-if="!day.sessions.length" class="m-auto text-xs text-muted">Tidak ada kelas</p>
            </div>
        </section>
    </div>

    <div class="card mt-6 flex flex-col gap-4 p-5 sm:flex-row sm:items-center">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><Icon name="calendar-days" /></span>
        <div class="min-w-0 flex-1">
            <h2 class="font-display font-bold text-ink">Tampilkan jadwal di kalender HP</h2>
            <p class="text-sm text-muted">
                Langganan tautan ini di Google Calendar (Tambah kalender → Dari URL), Apple Calendar, atau Outlook. Jadwal dan link Zoom ikut diperbarui otomatis.
            </p>
            <p v-if="calendarUrl" class="mt-2 truncate rounded-lg bg-chip px-3 py-1.5 font-mono text-xs text-ink">{{ calendarUrl }}</p>
        </div>
        <div class="flex shrink-0 flex-wrap gap-2">
            <template v-if="calendarUrl">
                <button class="btn-primary" @click="copyCalendar"><Icon name="link" :size="16" /> {{ copied ? 'Tersalin' : 'Salin tautan' }}</button>
                <button class="btn-ghost" title="Tautan lama tidak berlaku lagi" @click="makeCalendar">Buat ulang</button>
            </template>
            <button v-else class="btn-primary" @click="makeCalendar"><Icon name="plus" :size="16" /> Buat tautan kalender</button>
        </div>
    </div>
</template>
