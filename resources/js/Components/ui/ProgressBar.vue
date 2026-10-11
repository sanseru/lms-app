<script setup>
defineProps({
    value: { type: Number, default: 0 },
    tone: { type: String, default: 'brand' },
    size: { type: String, default: 'md' },
});

const tones = {
    brand: 'bg-brand-600',
    green: 'bg-emerald-500',
    amber: 'bg-amber-400',
};
</script>

<template>
    <div :class="size === 'sm' ? 'h-1.5' : 'h-2.5'" class="w-full overflow-hidden rounded-full bg-chip" role="progressbar" :aria-valuenow="value" aria-valuemin="0" aria-valuemax="100">
        <div :class="tones[tone] ?? tones.brand" class="h-full rounded-full transition-[width] duration-500 ease-out" :style="{ width: `${Math.min(100, Math.max(0, value))}%` }" />
    </div>
</template>
