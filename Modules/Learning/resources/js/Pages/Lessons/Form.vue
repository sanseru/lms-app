<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { useConfirm } from '@/composables/useConfirm';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

const props = defineProps({ course: Object, lesson: Object, defaultType: String, types: Object, extraTypes: Object, assessments: Object, questionTypes: Object });
const l = props.lesson ?? {};

const blankQuestion = () => ({ id: null, prompt: '', type: 'single', points: 1, explanation: '', concept: '', options: [{ text: '', correct: true }, { text: '', correct: false }, { text: '', correct: false }] });

const form = useForm({
    title: l.title ?? '',
    type: l.type ?? props.defaultType,
    body: l.body ?? '',
    video_url: l.video_url ?? '',
    duration_minutes: l.duration_minutes ?? '',
    is_published: l.is_published ?? true,
    pass_score: l.pass_score ?? 70,
    max_attempts: l.max_attempts ?? '',
    time_limit_minutes: l.time_limit_minutes ?? '',
    max_score: l.max_score ?? 100,
    workspace: l.workspace ?? '',
    assessment: l.assessment ?? '',
    questions: l.questions?.length ? JSON.parse(JSON.stringify(l.questions)) : [blankQuestion()],
});

const isQuiz = computed(() => form.type === 'quiz');
const bodyLabel = computed(() => ({ material: 'Lesson content', quiz: 'Quiz instructions', assignment: 'Assignment instructions' })[form.type]);

const toggleCorrect = (q, i) => {
    if (q.type === 'single' || q.type === 'true_false') q.options.forEach((o, j) => (o.correct = i === j));
    else q.options[i].correct = !q.options[i].correct;
};
const isChoice = (type) => type === 'single' || type === 'multiple';
const setType = (q, type) => {
    const from = q.type;
    if (from === type) return;
    // Keep the multiple-choice options aside so they are not lost while the teacher tries another type.
    if (isChoice(from)) q._choices = q.options;
    q.type = type;
    if (type === 'true_false') {
        q.options = [{ text: 'True', correct: true }, { text: 'False', correct: false }];
    } else if (type === 'short') {
        q.options = [{ text: '', correct: true }];
    } else if (!isChoice(from)) {
        q.options = q._choices ?? blankQuestion().options;
    }
    if (type === 'single' && q.options.filter((o) => o.correct).length !== 1) {
        const first = Math.max(0, q.options.findIndex((o) => o.correct));
        q.options.forEach((o, j) => (o.correct = j === first));
    }
};
const moveQuestion = (i, d) => {
    const j = i + d;
    if (j < 0 || j >= form.questions.length) return;
    [form.questions[i], form.questions[j]] = [form.questions[j], form.questions[i]];
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        duration_minutes: data.duration_minutes || null,
        max_attempts: data.max_attempts || null,
        time_limit_minutes: data.time_limit_minutes || null,
        assessment: data.assessment || null,
        questions: data.type === 'quiz' ? data.questions.map(({ _choices, ...q }) => q) : [],
    }));
    props.lesson ? form.put(route('learning.lessons.update', props.lesson.id)) : form.post(route('learning.lessons.store', props.course.id));
};

const confirm = useConfirm();
const err = (key) => form.errors[key];
</script>

