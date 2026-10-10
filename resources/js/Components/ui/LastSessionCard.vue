<script setup>
import { Link } from '@inertiajs/vue3';
import StatusBadge from './StatusBadge.vue';

/** Catatan sesi terakhir: materi, PR, dan pesan guru. */
defineProps({ session: { type: Object, required: true }, forParent: { type: Boolean, default: false } });
const tone = { present: 'active', excused: 'upcoming', sick: 'upcoming', absent: 'overdue' };
</script>

<template>
    <div class="rounded-xl border border-line bg-white p-4">
        <div class="flex items-start justify-between gap-2">
            <div>
                <p class="text-xs font-semibold text-muted">{{ session.date_label }} · {{ session.class?.name }}</p>
                <Link :href="route('sessions.show', session.id)" class="font-display font-bold text-ink hover:text-brand-600">{{ session.topic ?? 'Sesi kelas' }}</Link>
            </div>
            <StatusBadge :status="tone[session.attendance_status]" :label="session.attendance" />
        </div>
        <p v-if="session.homework" class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-ink"><b>PR:</b> {{ session.homework }}</p>
        <p v-if="forParent && session.parent_note" class="mt-2 rounded-lg bg-sky-soft px-3 py-2 text-sm text-ink">
            <b>Catatan guru:</b> {{ session.parent_note }}
        </p>
    </div>
</template>
