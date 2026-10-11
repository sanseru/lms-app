<script setup>
import { Link } from '@inertiajs/vue3';
import { Eye, Heart, MessageCircle } from 'lucide-vue-next';
import Avatar from '@/Components/ui/Avatar.vue';
import ProjectThumb from './ProjectThumb.vue';

defineProps({
    project: { type: Object, required: true },
    href: { type: String, required: true },
    showAuthor: { type: Boolean, default: true },
});
</script>

<template>
    <Link :href="href" class="card group block overflow-hidden transition hover:-translate-y-1 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-brand-300 focus-visible:outline-none">
        <ProjectThumb :language="project.language" compact>
            <template #label>{{ project.language_label }}</template>
            <span v-if="!showAuthor && project.shared" class="absolute top-3 right-3 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-success-ink">In Community</span>
            <span v-else-if="!showAuthor && project.hidden" class="absolute top-3 right-3 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-accent-700">Removed by teacher</span>
        </ProjectThumb>
        <div class="p-4">
            <h3 class="truncate font-display text-base font-bold text-ink group-hover:text-brand-600">{{ project.title }}</h3>
            <p v-if="project.description" class="mt-0.5 line-clamp-2 text-sm text-muted">{{ project.description }}</p>
            <div class="mt-3 flex items-center justify-between gap-3 text-xs text-muted">
                <span v-if="showAuthor" class="flex min-w-0 items-center gap-2">
                    <Avatar :initials="project.author.initials" :seed="project.author.id" size="sm" />
                    <span class="truncate font-semibold text-ink">{{ project.author.name }}</span>
                </span>
                <span v-else class="font-semibold">Edited {{ new Date(project.updated_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}</span>
                <span class="flex shrink-0 items-center gap-3">
                    <span class="flex items-center gap-1" :class="project.liked ? 'text-accent-600' : ''"><Heart :size="14" :fill="project.liked ? 'currentColor' : 'none'" aria-hidden="true" />{{ project.likes_count }}<span class="sr-only"> likes</span></span>
                    <span class="flex items-center gap-1"><Eye :size="14" aria-hidden="true" />{{ project.views_count }}<span class="sr-only"> views</span></span>
                    <span class="flex items-center gap-1"><MessageCircle :size="14" aria-hidden="true" />{{ project.comments_count }}<span class="sr-only"> comments</span></span>
                </span>
            </div>
        </div>
    </Link>
</template>
