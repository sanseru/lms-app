<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import ProgressBar from '@/Components/ui/ProgressBar.vue';
import { useConfirm } from '@/composables/useConfirm';
import { classColors } from '@/lib/format';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

const props = defineProps({ course: Object, lessons: Array, progress: Object, classes: Array, availableClasses: Array, pendingTotal: Number, interests: { type: Array, default: () => [] }, can: Object });

const color = computed(() => classColors[props.course.color] ?? classColors.violet);
const confirm = useConfirm();
const attach = useForm({ class_room_id: '' });
const attachClass = () => attach.post(route('learning.courses.classes.store', props.course.id), { preserveScroll: true, onSuccess: () => attach.reset() });
const move = (lesson, direction) => router.post(route('learning.lessons.move', lesson.id), { direction }, { preserveScroll: true });

const counts = computed(() => ({
    material: props.lessons.filter((l) => l.type === 'material').length,
    code: props.lessons.filter((l) => l.type === 'code').length,
    quiz: props.lessons.filter((l) => l.type === 'quiz').length,
    assignment: props.lessons.filter((l) => l.type === 'assignment').length,
}));
</script>

<template>
    <Head :title="course.title" />

    <Link :href="route('learning.courses.index')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> {{ can.manage ? 'All courses' : 'My course' }}
    </Link>

    <div :class="color.grad" class="relative overflow-hidden rounded-3xl p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -right-4 -bottom-10 font-display text-[9rem] leading-none font-black tracking-tighter text-white/15 select-none">{{ course.subject.slice(0, 2) }}</span>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm font-semibold text-white">{{ course.subject }} · {{ course.level_label }}</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight">{{ course.title }}</h1>
                <p v-if="course.description" class="mt-2 text-white">{{ course.description }}</p>
                <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold text-white">
                    <span>{{ counts.material }} {{ counts.material === 1 ? 'lesson' : 'lessons' }}</span><span>{{ counts.quiz }} {{ counts.quiz === 1 ? 'quiz' : 'quizzes' }}</span><span>{{ counts.assignment }} {{ counts.assignment === 1 ? 'assignment' : 'assignments' }}</span><span v-if="counts.code">{{ counts.code }} coding {{ counts.code === 1 ? 'exercise' : 'exercises' }}</span>
                </p>
            </div>
            <div v-if="can.manage" class="flex flex-wrap gap-2">
                <span v-if="course.status !== 'published'" class="rounded-full bg-white/25 px-3 py-1.5 text-xs font-bold">Draft, not visible to students</span>
                <Link :href="route('learning.courses.edit', course.id)" class="btn bg-white text-ink hover:bg-white/90"><Icon name="pencil" :size="16" /> Edit</Link>
                <button class="btn bg-white/20 text-white hover:bg-white/30" aria-label="Delete course" @click="confirm.ask({ title: 'Delete course?', message: 'All lessons, quizzes and assignments in it will be deleted too. Classes using it will be detached.', url: route('learning.courses.destroy', course.id) })">
                    <Icon name="trash" :size="16" />
                </button>
            </div>
            <div v-else-if="progress" class="w-full max-w-xs rounded-2xl bg-white/15 p-4 backdrop-blur">
                <div class="flex items-baseline justify-between"><span class="text-sm font-semibold">Your progress</span><span class="font-display text-2xl font-extrabold">{{ progress.percent }}%</span></div>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/30"><div class="h-full rounded-full bg-white" :style="{ width: `${progress.percent}%` }" /></div>
                <p class="mt-2 text-xs text-white">{{ progress.completed }} of {{ progress.total }} done<template v-if="progress.quiz_avg !== null"> · quiz average {{ progress.quiz_avg }}</template></p>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <!-- Lesson list -->
        <div class="card overflow-hidden lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="font-display font-bold text-ink">Course content</h2>
                <div v-if="can.manage" class="flex flex-wrap gap-2">
                    <Link :href="route('learning.lessons.create', { course: course.id, type: 'material' })" class="btn-ghost px-3 py-1.5 text-xs"><Icon name="book-open" :size="14" /> Lesson</Link>
                    <Link :href="route('learning.lessons.create', { course: course.id, type: 'quiz' })" class="btn-ghost px-3 py-1.5 text-xs"><Icon name="list-checks" :size="14" /> Quiz</Link>
                    <Link :href="route('learning.lessons.create', { course: course.id, type: 'assignment' })" class="btn-ghost px-3 py-1.5 text-xs"><Icon name="file-pen" :size="14" /> Assignment</Link>
                    <Link :href="route('learning.lessons.create', { course: course.id, type: 'code' })" class="btn-ghost px-3 py-1.5 text-xs"><Icon name="code" :size="14" /> Coding</Link>
                </div>
            </div>

            <ol v-if="lessons.length" class="divide-y divide-line">
                <li v-for="(lesson, i) in lessons" :key="lesson.id" class="flex items-center gap-3 px-5 py-3.5 transition hover:bg-chip/40">
                    <span class="w-5 text-center text-xs font-bold text-muted">{{ i + 1 }}</span>
                    <LessonTypeIcon :type="lesson.type" :done="lesson.done" />
                    <Link :href="route('learning.lessons.show', lesson.id)" class="min-w-0 flex-1">
                        <span class="block truncate font-semibold text-ink hover:text-brand-600">{{ lesson.title }}</span>
                        <span class="flex flex-wrap items-center gap-x-3 text-xs text-muted">
                            <span>{{ lesson.type_label }}</span>
                            <span v-if="lesson.duration_minutes">{{ lesson.duration_minutes }} min</span>
                            <span v-if="lesson.type === 'quiz' && lesson.questions_count !== null">{{ lesson.questions_count }} {{ lesson.questions_count === 1 ? 'question' : 'questions' }}</span>
                            <span v-if="lesson.assessment_label" class="font-semibold text-accent-600">{{ lesson.assessment_label }}</span>
                            <span v-if="lesson.quiz_best !== null && lesson.quiz_best !== undefined" class="font-semibold text-ink">Best score {{ lesson.quiz_best }}</span>
                            <span v-if="lesson.submission_label" class="font-semibold" :class="lesson.submission_status === 'revision' ? 'text-accent-600' : 'text-ink'">
                                {{ lesson.submission_label }}<template v-if="lesson.submission_score !== null"> · {{ lesson.submission_score }}/{{ lesson.max_score }}</template>
                            </span>
                        </span>
                    </Link>
                    <Badge v-if="can.manage && !lesson.is_published" tone="gray">Draft</Badge>
                    <Badge v-if="lesson.pending_count" tone="amber">{{ lesson.pending_count }} to grade</Badge>
                    <div v-if="can.manage" class="flex shrink-0 gap-1">
                        <button :disabled="i === 0" class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink disabled:opacity-30" aria-label="Move up" @click="move(lesson, 'up')"><Icon name="arrow-up" :size="15" /></button>
                        <button :disabled="i === lessons.length - 1" class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink disabled:opacity-30" aria-label="Move down" @click="move(lesson, 'down')"><Icon name="arrow-down" :size="15" /></button>
                        <Link :href="route('learning.lessons.edit', lesson.id)" class="rounded-lg p-1.5 text-muted hover:bg-chip hover:text-ink" aria-label="Edit"><Icon name="pencil" :size="15" /></Link>
                    </div>
                </li>
            </ol>
            <EmptyState v-else icon="book-open" title="No content yet" :description="can.manage ? 'Add a reading lesson, quiz or assignment with the buttons above.' : 'Your teacher has not added any lessons yet.'" />
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <Link v-if="progress?.next" :href="route('learning.lessons.show', progress.next.id)" class="card flex items-center gap-3 p-5 transition hover:shadow-lg">
                <LessonTypeIcon :type="progress.next.type" />
                <span class="min-w-0 flex-1">
                    <span class="eyebrow block">Continue learning</span>
                    <span class="block truncate font-semibold text-ink">{{ progress.next.title }}</span>
                </span>
                <Icon name="arrow-right" :size="18" class="text-brand-600" />
            </Link>

            <Link v-if="can.manage && pendingTotal" :href="route('learning.grading.index')" class="card flex items-center gap-3 border-amber-200 bg-amber-50 p-5">
                <span class="grid size-10 place-items-center rounded-xl bg-amber-100 text-amber-700"><Icon name="file-pen" :size="18" /></span>
                <span class="flex-1 text-sm"><b class="text-ink">{{ pendingTotal }} {{ pendingTotal === 1 ? 'assignment' : 'assignments' }}</b> <span class="text-muted">waiting to be graded</span></span>
                <Icon name="arrow-right" :size="18" class="text-amber-700" />
            </Link>

            <div v-if="can.manage" class="card p-5">
                <h2 class="font-display font-bold text-ink">Used in classes</h2>
                <p class="mb-3 text-sm text-muted">Students in these classes can open the course once it is Published.</p>
                <ul class="space-y-2">
                    <li v-for="c in classes" :key="c.id" class="flex items-center gap-3 rounded-xl border border-line p-3">
                        <span :class="(classColors[c.color] ?? classColors.violet).dot" class="size-2.5 rounded-full" />
                        <Link :href="route('classes.show', c.id)" class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ink">{{ c.name }}</span>
                            <span class="block text-xs text-muted">{{ c.teacher?.name ?? 'No teacher yet' }} · {{ c.students_count }} {{ c.students_count === 1 ? 'student' : 'students' }}</span>
                        </Link>
                        <button class="rounded-lg p-1.5 text-muted hover:bg-rose-50 hover:text-rose-600" aria-label="Detach" @click="confirm.ask({ title: 'Detach class?', message: `${c.name} will no longer use this course. Student progress is kept.`, url: route('learning.courses.classes.destroy', [course.id, c.id]), confirmText: 'Detach' })">
                            <Icon name="x" :size="15" />
                        </button>
                    </li>
                    <li v-if="!classes.length" class="rounded-xl bg-chip/60 p-3 text-center text-sm text-muted">Not attached to any class yet.</li>
                </ul>
                <form v-if="availableClasses.length" class="mt-3 flex gap-2" @submit.prevent="attachClass">
                    <select v-model="attach.class_room_id" class="field flex-1 py-2 text-sm" required>
                        <option value="" disabled>Choose a class…</option>
                        <option v-for="c in availableClasses" :key="c.id" :value="c.id">{{ c.name }}{{ c.course_id ? ' (switch course)' : '' }}</option>
                    </select>
                    <button class="btn-primary px-3 py-2 text-sm" aria-label="Attach to class" :disabled="attach.processing || !attach.class_room_id"><Icon name="plus" :size="16" /></button>
                </form>
            </div>

            <!-- Students who tapped "I'm interested" in the course catalog -->
            <div v-if="can.manage && interests.length" class="card p-5">
                <h2 class="font-display font-bold text-ink">Interested students</h2>
                <p class="mb-3 text-sm text-muted">They asked about this course in the catalog. Consider offering a class or a free trial.</p>
                <ul class="space-y-2">
                    <li v-for="i in interests" :key="i.id" class="flex items-center justify-between gap-3 rounded-xl bg-chip/60 px-3 py-2 text-sm">
                        <span class="truncate font-semibold text-ink">{{ i.name }}</span>
                        <span class="shrink-0 text-xs text-muted">{{ i.at }}</span>
                    </li>
                </ul>
            </div>

            <div v-if="!can.manage && !progress" class="card p-5 text-sm text-muted">This is a read-only view. You can see your child's progress under Learning progress.</div>
        </div>
    </div>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
