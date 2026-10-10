<script setup>
import { computed } from 'vue';

const props = defineProps({
    initials: { type: String, default: '?' },
    seed: { type: [String, Number], default: '' },
    size: { type: String, default: 'md' },
});

const palette = [
    'bg-violet-100 text-violet-700',
    'bg-amber-100 text-amber-700',
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
    'bg-rose-100 text-rose-700',
    'bg-indigo-100 text-indigo-700',
];

const color = computed(() => {
    const key = String(props.seed || props.initials);
    const sum = [...key].reduce((acc, ch) => acc + ch.charCodeAt(0), 0);
    return palette[sum % palette.length];
});

const sizes = { sm: 'size-8 text-xs', md: 'size-10 text-sm', lg: 'size-14 text-lg' };
</script>

<template>
    <span :class="[color, sizes[size]]" class="inline-flex shrink-0 items-center justify-center rounded-full font-bold font-display">
        {{ initials }}
    </span>
</template>
