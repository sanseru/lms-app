<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { classColors } from '@/lib/format';

const props = defineProps({ days: Array, week: Object });
const total = computed(() => props.days.reduce((n, d) => n + d.sessions.length, 0));
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
                <p v-if="day.is_today" class="w-full text-[10px] font-extrabold tracking-wider text-accent-600 uppercase">Hari ini</p>
            </header>
            <div class="flex flex-1 flex-col gap-2 p-3">
                <Link
                    v-for="s in day.sessions"
                    :key="s.id"
                    :href="route('classes.show', s.id)"
                    :class="[classColors[s.color]?.bg, classColors[s.color]?.ring]"
                    class="block rounded-xl p-3 ring-1 transition hover:-translate-y-0.5 ring-inset"
                >
                    <p :class="classColors[s.color]?.text" class="font-mono text-xs font-medium">{{ s.start_time }}–{{ s.end_time }}</p>
                    <p class="mt-0.5 text-sm leading-snug font-bold text-ink">{{ s.name }}</p>
                    <p v-if="s.teacher" class="mt-1 truncate text-xs text-muted">{{ s.teacher.name }}</p>
                </Link>
                <p v-if="!day.sessions.length" class="m-auto text-xs text-muted/70">Tidak ada kelas</p>
            </div>
        </section>
    </div>
</template>
