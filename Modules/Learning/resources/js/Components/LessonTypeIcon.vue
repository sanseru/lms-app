<script setup>
import Icon from '@/Components/ui/Icon.vue';

defineProps({ type: String, done: Boolean, size: { type: String, default: 'md' } });

const styles = {
    material: 'bg-sky-50 text-sky-600 ring-sky-100',
    quiz: 'bg-amber-50 text-amber-600 ring-amber-100',
    assignment: 'bg-violet-50 text-violet-600 ring-violet-100',
};
const icons = { material: 'book-open', quiz: 'list-checks', assignment: 'file-pen' };
</script>

<template>
    <span
        :class="[done ? 'bg-emerald-500 text-white ring-emerald-500' : styles[type], size === 'sm' ? 'size-8 rounded-lg' : 'size-10 rounded-xl']"
        class="grid shrink-0 place-items-center ring-1 ring-inset"
    >
        <Icon :name="done ? 'check' : icons[type]" :size="size === 'sm' ? 15 : 18" />
    </span>
</template>
