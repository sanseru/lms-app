<script setup>
defineProps({
    value: { type: Number, default: 0 },
    tone: { type: String, default: 'brand' },
    size: { type: String, default: 'md' },
});

const tones = {
    brand: 'from-brand-500 to-accent-500',
    green: 'from-emerald-500 to-teal-400',
    amber: 'from-amber-400 to-orange-500',
};
</script>

<template>
    <div :class="size === 'sm' ? 'h-1.5' : 'h-2.5'" class="w-full overflow-hidden rounded-full bg-chip" role="progressbar" :aria-valuenow="value" aria-valuemin="0" aria-valuemax="100">
        <div :class="tones[tone] ?? tones.brand" class="h-full rounded-full bg-gradient-to-r transition-all duration-500" :style="{ width: `${Math.min(100, Math.max(0, value))}%` }" />
    </div>
</template>
