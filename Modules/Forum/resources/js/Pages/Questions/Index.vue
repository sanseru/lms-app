<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import SearchInput from '@/Components/ui/SearchInput.vue';
import QuestionCard from '../../Components/QuestionCard.vue';

const props = defineProps({ questions: Object, filters: Object, lesson: Object, counts: Object, classes: Array, canAsk: Boolean, isStaff: Boolean });

const tabs = { all: 'All', unanswered: 'Unanswered', answered: 'Has answers', resolved: 'Resolved', mine: 'My questions' };
const search = ref(props.filters.q ?? '');
const classId = ref(props.filters.class ?? '');

const go = (extra = {}) => router.get(route('forum.questions.index'), {
    tab: props.filters.tab, class: classId.value || undefined, q: search.value || undefined, lesson: props.lesson?.id, ...extra,
}, { preserveState: true, preserveScroll: true, replace: true });

let timer;
watch(search, () => { clearTimeout(timer); timer = setTimeout(() => go(), 350); });
watch(classId, () => go());
</script>

<template>
    <Head title="Q&A" />

    <PageHeader
        eyebrow="Learning"
        title="Q&A"
        :description="isStaff ? 'Questions from students in your classes. Answer the unanswered ones first, then pin the ones that come up often.' : 'Stuck? Ask here. Your teacher and classmates are ready to help.'"
    >
        <template #actions>
            <Link v-if="canAsk" :href="route('forum.questions.create', { lesson: lesson?.id })" class="btn-primary"><Icon name="plus" :size="18" /> Ask a question</Link>
        </template>
    </PageHeader>

    <div v-if="lesson" class="mb-4 flex items-center gap-2 rounded-2xl bg-brand-50 px-4 py-3 text-sm text-ink">
        <Icon name="book-open" :size="16" class="text-brand-600" />
        <span class="flex-1">Questions about <b>{{ lesson.title }}</b></span>
        <Link :href="route('learning.lessons.show', lesson.id)" class="font-semibold text-brand-600 hover:underline">Open lesson</Link>
        <button class="rounded-lg p-1 text-muted hover:bg-white hover:text-ink" title="Clear filter" @click="go({ lesson: undefined })"><Icon name="x" :size="16" /></button>
    </div>

    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-wrap gap-2">
            <button
                v-for="(label, key) in tabs"
                :key="key"
                :class="filters.tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
                class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
                @click="go({ tab: key, page: undefined })"
            >
                {{ label }}
                <span :class="filters.tab === key ? 'bg-white/25' : 'bg-chip'" class="rounded-full px-2 text-xs">{{ counts[key] }}</span>
            </button>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row">
            <select v-if="classes.length > 1" v-model="classId" class="field sm:w-52">
                <option value="">All classes</option>
                <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <SearchInput v-model="search" placeholder="Search questions…" />
        </div>
    </div>

    <div v-if="questions.data.length" class="space-y-3">
        <QuestionCard v-for="q in questions.data" :key="q.id" :question="q" />
        <div v-if="questions.last_page > 1" class="card overflow-hidden"><Pagination :meta="questions" /></div>
    </div>

    <div v-else class="card">
        <EmptyState
            icon="message-question"
            :title="filters.tab === 'unanswered' ? 'Every question has been answered' : 'No questions yet'"
            :description="canAsk && filters.tab !== 'unanswered' ? 'There are no silly questions. Ask about anything that confuses you!' : null"
        />
        <div v-if="canAsk && filters.tab !== 'unanswered'" class="pb-8 text-center">
            <Link :href="route('forum.questions.create', { lesson: lesson?.id })" class="btn-primary"><Icon name="plus" :size="18" /> Ask now</Link>
        </div>
    </div>
</template>
