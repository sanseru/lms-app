<script setup>
import { computed } from 'vue';

const props = defineProps({
    tone: { type: String, default: 'gray' },
    dot: { type: Boolean, default: true },
});

const tones = {
    gray: 'bg-slate-100 text-slate-700 ring-slate-200',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    amber: 'bg-amber-50 text-amber-800 ring-amber-200',
    red: 'bg-rose-50 text-rose-700 ring-rose-200',
    blue: 'bg-sky-50 text-sky-700 ring-sky-200',
    brand: 'bg-brand-50 text-brand-700 ring-brand-200',
};
const dots = { gray: 'bg-slate-400', green: 'bg-emerald-500', amber: 'bg-amber-500', red: 'bg-rose-500', blue: 'bg-sky-500', brand: 'bg-brand-500' };
const cls = computed(() => tones[props.tone] ?? tones.gray);
</script>

<template>
    <span :class="cls" class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset">
        <span v-if="dot" :class="dots[tone]" class="size-1.5 rounded-full" />
        <slot />
    </span>
</template>
