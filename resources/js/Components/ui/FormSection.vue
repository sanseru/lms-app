<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <section class="grid gap-6 border-b border-line py-8 first:pt-0 last:border-0 lg:grid-cols-3">
        <div>
            <h2 class="font-display text-base font-bold text-ink">{{ title }}</h2>
            <p v-if="description" class="mt-1 text-sm text-muted">{{ description }}</p>
        </div>
        <div class="grid gap-5 sm:grid-cols-2 lg:col-span-2">
            <slot />
        </div>
    </section>
</template>
