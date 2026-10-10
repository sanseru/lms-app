<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-line bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-ink shadow-sm transition duration-150 ease-in-out hover:bg-chip focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
