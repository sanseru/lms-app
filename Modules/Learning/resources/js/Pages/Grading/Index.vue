<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { classColors } from '@/lib/format';
import GradeForm from '../../Components/GradeForm.vue';
import SubmissionContent from '../../Components/SubmissionContent.vue';

defineProps({ submissions: Object, tab: String, counts: Object });

const tabs = { submitted: 'To grade', revision: 'Awaiting revision', graded: 'Graded' };
const tone = { submitted: 'amber', revision: 'red', graded: 'green' };
const open = ref(null);
</script>

<template>
    <Head title="Grading" />

    <PageHeader eyebrow="Learning" title="Grading" description="Assignments from students in your classes. Give a grade or ask for a revision with feedback." />

    <div class="mb-5 flex flex-wrap gap-2">
        <Link
            v-for="(label, key) in tabs"
            :key="key"
            :href="route('learning.grading.index', { tab: key })"
            :class="tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition"
            preserve-scroll
        >
            {{ label }}
            <span :class="tab === key ? 'bg-white/25' : 'bg-chip'" class="rounded-full px-2 text-xs">{{ counts[key] ?? 0 }}</span>
        </Link>
    </div>

    <div v-if="submissions.data.length" class="space-y-4">
        <div v-for="s in submissions.data" :key="s.id" class="card p-5">
            <div class="flex flex-wrap items-center gap-3">
                <Avatar :initials="s.student.initials" :seed="s.student.id" />
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-ink">{{ s.student.name }}</p>
                    <p class="flex flex-wrap items-center gap-x-2 text-sm text-muted">
                        <span :class="(classColors[s.course.color] ?? classColors.violet).dot" class="size-2 rounded-full" />
                        {{ s.course.title }} ·
                        <Link :href="route('learning.lessons.show', s.lesson.id)" class="font-semibold text-brand-600 hover:underline">{{ s.lesson.title }}</Link>
                    </p>
                </div>
                <div class="text-right text-xs text-muted">
                    <p>Submitted {{ s.submitted_at }}</p>
                    <p v-if="s.graded_at">Graded {{ s.graded_at }}<template v-if="s.grader"> by {{ s.grader }}</template></p>
                </div>
                <span v-if="s.score !== null" class="font-display text-lg font-bold text-ink">{{ s.score }}/{{ s.lesson.max_score }}</span>
                <Badge :tone="tone[s.status]">{{ s.status_label }}</Badge>
            </div>
            <div class="mt-4 rounded-2xl border border-line p-4">
                <SubmissionContent :submission="s" />
            </div>
            <p v-if="s.feedback && open !== s.id" class="mt-3 text-sm text-muted"><b class="text-ink">Feedback:</b> {{ s.feedback }}</p>
            <div class="mt-3">
                <GradeForm v-if="open === s.id || s.status === 'submitted'" :submission="s" :max-score="s.lesson.max_score" @done="open = null" />
                <button v-else class="btn-ghost py-2 text-sm" @click="open = s.id"><Icon name="pencil" :size="15" /> Edit grade</button>
            </div>
        </div>
        <div v-if="submissions.last_page > 1" class="card overflow-hidden"><Pagination :meta="submissions" /></div>
    </div>

    <div v-else class="card">
        <EmptyState icon="badge-check" :title="tab === 'submitted' ? 'All assignments are graded' : 'Nothing here yet'" :description="tab === 'submitted' ? 'New assignments from students will show up here.' : null" />
    </div>
</template>
