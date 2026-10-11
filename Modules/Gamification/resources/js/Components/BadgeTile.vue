<script setup>
defineProps({ badge: { type: Object, required: true } });
const colors = {
    sky: 'bg-sky-50 ring-sky-200', violet: 'bg-violet-50 ring-violet-200', amber: 'bg-amber-50 ring-amber-200',
    rose: 'bg-rose-50 ring-rose-200', emerald: 'bg-emerald-50 ring-emerald-200', slate: 'bg-slate-100 ring-slate-300',
    orange: 'bg-orange-50 ring-orange-200',
};
</script>

<template>
    <div
        :class="badge.earned_at ? ['ring-1', colors[badge.color] ?? colors.violet] : 'bg-chip/50 ring-1 ring-line'"
        class="flex flex-col items-center rounded-2xl p-4 text-center"
        :title="badge.how"
    >
        <span :class="badge.earned_at ? '' : 'opacity-30 grayscale'" class="text-4xl" aria-hidden="true">{{ badge.emoji }}</span>
        <p :class="badge.earned_at ? 'text-ink' : 'text-muted'" class="mt-2 text-sm leading-tight font-bold">{{ badge.name }}</p>
        <p class="mt-0.5 text-xs leading-snug text-muted">{{ badge.earned_at ? badge.earned_at : badge.how }}</p>
        <div v-if="!badge.earned_at && badge.target > 1" class="mt-2 w-full">
            <div class="h-1 overflow-hidden rounded-full bg-line"><div class="h-full rounded-full bg-brand-400" :style="{ width: `${(badge.progress / badge.target) * 100}%` }" /></div>
            <p class="mt-1 text-xs text-muted tabular-nums">{{ badge.progress }}/{{ badge.target }}</p>
        </div>
    </div>
</template>
