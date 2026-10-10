<script setup>
import Icon from './Icon.vue';

const model = defineModel({ type: String, default: '' });
defineProps({ placeholder: { type: String, default: 'Cari…' } });
</script>

<template>
    <label class="relative block w-full sm:max-w-xs">
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-muted">
            <Icon name="search" :size="16" />
        </span>
        <input v-model="model" type="search" :placeholder="placeholder" class="field pl-9" />
    </label>
</template>
