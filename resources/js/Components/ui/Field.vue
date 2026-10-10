<script setup>
defineProps({
    label: { type: String, default: null },
    error: { type: String, default: null },
    hint: { type: String, default: null },
    required: { type: Boolean, default: false },
    id: { type: String, default: null },
});
</script>

<template>
    <div>
        <label v-if="label" :for="id" class="label">
            {{ label }} <span v-if="required" class="text-accent-600">*</span>
        </label>
        <slot />
        <p v-if="error" class="mt-1.5 text-sm font-medium text-rose-600">{{ error }}</p>
        <p v-else-if="hint" class="mt-1.5 text-xs text-muted">{{ hint }}</p>
    </div>
</template>
