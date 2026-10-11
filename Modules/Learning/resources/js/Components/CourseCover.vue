<script setup>
import { computed } from 'vue';
import { classColors } from '@/lib/format';

const props = defineProps({ course: Object, compact: Boolean });
const color = computed(() => classColors[props.course.color] ?? classColors.violet);
const glyph = computed(() => (props.course.subject || props.course.title || '?').slice(0, 2));
</script>

<template>
    <div :class="[color.grad, compact ? 'h-20' : 'h-28']" class="relative overflow-hidden bg-gradient-to-br">
        <span class="absolute -right-3 -bottom-6 font-display text-[5.5rem] leading-none font-black tracking-tighter text-white/20 select-none">{{ glyph }}</span>
        <span class="absolute top-3 left-4 rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold text-white backdrop-blur">{{ course.subject }}</span>
        <slot />
    </div>
</template>
