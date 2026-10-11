<script setup>
import { ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';

/** Susun baris kode yang diacak. Bisa diseret, atau pakai tombol naik/turun (ramah keyboard & layar sentuh). */
const model = defineModel({ type: Array, required: true });
defineProps({ results: { type: Array, default: null }, disabled: Boolean });

const dragging = ref(null);
const move = (from, to) => {
    if (to < 0 || to >= model.value.length) return;
    const list = [...model.value];
    const [item] = list.splice(from, 1);
    list.splice(to, 0, item);
    model.value = list;
};
const onDrop = (to) => {
    if (dragging.value !== null) move(dragging.value, to);
    dragging.value = null;
};
</script>

<template>
    <ol class="space-y-2">
        <li
            v-for="(line, i) in model"
            :key="line + i"
            :draggable="!disabled"
            :class="[
                results ? (results[i]?.passed ? 'border-emerald-300 bg-emerald-50' : 'border-rose-300 bg-rose-50') : 'border-line bg-white hover:border-brand-300',
                dragging === i && 'opacity-40',
            ]"
            class="flex cursor-grab items-center gap-2 rounded-xl border px-3 py-2 shadow-sm transition active:cursor-grabbing"
            @dragstart="dragging = i"
            @dragover.prevent
            @drop="onDrop(i)"
            @dragend="dragging = null"
        >
            <span class="w-5 text-right text-xs font-bold text-muted">{{ i + 1 }}</span>
            <code class="flex-1 font-mono text-sm whitespace-pre text-ink">{{ line }}</code>
            <button type="button" :disabled="disabled || i === 0" class="rounded-lg p-1 text-muted hover:bg-chip disabled:opacity-30" :aria-label="`Naikkan baris ${i + 1}`" @click="move(i, i - 1)"><Icon name="arrow-up" :size="15" /></button>
            <button type="button" :disabled="disabled || i === model.length - 1" class="rounded-lg p-1 text-muted hover:bg-chip disabled:opacity-30" :aria-label="`Turunkan baris ${i + 1}`" @click="move(i, i + 1)"><Icon name="arrow-down" :size="15" /></button>
        </li>
    </ol>
</template>
