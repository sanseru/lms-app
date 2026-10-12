<script setup>
import { computed } from 'vue';

// Labels from the server mark code with backticks, e.g. "Uses `for`".
// Odd segments after splitting on ` are code; everything is rendered as text (no v-html).
const props = defineProps({ text: { type: String, default: '' } });
const parts = computed(() => props.text.split('`').map((value, i) => ({ value, code: i % 2 === 1 })));
</script>

<template>
    <span><template v-for="(p, i) in parts" :key="i"><code v-if="p.code" class="rounded bg-brand-50 px-1.5 py-0.5 font-mono text-[0.9em] text-brand-700">{{ p.value }}</code><template v-else>{{ p.value }}</template></template></span>
</template>
