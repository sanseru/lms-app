<script setup>
defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, default: null },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <slot name="back" />
            <p v-if="eyebrow" class="eyebrow mb-1">{{ eyebrow }}</p>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ title }}</h1>
            <p v-if="description" class="mt-1 max-w-2xl text-sm text-muted">{{ description }}</p>
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
