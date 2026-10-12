<script setup>
import ProgressBar from './ProgressBar.vue';

/** Level and XP card from the Gamification module. */
defineProps({ level: { type: Object, required: true }, compact: { type: Boolean, default: false } });
</script>

<template>
    <div class="panel-brand" :class="compact ? 'p-5' : 'p-6 sm:p-8'">
        <div class="relative flex items-center gap-4">
            <div :class="compact ? 'size-16' : 'size-20'" class="grid shrink-0 place-items-center rounded-2xl bg-accent-500 text-ink shadow-[0_4px_0_var(--color-accent-700)]">
                <span class="text-xs font-bold">Level</span>
                <span :class="compact ? 'text-2xl' : 'text-3xl'" class="-mt-2 font-display font-black tabular-nums">{{ level.level }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p :class="compact ? 'text-lg' : 'text-2xl'" class="font-display font-extrabold">{{ level.name }}</p>
                <p class="text-sm text-white/85"><b class="text-white tabular-nums">{{ level.xp }} XP</b> · {{ level.to_next }} XP to level {{ level.level + 1 }}</p>
                <div class="mt-3 rounded-full bg-white/20 p-0.5"><ProgressBar :value="level.percent" tone="amber" size="sm" /></div>
            </div>
        </div>
    </div>
</template>
