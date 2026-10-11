<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ meta: { type: Object, required: true } });
</script>

<template>
    <div v-if="meta.last_page > 1" class="flex flex-col items-center justify-between gap-3 border-t border-line px-5 py-4 sm:flex-row">
        <p class="text-sm text-muted">
            Showing <b class="text-ink">{{ meta.from }}–{{ meta.to }}</b> of <b class="text-ink">{{ meta.total }}</b>
        </p>
        <nav class="flex flex-wrap gap-1">
            <template v-for="(link, i) in meta.links" :key="i">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    preserve-scroll
                    preserve-state
                    class="min-w-9 rounded-lg px-3 py-1.5 text-center text-sm font-semibold transition"
                    :class="link.active ? 'bg-brand-600 text-white' : 'text-muted hover:bg-chip hover:text-ink'"
                    v-html="link.label"
                />
                <span v-else class="min-w-9 px-3 py-1.5 text-center text-sm text-slate-300" v-html="link.label" />
            </template>
        </nav>
    </div>
</template>
