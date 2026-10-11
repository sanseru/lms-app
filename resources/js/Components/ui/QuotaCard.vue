<script setup>
import { computed } from 'vue';
import StatusBadge from './StatusBadge.vue';

/** Remaining quota of one package (data from StudentPackage::toSummary). */
const props = defineProps({ pkg: { type: Object, required: true } });

const percent = computed(() => (props.pkg.total ? Math.round((props.pkg.used / props.pkg.total) * 100) : 0));
const low = computed(() => props.pkg.status === 'active' && props.pkg.remaining <= 2);
</script>

<template>
    <div class="rounded-xl border p-4" :class="low ? 'border-amber-200 bg-amber-50/60' : 'border-line bg-white'">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="truncate font-semibold text-ink">{{ pkg.name }}</p>
                <p class="text-xs text-muted">
                    <template v-if="pkg.class">{{ pkg.class }} · </template>
                    <template v-if="pkg.expires_at">valid until {{ pkg.expires_at }}</template>
                </p>
            </div>
            <StatusBadge v-if="pkg.status !== 'active'" :status="pkg.status === 'pending_payment' ? 'unpaid' : 'inactive'" :label="pkg.status_label" />
        </div>
        <div class="mt-3 flex items-end justify-between">
            <p class="font-display text-2xl font-extrabold text-ink">{{ pkg.remaining }}<span class="text-sm font-semibold text-muted"> / {{ pkg.total }} sessions left</span></p>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-chip">
            <div class="h-full rounded-full transition-all" :class="low ? 'bg-amber-400' : 'bg-brand-600'" :style="{ width: `${percent}%` }" />
        </div>
        <p v-if="low" class="mt-2 text-xs font-semibold text-amber-800">Almost out of sessions. Renew the package so learning is not interrupted.</p>
    </div>
</template>
