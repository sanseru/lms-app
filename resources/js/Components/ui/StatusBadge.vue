<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
});

const map = {
    active: ['green', 'Aktif'],
    inactive: ['gray', 'Nonaktif'],
    upcoming: ['blue', 'Akan datang'],
    finished: ['gray', 'Selesai'],
    unpaid: ['amber', 'Belum bayar'],
    partial: ['blue', 'Sebagian'],
    paid: ['green', 'Lunas'],
    cancelled: ['gray', 'Dibatalkan'],
    overdue: ['red', 'Terlambat'],
    pending: ['amber', 'Menunggu verifikasi'],
    verified: ['green', 'Terverifikasi'],
    rejected: ['red', 'Ditolak'],
};

const tone = computed(() => map[props.status]?.[0] ?? 'gray');
const text = computed(() => props.label ?? map[props.status]?.[1] ?? props.status);
</script>

<template>
    <Badge :tone="tone">{{ text }}</Badge>
</template>
