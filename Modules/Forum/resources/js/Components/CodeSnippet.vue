<script setup>
import { ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

const props = defineProps({ code: { type: String, required: true } });
const copied = ref(false);
const copy = async () => {
    try {
        await navigator.clipboard.writeText(props.code);
        copied.value = true;
        setTimeout(() => (copied.value = false), 1500);
    } catch {
        /* clipboard tidak tersedia */
    }
};
</script>

<template>
    <div class="group relative overflow-hidden rounded-2xl bg-[#241a35]">
        <div class="flex items-center justify-between border-b border-white/10 px-4 py-2 text-xs text-white/60">
            <span class="flex items-center gap-1.5 font-semibold"><Icon name="code" :size="14" /> Kode</span>
            <button type="button" class="rounded-md px-2 py-0.5 font-semibold hover:bg-white/10 hover:text-white" @click="copy">{{ copied ? 'Tersalin ✓' : 'Salin' }}</button>
        </div>
        <pre class="max-h-96 overflow-auto p-4 font-mono text-sm leading-relaxed text-[#f3eefb]"><code>{{ code }}</code></pre>
    </div>
</template>
