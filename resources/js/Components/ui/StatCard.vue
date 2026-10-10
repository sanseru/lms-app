<script setup>
import Icon from './Icon.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    icon: { type: String, default: 'sparkles' },
    tone: { type: String, default: 'brand' },
    hint: { type: String, default: null },
});

const tones = {
    brand: 'bg-brand-50 text-brand-600',
    accent: 'bg-accent-50 text-accent-600',
    green: 'bg-emerald-50 text-emerald-600',
    sky: 'bg-sky-50 text-sky-600',
    amber: 'bg-amber-50 text-amber-600',
    red: 'bg-rose-50 text-rose-600',
};
</script>

<template>
    <div class="card flex items-start gap-4 p-5">
        <span :class="tones[tone]" class="grid size-11 shrink-0 place-items-center rounded-xl">
            <Icon :name="icon" :size="20" />
        </span>
        <div class="min-w-0">
            <p class="text-sm font-medium text-muted">{{ label }}</p>
            <p class="mt-0.5 truncate font-display text-2xl font-extrabold tracking-tight text-ink">{{ value }}</p>
            <p v-if="hint" class="mt-0.5 text-xs text-muted">{{ hint }}</p>
        </div>
    </div>
</template>
