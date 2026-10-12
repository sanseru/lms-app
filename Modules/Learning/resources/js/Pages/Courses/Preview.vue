<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '@/Components/ui/Icon.vue';
import CourseCover from '../../Components/CourseCover.vue';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

const props = defineProps({
    course: Object,
    lessons: Array,
    counts: Object,
    interested: Boolean,
    program: { type: Object, default: null },
    /** Set when this is a locked level of the student's own program: the level to finish first. */
    unlockAfter: { type: String, default: null },
});

const form = useForm({});
const interest = () => form.post(route('learning.courses.interest', props.course.id), { preserveScroll: true });

const labels = { material: ['lesson', 'lessons'], quiz: ['quiz', 'quizzes'], assignment: ['assignment', 'assignments'], code: ['coding exercise', 'coding exercises'] };
const summary = computed(() =>
    Object.entries(props.counts ?? {}).map(([type, n]) => `${n} ${(labels[type] ?? [type, type])[n === 1 ? 0 : 1]}`),
);
</script>

<template>
    <Head :title="course.title" />

    <Link :href="route('learning.courses.explore')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> All courses
    </Link>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,20rem)] lg:items-start">
        <div class="min-w-0 space-y-6">
            <section class="card overflow-hidden">
                <CourseCover :course="course" />
                <div class="p-6">
                    <p class="eyebrow">{{ program ? `${program.name} · ` : '' }}{{ course.subject }} · {{ course.level_label }}</p>
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ course.title }}</h1>
                    <p v-if="course.description" class="mt-2 text-muted">{{ course.description }}</p>
                    <p v-if="summary.length" class="mt-4 flex flex-wrap gap-2 text-sm font-semibold text-ink">
                        <span v-for="s in summary" :key="s" class="rounded-full bg-chip px-3 py-1">{{ s }}</span>
                        <span v-if="course.minutes" class="rounded-full bg-chip px-3 py-1">about {{ course.minutes >= 60 ? `${Math.round(course.minutes / 60)} hours` : `${course.minutes} minutes` }}</span>
                    </p>
                </div>
            </section>

            <section class="card overflow-hidden" aria-labelledby="outline-title">
                <div class="border-b border-line px-5 py-4">
                    <h2 id="outline-title" class="font-display font-bold text-ink">What you will learn</h2>
                    <p class="text-sm text-muted">{{ unlockAfter ? 'Lessons open when this level unlocks.' : 'Lessons open once you join a class that uses this course.' }}</p>
                </div>
                <ol v-if="lessons.length" class="divide-y divide-line">
                    <li v-for="(l, i) in lessons" :key="l.id" class="flex items-center gap-3 px-5 py-3">
                        <span class="w-6 text-right text-xs font-bold text-muted">{{ i + 1 }}</span>
                        <LessonTypeIcon :type="l.type" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-ink">{{ l.title }}</p>
                            <p class="text-xs text-muted">{{ l.assessment_label ?? l.type_label }}<template v-if="l.duration_minutes"> · {{ l.duration_minutes }} min</template></p>
                        </div>
                        <Icon name="lock" :size="15" class="text-muted" />
                    </li>
                </ol>
                <p v-else class="px-5 py-8 text-center text-sm text-muted">Lessons for this course are still being written.</p>
            </section>
        </div>

        <aside class="lg:sticky lg:top-24">
            <section class="card p-6">
                <template v-if="unlockAfter">
                    <span class="grid size-12 place-items-center rounded-2xl bg-chip text-ink"><Icon name="lock" :size="24" /></span>
                    <h2 class="mt-3 font-display text-lg font-extrabold text-ink">Next level, almost there</h2>
                    <p class="mt-1 text-sm text-muted">This level opens by itself when you finish every lesson of <b class="text-ink">{{ unlockAfter }}</b>. No need to change class.</p>
                    <Link :href="route('learning.courses.index')" class="btn-accent mt-5 w-full">Back to my course</Link>
                </template>
                <template v-else-if="interested">
                    <span class="grid size-12 place-items-center rounded-2xl bg-success-soft text-success-ink"><Icon name="check" :size="24" /></span>
                    <h2 class="mt-3 font-display text-lg font-extrabold text-ink">We got your message</h2>
                    <p class="mt-1 text-sm text-muted">Your teacher and parents know you are interested in this course. They will talk with you about joining a class.</p>
                </template>
                <template v-else>
                    <span class="grid size-12 place-items-center rounded-2xl bg-accent-50 text-accent-700"><Icon name="sparkles" :size="24" /></span>
                    <h2 class="mt-3 font-display text-lg font-extrabold text-ink">Want to learn this?</h2>
                    <p class="mt-1 text-sm text-muted">Tell us! We will let your teacher and parents know, so they can help you join a class.</p>
                    <button type="button" class="btn-accent mt-5 w-full" :disabled="form.processing" @click="interest">
                        <Icon name="sparkles" :size="16" /> I'm interested
                    </button>
                </template>
            </section>
        </aside>
    </div>
</template>
