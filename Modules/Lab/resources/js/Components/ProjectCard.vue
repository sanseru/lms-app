<script setup>
import { Link, router } from '@inertiajs/vue3';
import { Eye, Heart, MessageCircle } from 'lucide-vue-next';
import Avatar from '@/Components/ui/Avatar.vue';
import ProjectThumb from './ProjectThumb.vue';

const props = defineProps({
    project: { type: Object, required: true },
    href: { type: String, required: true },
    showAuthor: { type: Boolean, default: true },
});

// Like straight from the card (client prototype: hearts on every project).
const like = () => router.post(route('community.like', props.project.id), {}, { preserveScroll: true, preserveState: true });
</script>

<template>
    <!-- The title link covers the whole card; the like button sits above it. -->
    <article class="card group relative overflow-hidden transition hover:-translate-y-1 hover:shadow-lg has-[a:focus-visible]:ring-2 has-[a:focus-visible]:ring-brand-300">
        <ProjectThumb :language="project.language" compact>
            <template #label>{{ project.language_label }}</template>
            <span v-if="!showAuthor && project.shared" class="absolute top-3 right-3 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-success-ink">In Community</span>
            <span v-else-if="!showAuthor && project.hidden" class="absolute top-3 right-3 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-accent-700">Removed by teacher</span>
        </ProjectThumb>
        <div class="p-4">
            <h3 class="truncate font-display text-base font-bold text-ink group-hover:text-brand-600">
                <Link :href="href" class="after:absolute after:inset-0 focus-visible:outline-none">{{ project.title }}</Link>
            </h3>
            <p v-if="project.description" class="mt-0.5 line-clamp-2 text-sm text-muted">{{ project.description }}</p>
            <div class="mt-3 flex items-center justify-between gap-3 text-xs text-muted">
                <span v-if="showAuthor" class="flex min-w-0 items-center gap-2">
                    <Avatar :initials="project.author.initials" :seed="project.author.id" size="sm" />
                    <span class="truncate font-semibold text-ink">{{ project.author.name }}</span>
                </span>
                <span v-else class="font-semibold">Edited {{ new Date(project.updated_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) }}</span>
                <span class="flex shrink-0 items-center gap-3">
                    <button
                        v-if="project.shared"
                        type="button"
                        :aria-pressed="project.liked"
                        :aria-label="`${project.liked ? 'Unlike' : 'Like'} ${project.title}, ${project.likes_count} likes`"
                        :class="project.liked ? 'text-accent-600' : 'hover:text-accent-600'"
                        class="relative z-10 -m-2 flex items-center gap-1 rounded-lg p-2"
                        @click="like"
                    >
                        <Heart :size="14" :fill="project.liked ? 'currentColor' : 'none'" aria-hidden="true" />{{ project.likes_count }}
                    </button>
                    <span v-else class="flex items-center gap-1"><Heart :size="14" aria-hidden="true" />{{ project.likes_count }}<span class="sr-only"> likes</span></span>
                    <span class="flex items-center gap-1"><Eye :size="14" aria-hidden="true" />{{ project.views_count }}<span class="sr-only"> views</span></span>
                    <span class="flex items-center gap-1"><MessageCircle :size="14" aria-hidden="true" />{{ project.comments_count }}<span class="sr-only"> comments</span></span>
                </span>
            </div>
        </div>
    </article>
</template>
