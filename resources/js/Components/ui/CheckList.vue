<script setup>
import { computed, ref } from 'vue';
import SearchInput from './SearchInput.vue';

/** Searchable checkbox list for many-to-many relations. */
const model = defineModel({ type: Array, default: () => [] });
const props = defineProps({
    options: { type: Array, required: true },
    empty: { type: String, default: 'Nothing here yet.' },
    subtitle: { type: Function, default: () => null },
});

const search = ref('');
const filtered = computed(() => props.options.filter((o) => o.name.toLowerCase().includes(search.value.toLowerCase())));
</script>

<template>
    <div class="rounded-xl border border-line">
        <div class="border-b border-line p-3">
            <SearchInput v-model="search" placeholder="Search by name…" class="!max-w-none" />
        </div>
        <ul class="max-h-64 divide-y divide-line overflow-y-auto">
            <li v-for="option in filtered" :key="option.id">
                <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-chip">
                    <input v-model="model" type="checkbox" :value="option.id" class="rounded border-line text-brand-600 focus:ring-brand-300" />
                    <span class="text-sm font-medium text-ink">{{ option.name }}</span>
                    <span v-if="subtitle(option)" class="ml-auto font-mono text-xs text-muted">{{ subtitle(option) }}</span>
                </label>
            </li>
            <li v-if="!filtered.length" class="px-4 py-6 text-center text-sm text-muted">{{ empty }}</li>
        </ul>
        <p class="border-t border-line px-4 py-2 text-xs text-muted">{{ model.length }} selected</p>
    </div>
</template>
