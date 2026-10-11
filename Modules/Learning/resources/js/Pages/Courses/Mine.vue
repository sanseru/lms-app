<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import CourseCover from '../../Components/CourseCover.vue';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

defineProps({ courses: Array });
</script>

<template>
    <Head title="Materi Saya" />

    <PageHeader eyebrow="Belajar" title="Materi saya" description="Pelajari materi, kerjakan kuis, dan kumpulkan tugas dari kelas yang kamu ikuti." />

    <div v-if="courses.length" class="grid gap-5 lg:grid-cols-2">
        <div v-for="c in courses" :key="c.id" class="card overflow-hidden">
            <CourseCover :course="c" compact />
            <div class="p-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-muted">{{ c.level_label }} · {{ c.total }} materi</p>
                        <Link :href="route('learning.courses.show', c.id)" class="font-display text-lg font-bold text-ink hover:text-brand-600">{{ c.title }}</Link>
                    </div>
                    <p class="shrink-0 font-display text-2xl font-extrabold text-ink">{{ c.percent }}<span class="text-sm text-muted">%</span></p>
                </div>
                <ProgressBar :value="c.percent" class="mt-3" />
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted">
                    <span>{{ c.completed }}/{{ c.total }} selesai</span>
                    <span v-if="c.quiz_avg !== null">Rata-rata kuis {{ c.quiz_avg }}</span>
                    <span v-if="c.pending_assignments" class="font-semibold text-accent-600">{{ c.pending_assignments }} tugas belum dikumpulkan</span>
                </div>

                <Link v-if="c.next" :href="route('learning.lessons.show', c.next.id)" class="mt-4 flex items-center gap-3 rounded-2xl bg-brand-50 p-3 transition hover:bg-brand-100">
                    <LessonTypeIcon :type="c.next.type" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-semibold text-brand-600">Lanjutkan · {{ c.next.type_label }}</span>
                        <span class="block truncate font-semibold text-ink">{{ c.next.title }}</span>
                    </span>
                    <Icon name="arrow-right" :size="18" class="text-brand-600" />
                </Link>
                <p v-else class="mt-4 flex items-center gap-2 rounded-2xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-700"><Icon name="trophy" :size="18" /> Semua materi selesai. Keren!</p>
            </div>
        </div>
    </div>

    <div v-else class="card">
        <EmptyState icon="book-open" title="Belum ada materi" description="Materi akan muncul setelah gurumu memasang kursus di kelasmu." />
    </div>
</template>
