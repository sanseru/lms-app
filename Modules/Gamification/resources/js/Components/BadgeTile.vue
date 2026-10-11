<script setup>
defineProps({ badge: { type: Object, required: true } });
const colors = {
    sky: 'from-sky-100 to-sky-50 ring-sky-200', violet: 'from-violet-100 to-violet-50 ring-violet-200', amber: 'from-amber-100 to-amber-50 ring-amber-200',
    rose: 'from-rose-100 to-rose-50 ring-rose-200', emerald: 'from-emerald-100 to-emerald-50 ring-emerald-200', slate: 'from-slate-200 to-slate-50 ring-slate-300',
    orange: 'from-orange-100 to-orange-50 ring-orange-200',
};
</script>

<template>
    <div
        :class="badge.earned_at ? ['bg-gradient-to-br ring-1', colors[badge.color] ?? colors.violet] : 'bg-chip/50 ring-1 ring-line'"
        class="flex flex-col items-center rounded-2xl p-4 text-center"
        :title="badge.how"
    >
        <span :class="badge.earned_at ? '' : 'opacity-30 grayscale'" class="text-4xl drop-shadow-sm">{{ badge.emoji }}</span>
        <p :class="badge.earned_at ? 'text-ink' : 'text-muted'" class="mt-2 text-sm leading-tight font-bold">{{ badge.name }}</p>
        <p class="mt-0.5 text-[11px] leading-snug text-muted">{{ badge.earned_at ? badge.earned_at : badge.how }}</p>
        <div v-if="!badge.earned_at && badge.target > 1" class="mt-2 w-full">
            <div class="h-1 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-brand-400" :style="{ width: `${(badge.progress / badge.target) * 100}%` }" /></div>
            <p class="mt-1 text-[10px] text-muted">{{ badge.progress }}/{{ badge.target }}</p>
        </div>
    </div>
</template>
