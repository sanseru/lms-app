<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
    status: { type: String, required: true },
    label: { type: String, default: null },
});

const map = {
    active: ['green', 'Active'],
    inactive: ['gray', 'Inactive'],
    upcoming: ['blue', 'Upcoming'],
    finished: ['gray', 'Finished'],
    unpaid: ['amber', 'Unpaid'],
    partial: ['blue', 'Partially paid'],
    paid: ['green', 'Paid'],
    cancelled: ['gray', 'Cancelled'],
    overdue: ['red', 'Overdue'],
    pending: ['amber', 'Awaiting verification'],
    verified: ['green', 'Verified'],
    rejected: ['red', 'Rejected'],
};

const tone = computed(() => map[props.status]?.[0] ?? 'gray');
const text = computed(() => props.label ?? map[props.status]?.[1] ?? props.status);
</script>

<template>
    <Badge :tone="tone">{{ text }}</Badge>
</template>
