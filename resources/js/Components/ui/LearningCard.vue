<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { classColors } from '@/lib/format';
import Icon from './Icon.vue';
import ProgressBar from './ProgressBar.vue';

/** Kartu progres kursus untuk dashboard siswa dan orang tua. */
const props = defineProps({ course: Object, linkNext: { type: Boolean, default: true } });
const color = computed(() => classColors[props.course.color] ?? classColors.violet);
const icons = { material: 'book-open', quiz: 'list-checks', assignment: 'file-pen' };
</script>

<template>
    <div class="card p-4">
        <div class="flex items-center gap-3">
            <span :class="color.grad" class="grid size-11 shrink-0 place-items-center rounded-xl bg-gradient-to-br font-display text-sm font-black text-white">{{ course.subject.slice(0, 2) }}</span>
            <div class="min-w-0 flex-1">
                <Link :href="route('learning.courses.show', course.id)" class="block truncate font-semibold text-ink hover:text-brand-600">{{ course.title }}</Link>
                <div class="mt-1.5 flex items-center gap-2">
                    <ProgressBar :value="course.percent" size="sm" />
                    <span class="shrink-0 text-xs font-bold text-ink">{{ course.percent }}%</span>
                </div>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted">
            <span>{{ course.completed }}/{{ course.total }} selesai</span>
            <span v-if="course.quiz_avg !== null">Kuis rata-rata {{ course.quiz_avg }}</span>
            <span v-if="course.pending_assignments" class="font-semibold text-accent-600">{{ course.pending_assignments }} tugas belum dikumpulkan</span>
        </div>
        <Link v-if="linkNext && course.next" :href="route('learning.lessons.show', course.next.id)" class="mt-3 flex items-center gap-2 rounded-xl bg-brand-50 px-3 py-2 text-sm transition hover:bg-brand-100">
            <Icon :name="icons[course.next.type]" :size="15" class="text-brand-600" />
            <span class="min-w-0 flex-1 truncate"><b class="text-brand-700">{{ course.next.type_label }}:</b> <span class="text-ink">{{ course.next.title }}</span></span>
            <Icon name="arrow-right" :size="15" class="text-brand-600" />
        </Link>
        <p v-else-if="!course.next && course.total" class="mt-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">Semua materi selesai 🎉</p>
    </div>
</template>
