<script setup>
import { ref } from 'vue';
import Icon from '@/Components/ui/Icon.vue';
import ScratchFrame from '../../../../Lab/resources/js/Components/ScratchFrame.vue';

defineProps({ submission: Object });

// A handed-in Scratch project plays right here (green flag), no download needed.
const playing = ref(false);
</script>

<template>
    <div class="space-y-2 text-sm">
        <p v-if="submission.body" class="whitespace-pre-line text-ink">{{ submission.body }}</p>
        <div class="flex flex-wrap gap-2">
            <button v-if="submission.is_scratch" type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-semibold text-brand-600 ring-1 ring-line hover:ring-brand-200" :aria-expanded="playing" @click="playing = !playing">
                <Icon :name="playing ? 'x' : 'play'" :size="14" /> {{ playing ? 'Close project' : 'Play Scratch project' }}
            </button>
            <a v-if="submission.link_url" :href="submission.link_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-semibold text-brand-600 ring-1 ring-line hover:ring-brand-200">
                <Icon name="link" :size="14" /> Open link
            </a>
            <a v-if="submission.file_url" :href="submission.file_url" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-semibold text-brand-600 ring-1 ring-line hover:ring-brand-200">
                <Icon name="paperclip" :size="14" /> {{ submission.file_name }}
            </a>
        </div>
        <ScratchFrame v-if="playing" :src="submission.file_url" mode="player" title="Scratch project" class="aspect-[4/3.3] max-h-[70vh] w-full max-w-2xl" />
    </div>
</template>
