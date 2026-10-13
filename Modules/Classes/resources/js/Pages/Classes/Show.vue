<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import EmptyState from '@/Components/ui/EmptyState.vue';
import Icon from '@/Components/ui/Icon.vue';
import SessionItem from '@/Components/ui/SessionItem.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import { useConfirm } from '@/composables/useConfirm';
import { classColors, gradeLabel } from '@/lib/format';
import CheckList from '@/Components/ui/CheckList.vue';

const props = defineProps({
    classRoom: Object,
    sessions: Array,
    students: Array,
    availableStudents: Array,
    can: Object,
});

const color = computed(() => classColors[props.classRoom.color] ?? classColors.violet);
const confirm = useConfirm();
const enrolling = ref(false);
const enrollForm = useForm({ student_ids: [] });
const copied = ref(false);

const enroll = () =>
    enrollForm.post(route('classes.students.store', props.classRoom.id), {
        preserveScroll: true,
        onSuccess: () => {
            enrolling.value = false;
            enrollForm.reset();
        },
    });

const copyZoom = async () => {
    const text = [props.classRoom.zoom_url, props.classRoom.zoom_meeting_id && `Meeting ID: ${props.classRoom.zoom_meeting_id}`, props.classRoom.zoom_passcode && `Passcode: ${props.classRoom.zoom_passcode}`]
        .filter(Boolean)
        .join('\n');
    await navigator.clipboard?.writeText(text);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
};
</script>

