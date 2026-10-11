<script setup>
import ProgressBar from './ProgressBar.vue';

/** Kartu level dan XP dari modul Gamifikasi. */
defineProps({ level: { type: Object, required: true }, compact: { type: Boolean, default: false } });
</script>

<template>
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-accent-500 text-white" :class="compact ? 'p-5' : 'p-6 sm:p-8'">
        <span class="pointer-events-none absolute -top-6 -right-4 text-[8rem] leading-none opacity-15 select-none">⭐</span>
        <div class="relative flex items-center gap-4">
            <div :class="compact ? 'size-16' : 'size-20'" class="grid shrink-0 place-items-center rounded-2xl bg-white/15 ring-4 ring-white/25 backdrop-blur-sm">
                <span class="text-[10px] font-bold tracking-widest text-white/80 uppercase">Level</span>
                <span :class="compact ? 'text-2xl' : 'text-3xl'" class="-mt-2 font-display font-black">{{ level.level }}</span>
            </div>
            <div class="min-w-0 flex-1">
                <p :class="compact ? 'text-lg' : 'text-2xl'" class="font-display font-extrabold">{{ level.name }}</p>
                <p class="text-sm text-white/85"><b class="text-white">{{ level.xp }} XP</b> · {{ level.to_next }} XP lagi ke level {{ level.level + 1 }}</p>
                <div class="mt-3 rounded-full bg-white/20 p-0.5"><ProgressBar :value="level.percent" tone="amber" size="sm" /></div>
            </div>
        </div>
    </div>
</template>
