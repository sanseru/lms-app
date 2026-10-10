<script setup>
import Icon from './Icon.vue';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    icon: { type: String, default: 'inbox' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
        <span class="mb-4 grid size-14 place-items-center rounded-2xl bg-brand-50 text-brand-500">
            <Icon :name="icon" :size="26" />
        </span>
        <h3 class="font-display text-base font-bold text-ink">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-5"><slot /></div>
    </div>
</template>