<template>
    <Head :title="classRoom.name" />

    <Link :href="route('classes.index')" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> All classes
    </Link>

    <!-- Hero -->
    <div :class="color.grad" class="relative overflow-hidden rounded-3xl p-6 text-white sm:p-8">
        <span class="pointer-events-none absolute -right-6 -bottom-14 font-mono text-[11rem] leading-none font-black opacity-15 select-none">{ }</span>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/20 px-3 py-1 font-mono text-xs font-medium">{{ classRoom.code }}</span>
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">{{ classRoom.subject }} · {{ classRoom.level_label }}</span>
                    <span class="rounded-full bg-white px-1 py-0.5"><StatusBadge :status="classRoom.status" :label="classRoom.status_label" /></span>
                </div>
                <h1 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">{{ classRoom.name }}</h1>
                <p class="mt-2 flex items-center gap-2 text-white"><Icon name="clock" :size="16" /> {{ classRoom.schedule }} WIB</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a v-if="classRoom.zoom_url" :href="classRoom.zoom_url" target="_blank" rel="noopener" class="btn bg-white text-ink shadow-[0_4px_0_rgba(0,0,0,0.15)] hover:bg-white/90">
                    <Icon name="video" :size="18" /> Join Zoom
                </a>
                <Link v-if="can.lessonAccess && route().has('learning.access.show')" :href="route('learning.access.show', classRoom.id)" class="btn bg-white/20 text-white hover:bg-white/30"><Icon name="lock" :size="16" /> Lesson access</Link>
                <Link v-if="can.manage" :href="route('classes.edit', classRoom.id)" class="btn bg-white/20 text-white hover:bg-white/30"><Icon name="pencil" :size="16" /> Edit</Link>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <!-- Students & sessions -->
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display font-bold text-ink">Sessions</h2>
                        <p class="text-sm text-muted">The last two weeks and upcoming</p>
                    </div>
                    <button v-if="can.manage" class="btn-ghost px-3 py-2" @click="router.post(route('classes.sessions.generate', classRoom.id), { weeks: 8 }, { preserveScroll: true })">
                        <Icon name="calendar-days" :size="16" /> Create 8 weeks of sessions
                    </button>
                </div>
                <div v-if="sessions.length" class="space-y-2">
                    <SessionItem v-for="s in sessions" :key="s.id" :session="s" :show-teacher="false" show-date />
                </div>
                <p v-else class="text-sm text-muted">No sessions yet. Sessions are created automatically from the weekly schedule.</p>
            </div>

            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <div>
                        <h2 class="font-display font-bold text-ink">Students</h2>
                        <p class="text-sm text-muted">{{ classRoom.students_count }}<template v-if="classRoom.capacity"> of {{ classRoom.capacity }}</template> students enrolled</p>
                    </div>
                    <button v-if="can.manage" class="btn-primary" @click="enrolling = true"><Icon name="user-plus" :size="16" /> Add students</button>
                </div>

                <ul v-if="can.roster && students.length" class="divide-y divide-line">
                    <li v-for="student in students" :key="student.id" class="flex items-center gap-3 px-5 py-3">
                        <Avatar :initials="student.initials" :seed="student.id" size="sm" />
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ink">{{ student.name }}</p>
                            <p class="font-mono text-xs text-muted">{{ student.code }}<template v-if="student.grade"> · {{ gradeLabel(student.grade) }}</template></p>
                        </div>
                        <p v-if="student.joined_at" class="hidden text-xs text-muted sm:block">Since {{ student.joined_at }}</p>
                        <Link v-if="can.progress" :href="route('learning.progress.show', student.id)" class="inline-flex items-center gap-1 rounded-lg px-2 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-50" :aria-label="`Learning progress of ${student.name}`">
                            <Icon name="target" :size="14" /> Progress
                        </Link>
                        <button
                            v-if="can.manage"
                            class="rounded-lg p-2 text-muted hover:bg-rose-50 hover:text-rose-600"
                            title="Remove from class"
                            @click="confirm.ask({ title: `Remove ${student.name}?`, message: 'The student will no longer see this class.', confirmText: 'Yes, remove', url: route('classes.students.destroy', [classRoom.id, student.id]) })"
                        >
                            <Icon name="x" :size="16" />
                        </button>
                    </li>
                </ul>
                <EmptyState v-else-if="can.roster" icon="users" title="No students yet" description="Add students so they see this class on their dashboard." />
                <p v-else class="px-5 py-6 text-sm text-muted">Only teachers and admins can see the student list.</p>
            </div>
        </div>

        <!-- Info -->
        <div class="space-y-6">
            <div v-if="classRoom.teacher" class="card p-5">
                <p class="eyebrow mb-3">Teacher</p>
                <div class="flex items-center gap-3">
                    <Avatar :initials="classRoom.teacher.initials" :seed="classRoom.teacher.name" size="lg" />
                    <div>
                        <p class="font-display font-bold text-ink">{{ classRoom.teacher.name }}</p>
                        <p v-if="classRoom.teacher.expertise" class="text-sm text-muted">{{ classRoom.teacher.expertise }}</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <p class="eyebrow">Zoom</p>
                    <button v-if="classRoom.zoom_url" class="text-xs font-semibold text-brand-600 hover:underline" @click="copyZoom">{{ copied ? 'Copied' : 'Copy details' }}</button>
                </div>
                <dl v-if="classRoom.zoom_url" class="space-y-2 text-sm">
                    <div><dt class="text-muted">Link</dt><dd class="truncate font-medium text-brand-600"><a :href="classRoom.zoom_url" target="_blank" rel="noopener">{{ classRoom.zoom_url }}</a></dd></div>
                    <div v-if="classRoom.zoom_meeting_id"><dt class="text-muted">Meeting ID</dt><dd class="font-mono text-ink">{{ classRoom.zoom_meeting_id }}</dd></div>
                    <div v-if="classRoom.zoom_passcode"><dt class="text-muted">Passcode</dt><dd class="font-mono text-ink">{{ classRoom.zoom_passcode }}</dd></div>
                </dl>
                <p v-else class="text-sm text-muted">No Zoom link set yet.</p>
            </div>

            <div class="card p-5">
                <p class="eyebrow mb-3">Detail</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-muted">Period</dt><dd class="text-right font-medium text-ink">{{ classRoom.start_date ?? '–' }} to {{ classRoom.end_date ?? 'ongoing' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-muted">Capacity</dt><dd class="font-medium text-ink">{{ classRoom.capacity ?? 'No limit' }}</dd></div>
                </dl>
                <p v-if="classRoom.description" class="mt-4 border-t border-line pt-4 text-sm leading-relaxed text-ink/80">{{ classRoom.description }}</p>
            </div>
        </div>
    </div>

    <Modal :show="enrolling" max-width="lg" @close="enrolling = false">
        <form class="p-6" @submit.prevent="enroll">
            <h3 class="font-display text-lg font-bold text-ink">Add students to the class</h3>
            <p class="mb-4 text-sm text-muted">Pick active students who are not in this class yet.</p>
            <CheckList v-model="enrollForm.student_ids" :options="availableStudents" empty="All active students are already enrolled." />
            <p v-if="enrollForm.errors.student_ids" class="mt-2 text-sm text-rose-600">{{ enrollForm.errors.student_ids }}</p>
            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="btn-ghost" @click="enrolling = false">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="enrollForm.processing || !enrollForm.student_ids.length">Add {{ enrollForm.student_ids.length || '' }}</button>
            </div>
        </form>
    </Modal>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