<template>
    <Head :title="lesson ? 'Edit ' + types[form.type].toLowerCase() : 'New ' + types[form.type].toLowerCase()" />

    <PageHeader :eyebrow="course.title" :title="lesson ? `Edit ${types[form.type].toLowerCase()}` : `New ${types[form.type].toLowerCase()}`">
        <template #back>
            <Link :href="route('learning.courses.show', course.id)" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600"><Icon name="arrow-left" :size="16" /> {{ course.title }}</Link>
        </template>
        <template v-if="lesson" #actions>
            <button type="button" class="btn-ghost text-rose-600" @click="confirm.ask({ title: 'Delete?', message: 'Student scores and submissions for this item will be deleted too.', url: route('learning.lessons.destroy', lesson.id) })"><Icon name="trash" :size="16" /> Delete</button>
        </template>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-6">
                <div v-if="!lesson" class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button
                        v-for="(label, key) in types"
                        :key="key"
                        type="button"
                        :class="form.type === key ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line hover:border-brand-200'"
                        class="flex items-center gap-3 rounded-2xl border p-3 text-left transition"
                        @click="form.type = key"
                    >
                        <LessonTypeIcon :type="key" size="sm" />
                        <span class="font-semibold text-ink">{{ label }}</span>
                    </button>
                    <Link v-for="(t, key) in extraTypes" :key="key" :href="t.url" class="flex items-center gap-3 rounded-2xl border border-line p-3 text-left transition hover:border-brand-200">
                        <LessonTypeIcon :type="key" size="sm" />
                        <span class="font-semibold text-ink">{{ t.label }}</span>
                    </Link>
                </div>

                <Field id="title" label="Title" :error="err('title')" required>
                    <input id="title" v-model="form.title" type="text" class="field" :placeholder="isQuiz ? 'e.g. Quiz: Variables and data types' : 'e.g. Meet variables'" />
                </Field>

                <Field id="body" :label="bodyLabel" :error="err('body')" hint="Supports Markdown: **bold**, # heading, - list, `code`, and code blocks with ```.">
                    <textarea id="body" v-model="form.body" :rows="form.type === 'material' ? 14 : 5" class="field font-mono text-sm" />
                </Field>

                <Field v-if="form.type === 'material'" id="video_url" label="Video link (YouTube/Vimeo)" :error="err('video_url')">
                    <input id="video_url" v-model="form.video_url" type="url" class="field" placeholder="https://youtu.be/…" />
                </Field>
            </div>

            <!-- Quiz question builder -->
            <div v-if="isQuiz" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold text-ink">Questions ({{ form.questions.length }})</h2>
                    <p v-if="err('questions')" class="text-sm font-medium text-rose-600">{{ err('questions') }}</p>
                </div>

                <div v-for="(q, qi) in form.questions" :key="qi" class="card p-5">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-lg bg-amber-100 text-xs font-bold text-amber-700">{{ qi + 1 }}</span>
                        <div class="flex flex-wrap gap-1">
                            <button v-for="(label, key) in questionTypes" :key="key" type="button" :class="q.type === key ? 'bg-ink text-white' : 'bg-chip text-muted hover:text-ink'" class="rounded-lg px-2.5 py-1 text-xs font-semibold" @click="setType(q, key)">{{ label }}</button>
                        </div>
                        <label class="ml-auto flex items-center gap-1.5 text-xs text-muted">Points <input v-model.number="q.points" type="number" min="1" max="20" class="field w-16 px-2 py-1 text-xs" /></label>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-chip disabled:opacity-30" :disabled="qi === 0" aria-label="Move question up" @click="moveQuestion(qi, -1)"><Icon name="arrow-up" :size="14" /></button>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-chip disabled:opacity-30" :disabled="qi === form.questions.length - 1" aria-label="Move question down" @click="moveQuestion(qi, 1)"><Icon name="arrow-down" :size="14" /></button>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30" :disabled="form.questions.length === 1" aria-label="Delete question" @click="form.questions.splice(qi, 1)"><Icon name="trash" :size="14" /></button>
                    </div>

                    <textarea v-model="q.prompt" rows="2" class="field" placeholder="Write the question…" :aria-label="`Question ${qi + 1}`" />
                    <p v-if="err(`questions.${qi}.prompt`)" class="mt-1 text-sm text-rose-600">{{ err(`questions.${qi}.prompt`) }}</p>

                    <template v-if="q.type === 'short'">
                        <p class="mt-4 mb-2 text-xs font-semibold text-muted">Accepted answers. Capital letters, extra spaces and a final full stop are ignored, and numbers match by value (2.5 = 2,50).</p>
                        <ul class="space-y-2">
                            <li v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2">
                                <span class="grid size-6 shrink-0 place-items-center rounded-md bg-emerald-500 text-white"><Icon name="check" :size="13" /></span>
                                <input v-model="o.text" type="text" class="field py-2" :placeholder="oi === 0 ? 'e.g. print' : 'Another accepted answer'" :aria-label="`Accepted answer ${oi + 1}`" />
                                <button type="button" class="rounded-lg p-1.5 text-muted hover:text-rose-600 disabled:opacity-30" :disabled="q.options.length <= 1" aria-label="Remove answer" @click="q.options.splice(oi, 1)"><Icon name="x" :size="14" /></button>
                            </li>
                        </ul>
                        <button v-if="q.options.length < 6" type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:underline" @click="q.options.push({ text: '', correct: true })">+ Add accepted answer</button>
                    </template>
                    <template v-else-if="q.type === 'true_false'">
                        <p class="mt-4 mb-2 text-xs font-semibold text-muted">Click the circle next to the right answer. You can rename the labels, e.g. Yes / No.</p>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            <li v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2">
                                <button
                                    type="button"
                                    :class="o.correct ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-line text-transparent hover:border-emerald-300'"
                                    class="grid size-6 shrink-0 place-items-center rounded-full border-2 transition"
                                    :aria-label="`Mark ${o.text || 'option ' + (oi + 1)} as correct`"
                                    :aria-pressed="o.correct"
                                    @click="toggleCorrect(q, oi)"
                                >
                                    <Icon name="check" :size="13" />
                                </button>
                                <input v-model="o.text" type="text" class="field py-2" :aria-label="oi === 0 ? 'Label for true' : 'Label for false'" />
                            </li>
                        </ul>
                    </template>
                    <template v-else>
                    <p class="mt-4 mb-2 text-xs font-semibold text-muted">Answer options. Click the circle to mark the correct answer.</p>
                    <ul class="space-y-2">
                        <li v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2">
                            <button
                                type="button"
                                :class="[o.correct ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-line text-transparent hover:border-emerald-300', q.type === 'single' ? 'rounded-full' : 'rounded-md']"
                                class="grid size-6 shrink-0 place-items-center border-2 transition"
                                :aria-label="`Mark option ${oi + 1} as correct`"
                                :aria-pressed="o.correct"
                                @click="toggleCorrect(q, oi)"
                            >
                                <Icon name="check" :size="13" />
                            </button>
                            <input v-model="o.text" type="text" class="field py-2" :placeholder="`Option ${String.fromCharCode(65 + oi)}`" />
                            <button type="button" class="rounded-lg p-1.5 text-muted hover:text-rose-600 disabled:opacity-30" :disabled="q.options.length <= 2" aria-label="Delete option" @click="q.options.splice(oi, 1)"><Icon name="x" :size="14" /></button>
                        </li>
                    </ul>
                    <p v-if="err(`questions.${qi}.options`)" class="mt-1 text-sm text-rose-600">{{ err(`questions.${qi}.options`) }}</p>
                    <button v-if="q.options.length < 6" type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:underline" @click="q.options.push({ text: '', correct: false })">+ Add option</button>
                    </template>
                    <div class="mt-4 grid gap-2 sm:grid-cols-3">
                        <input v-model="q.explanation" type="text" class="field text-sm sm:col-span-2" placeholder="Explanation (optional, shown after the student passes)" />
                        <input v-model="q.concept" type="text" class="field text-sm" placeholder="Concept, e.g. loops" :aria-label="`Concept for question ${qi + 1}`" />
                    </div>
                </div>

                <button type="button" class="w-full rounded-2xl border-2 border-dashed border-line py-4 text-sm font-semibold text-muted transition hover:border-brand-300 hover:text-brand-600" @click="form.questions.push(blankQuestion())">
                    <Icon name="plus" :size="16" class="inline" /> Add question
                </button>
            </div>
        </div>

        <!-- Settings -->
        <div class="space-y-6">
            <div class="card space-y-4 p-5 lg:sticky lg:top-24">
                <h2 class="font-display font-bold text-ink">Settings</h2>
                <Field id="duration" label="Estimated duration (minutes)" :error="err('duration_minutes')">
                    <input id="duration" v-model="form.duration_minutes" type="number" min="1" class="field" />
                </Field>
                <template v-if="isQuiz">
                    <Field id="pass_score" label="Pass score (0–100)" :error="err('pass_score')">
                        <input id="pass_score" v-model.number="form.pass_score" type="number" min="0" max="100" class="field" />
                    </Field>
                    <Field id="max_attempts" label="Attempt limit" :error="err('max_attempts')" hint="Leave empty to allow unlimited attempts.">
                        <input id="max_attempts" v-model="form.max_attempts" type="number" min="1" class="field" />
                    </Field>
                    <Field id="time_limit" label="Time limit (minutes)" :error="err('time_limit_minutes')" hint="Leave empty for no timer. Answers are sent automatically when time runs out.">
                        <input id="time_limit" v-model="form.time_limit_minutes" type="number" min="1" max="180" class="field" />
                    </Field>
                    <Field id="assessment" label="Make it a mission" :error="err('assessment')" hint="The Warm-up Mission (start of the course) and the Final Mission are compared for the per-concept growth report. Use different questions covering the same concepts.">
                        <select id="assessment" v-model="form.assessment" class="field">
                            <option value="">Regular quiz</option>
                            <option v-for="(label, key) in assessments" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </Field>
                </template>
                <template v-if="form.type === 'assignment'">
                    <Field id="max_score" label="Maximum score" :error="err('max_score')">
                        <input id="max_score" v-model.number="form.max_score" type="number" min="1" class="field" />
                    </Field>
                    <Field id="workspace" label="Students work in" :error="err('workspace')" hint="Scratch editor: students build the project right in the lesson and hand it in with one click. You can play it on the submissions list.">
                        <select id="workspace" v-model="form.workspace" class="field">
                            <option value="">Anywhere (link, file or text)</option>
                            <option value="scratch">Scratch editor</option>
                        </select>
                    </Field>
                </template>
                <label class="flex items-start gap-3 rounded-xl border border-line p-3">
                    <input v-model="form.is_published" type="checkbox" class="mt-0.5 rounded border-line text-brand-600 focus:ring-brand-300" />
                    <span>
                        <span class="block text-sm font-semibold text-ink">Show to students</span>
                        <span class="text-xs text-muted">Turn off to save as a draft.</span>
                    </span>
                </label>
                <button type="submit" class="btn-primary w-full" :disabled="form.processing"><Icon name="check" :size="18" /> Save</button>
            </div>
        </div>
    </form>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
