<script setup>
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Icon from './Icon.vue';

/**
 * "Stuck? Ask your teacher" card on the lesson page. Only shown when the Q&A
 * module is enabled; its data is fetched separately so Learning does not depend on it.
 */
const props = defineProps({ lessonId: { type: Number, required: true } });
const enabled = route().has('forum.lessons.summary');
const data = ref(null);

onMounted(async () => {
    if (!enabled) return;
    try {
        const res = await fetch(route('forum.lessons.summary', props.lessonId), { headers: { Accept: 'application/json' } });
        if (res.ok) data.value = await res.json();
    } catch {
        /* keep the card hidden */
    }
});
</script>

<template>
    <div v-if="data && (data.can_ask || data.count)" class="overflow-hidden rounded-2xl border border-brand-100 bg-brand-50">
        <div class="flex flex-wrap items-center gap-4 p-5">
            <span class="grid size-12 place-items-center rounded-2xl bg-white text-brand-600 shadow-sm"><Icon name="message-question" :size="22" /></span>
            <div class="min-w-0 flex-1">
                <p class="font-display font-bold text-ink">{{ data.can_ask ? 'Stuck? Ask your teacher' : 'Q&A for this lesson' }}</p>
                <p class="text-sm text-muted">
                    <template v-if="data.count">{{ data.count }} {{ data.count === 1 ? 'question' : 'questions' }} about this lesson.</template>
                    <template v-else>No questions yet. Be the first to ask!</template>
                </p>
            </div>
            <Link v-if="data.count" :href="route('forum.questions.index', { lesson: lessonId })" class="btn-ghost bg-white">View</Link>
            <Link v-if="data.can_ask" :href="route('forum.questions.create', { lesson: lessonId })" class="btn-primary"><Icon name="plus" :size="16" /> Ask</Link>
        </div>
        <ul v-if="data.latest.length" class="divide-y divide-brand-100 border-t border-brand-100 bg-white/70">
            <li v-for="q in data.latest" :key="q.id">
                <Link :href="route('forum.questions.show', q.id)" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-white">
                    <Icon :name="q.status === 'resolved' ? 'circle-check' : 'message-circle'" :size="16" :class="q.status === 'resolved' ? 'text-emerald-500' : 'text-muted'" />
                    <span class="min-w-0 flex-1 truncate font-semibold text-ink">{{ q.title }}</span>
                    <span class="shrink-0 text-xs text-muted">{{ q.answers_count }} {{ q.answers_count === 1 ? 'answer' : 'answers' }}</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
