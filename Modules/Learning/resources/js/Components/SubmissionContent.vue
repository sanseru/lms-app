<script setup>
import Icon from '@/Components/ui/Icon.vue';

defineProps({ submission: Object });
</script>

<template>
    <div class="space-y-2 text-sm">
        <p v-if="submission.body" class="whitespace-pre-line text-ink">{{ submission.body }}</p>
        <div class="flex flex-wrap gap-2">
            <a v-if="submission.link_url" :href="submission.link_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-semibold text-brand-600 ring-1 ring-line hover:ring-brand-200">
                <Icon name="link" :size="14" /> Open link
            </a>
            <a v-if="submission.file_url" :href="submission.file_url" class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 font-semibold text-brand-600 ring-1 ring-line hover:ring-brand-200">
                <Icon name="paperclip" :size="14" /> {{ submission.file_name }}
            </a>
        </div>
    </div>
</template>
