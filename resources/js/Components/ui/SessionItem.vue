<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { classColors } from '@/lib/format';
import Icon from './Icon.vue';

/** Baris satu sesi (data dari ClassSession::toItem). */
const props = defineProps({
    session: { type: Object, required: true },
    showTeacher: { type: Boolean, default: true },
    showDate: { type: Boolean, default: false },
});
const color = computed(() => classColors[props.session.class?.color] ?? classColors.violet);
</script>

<template>
    <div class="flex items-center gap-4 rounded-xl border border-line bg-white p-3 transition hover:border-brand-200">
        <div class="w-14 shrink-0 text-center">
            <p v-if="showDate" class="text-[11px] font-bold text-accent-600 uppercase">{{ session.date_label.split(',')[0].slice(0, 3) }} {{ session.date.slice(8) }}</p>
            <p class="font-display text-base font-extrabold text-ink">{{ session.start_time }}</p>
            <p class="text-xs text-muted">{{ session.end_time }}</p>
        </div>
        <span :class="color.solid" class="h-10 w-1 shrink-0 rounded-full" />
        <Link :href="route('sessions.show', session.id)" class="min-w-0 flex-1">
            <p class="truncate font-semibold text-ink hover:text-brand-600">{{ session.class?.name }}</p>
            <p class="truncate text-sm text-muted">
                <template v-if="session.topic">{{ session.topic }}</template>
                <template v-else>{{ session.class?.subject }}</template>
                <template v-if="showTeacher && session.teacher"> · {{ session.teacher.name }}</template>
            </p>
        </Link>
        <span v-if="session.status === 'completed'" class="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-emerald-600">
            <Icon name="check" :size="16" /> <span class="hidden sm:inline">Selesai</span>
        </span>
        <span v-else-if="session.status === 'cancelled'" class="shrink-0 text-sm font-semibold text-muted">Batal</span>
        <Link v-else-if="session.needs_report" :href="route('sessions.show', session.id)" class="btn-primary shrink-0 px-3 py-2">
            <Icon name="clipboard-check" :size="16" /> <span class="hidden sm:inline">Isi absensi</span>
        </Link>
        <a v-else-if="session.zoom_url" :href="session.zoom_url" target="_blank" rel="noopener" class="btn-accent shrink-0 px-3 py-2">
            <Icon name="video" :size="16" /> <span class="hidden sm:inline">Masuk Zoom</span>
        </a>
    </div>
</template>
