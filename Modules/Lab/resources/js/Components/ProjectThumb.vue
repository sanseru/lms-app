<script setup>
import { Blocks, Braces, Code, Globe, Presentation } from 'lucide-vue-next';

/** Thumbnail per project type: a solid color (no gradients) with a big mono glyph. */
defineProps({ language: String, title: String, compact: Boolean });

const looks = {
    python: { bg: 'bg-sky-700', glyph: 'py', icon: Code },
    javascript: { bg: 'bg-amber-300 text-amber-950', glyph: 'js', icon: Braces },
    html: { bg: 'bg-emerald-700', glyph: '</>', icon: Globe },
    scratch: { bg: 'bg-amber-400 text-amber-950', glyph: 'sb3', icon: Blocks },
    presentation: { bg: 'bg-brand-600', glyph: '▶', icon: Presentation },
};
</script>

<template>
    <div :class="[(looks[language] ?? looks.python).bg, compact ? 'h-24' : 'h-32']" class="relative overflow-hidden text-white">
        <span class="absolute -right-2 -bottom-5 font-mono text-[5rem] leading-none font-bold tracking-tighter opacity-25 select-none" aria-hidden="true">{{ (looks[language] ?? looks.python).glyph }}</span>
        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-black/20 px-2.5 py-1 text-xs font-bold">
            <component :is="(looks[language] ?? looks.python).icon" :size="13" aria-hidden="true" />
            <slot name="label" />
        </span>
        <slot />
    </div>
</template>
