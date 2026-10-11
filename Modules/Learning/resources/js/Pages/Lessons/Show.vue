<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import LessonQuestions from '@/Components/ui/LessonQuestions.vue';
import GradeForm from '../../Components/GradeForm.vue';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';
import SubmissionContent from '../../Components/SubmissionContent.vue';

const props = defineProps({
    course: Object, lesson: Object, outline: Array, done: Array, prev: Object, next: Object, can: Object,
    questions: Array, attempts: Array, remaining: Number, result: Object, answerKey: Object, attemptSummary: Array,
    submission: Object, submissions: Array,
});

const isDone = computed(() => props.done?.includes(props.lesson.id));
const complete = () => router.post(route('learning.lessons.complete', props.lesson.id), {}, { preserveScroll: true });

// ---- Kuis ----
const passedOnce = computed(() => props.attempts?.some((a) => a.passed));
const canAttempt = computed(() => props.can.learn && (props.remaining === null || props.remaining > 0));
const taking = ref(props.can.learn && !props.result);
const answers = reactive({});
const resetAnswers = () => props.questions?.forEach((q) => (answers[q.id] = q.type === 'multiple' ? [] : null));
resetAnswers();
const answeredCount = computed(() => props.questions?.filter((q) => (q.type === 'multiple' ? answers[q.id]?.length : answers[q.id] !== null)).length ?? 0);
const quizForm = useForm({ answers: {} });
const submitQuiz = () => {
    quizForm.answers = { ...answers };
    quizForm.post(route('learning.quiz.store', props.lesson.id), { preserveScroll: true, onSuccess: () => { taking.value = false; window.scrollTo({ top: 0, behavior: 'smooth' }); } });
};
const retry = () => { resetAnswers(); taking.value = true; };
const optionClass = (q, i) => {
    const item = props.result?.items?.[q.id];
    const chosen = item?.given?.includes(i);
    if (item?.answer) {
        if (item.answer.includes(i)) return 'border-emerald-300 bg-emerald-50';
        if (chosen) return 'border-rose-300 bg-rose-50';
    } else if (chosen) {
        return item.correct ? 'border-emerald-300 bg-emerald-50' : 'border-rose-300 bg-rose-50';
    }
    return 'border-line';
};

// ---- Tugas ----
const subForm = useForm({ body: props.submission?.body ?? '', link_url: props.submission?.link_url ?? '', file: null });
const editing = ref(!props.submission || props.submission.status === 'revision');
const submitWork = () => subForm.post(route('learning.submissions.store', props.lesson.id), { forceFormData: true, preserveScroll: true, onSuccess: () => (editing.value = false) });
const grading = ref(null);
const subTone = { submitted: 'amber', revision: 'red', graded: 'green' };
</script>

<template>
    <Head :title="lesson.title" />

    <div class="grid gap-6 lg:grid-cols-4">
        <!-- Daftar isi -->
        <aside class="order-2 lg:order-1">
            <div class="card overflow-hidden lg:sticky lg:top-24">
                <Link :href="route('learning.courses.show', course.id)" class="flex items-center gap-2 border-b border-line px-4 py-3 text-sm font-bold text-ink hover:text-brand-600">
                    <Icon name="arrow-left" :size="15" /> <span class="truncate">{{ course.title }}</span>
                </Link>
                <ol class="max-h-[60vh] overflow-y-auto p-2">
                    <li v-for="(item, i) in outline" :key="item.id">
                        <Link
                            :href="route('learning.lessons.show', item.id)"
                            :class="item.id === lesson.id ? 'bg-brand-50 text-brand-700' : 'text-ink/80 hover:bg-chip'"
                            class="flex items-center gap-2.5 rounded-xl px-2.5 py-2 text-sm"
                        >
                            <LessonTypeIcon :type="item.type" :done="done?.includes(item.id)" size="sm" />
                            <span class="min-w-0 flex-1 truncate" :class="item.id === lesson.id && 'font-semibold'">{{ i + 1 }}. {{ item.title }}</span>
                        </Link>
                    </li>
                </ol>
            </div>
        </aside>

        <div class="order-1 space-y-6 lg:order-2 lg:col-span-3">
            <!-- Kepala -->
            <div class="flex flex-wrap items-start gap-4">
                <LessonTypeIcon :type="lesson.type" :done="isDone" />
                <div class="min-w-0 flex-1">
                    <p class="eyebrow">{{ lesson.assessment_label ?? lesson.type_label }}<template v-if="lesson.duration_minutes"> · {{ lesson.duration_minutes }} menit</template></p>
                    <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ lesson.title }}</h1>
                </div>
                <Badge v-if="can.manage && !lesson.is_published" tone="gray">Draf</Badge>
                <Link v-if="can.manage" :href="route('learning.lessons.edit', lesson.id)" class="btn-ghost"><Icon name="pencil" :size="16" /> Ubah</Link>
            </div>

            <div v-if="lesson.video_embed" class="aspect-video overflow-hidden rounded-3xl bg-ink shadow-lg">
                <iframe :src="lesson.video_embed" class="size-full" title="Video materi" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen loading="lazy" />
            </div>
            <a v-else-if="lesson.video_url" :href="lesson.video_url" target="_blank" rel="noopener" class="card flex items-center gap-3 p-4 font-semibold text-brand-600"><Icon name="play" :size="18" /> Tonton video materi</a>

            <article v-if="lesson.body_html" class="card lesson-prose p-6 sm:p-8" v-html="lesson.body_html" />

            <!-- ========== MATERI ========== -->
            <div v-if="lesson.type === 'material' && can.learn" class="card flex flex-wrap items-center justify-between gap-3 p-5">
                <p class="text-sm text-muted">{{ isDone ? 'Materi ini sudah kamu selesaikan.' : 'Sudah selesai membaca? Tandai agar progresmu tercatat.' }}</p>
                <span v-if="isDone" class="inline-flex items-center gap-1.5 font-semibold text-emerald-700"><Icon name="circle-check" :size="18" /> Selesai</span>
                <button v-else class="btn-primary" @click="complete"><Icon name="check" :size="18" /> Tandai selesai</button>
            </div>

            <!-- ========== KUIS (siswa) ========== -->
            <template v-if="lesson.type === 'quiz' && can.learn">
                <div v-if="lesson.assessment && !result" class="flex items-start gap-3 rounded-2xl bg-brand-50 p-4 text-sm text-ink">
                    <Icon name="rocket" :size="20" class="mt-0.5 shrink-0 text-brand-600" />
                    <p v-if="lesson.assessment === 'pre'"><b>Ini misi pemanasan, bukan ujian.</b> Jawab sebisamu, tidak apa-apa kalau belum tahu. Hasilnya membantu gurumu tahu harus mulai dari mana.</p>
                    <p v-else><b>Misi akhir!</b> Tunjukkan apa yang sudah kamu pelajari. Hasilnya dibandingkan dengan misi pemanasan untuk melihat kemajuanmu.</p>
                </div>
                <div v-if="result && !taking" :class="result.passed ? 'bg-emerald-700' : 'bg-amber-700'" role="status" class="relative overflow-hidden rounded-3xl p-6 text-white">
                    <div class="flex flex-wrap items-center gap-5">
                        <div class="grid size-24 place-items-center rounded-full bg-white/20 ring-4 ring-white/40">
                            <span class="font-display text-4xl font-black tabular-nums">{{ result.score }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="font-display text-2xl font-extrabold">{{ result.passed ? 'Lulus! Kerja bagus 🎉' : 'Belum lulus, ayo coba lagi!' }}</p>
                            <p class="text-white">{{ result.earned }} dari {{ result.total }} poin · nilai lulus {{ lesson.pass_score }}</p>
                            <p v-if="remaining !== null" class="text-sm text-white">Sisa kesempatan: {{ remaining }}</p>
                        </div>
                        <button v-if="canAttempt" class="btn bg-white text-ink hover:bg-white/90" @click="retry"><Icon name="rotate-ccw" :size="16" /> {{ passedOnce ? 'Kerjakan lagi' : 'Coba lagi' }}</button>
                    </div>
                </div>

                <!-- Pembahasan hasil terakhir -->
                <div v-if="result && !taking" class="space-y-4">
                    <div v-for="(q, qi) in questions" :key="q.id" class="card p-5">
                        <div class="flex items-start gap-3">
                            <span :class="result.items[q.id].correct ? 'bg-emerald-500' : 'bg-rose-500'" class="grid size-7 shrink-0 place-items-center rounded-lg text-white"><Icon :name="result.items[q.id].correct ? 'check' : 'x'" :size="14" /></span>
                            <p class="font-semibold whitespace-pre-line text-ink">{{ qi + 1 }}. {{ q.prompt }}</p>
                        </div>
                        <ul class="mt-3 space-y-2 pl-10">
                            <li v-for="(o, oi) in q.options" :key="oi" :class="optionClass(q, oi)" class="rounded-xl border px-3 py-2 text-sm text-ink">{{ o }}</li>
                        </ul>
                        <p v-if="result.items[q.id].explanation" class="mt-3 ml-10 rounded-xl bg-sky-soft p-3 text-sm text-ink"><b class="text-sky-ink">Pembahasan:</b> {{ result.items[q.id].explanation }}</p>
                    </div>
                    <p v-if="!result.reveal" class="text-center text-sm text-muted">Kunci jawaban dan pembahasan muncul setelah kamu lulus.</p>
                </div>

                <!-- Mengerjakan -->
                <form v-if="taking && canAttempt" class="space-y-4" @submit.prevent="submitQuiz">
                    <div v-for="(q, qi) in questions" :key="q.id" class="card p-5">
                        <p class="font-semibold whitespace-pre-line text-ink">{{ qi + 1 }}. {{ q.prompt }}</p>
                        <p class="mt-0.5 text-xs text-muted">{{ q.type === 'multiple' ? 'Pilih semua jawaban yang benar' : 'Pilih satu jawaban' }} · {{ q.points }} poin</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="(o, oi) in q.options"
                                :key="oi"
                                :class="(q.type === 'multiple' ? answers[q.id].includes(oi) : answers[q.id] === oi) ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-100' : 'border-line hover:border-brand-200'"
                                class="flex cursor-pointer items-center gap-3 rounded-xl border p-3 text-sm text-ink transition"
                            >
                                <input v-if="q.type === 'multiple'" v-model="answers[q.id]" type="checkbox" :value="oi" class="rounded border-line text-brand-600 focus:ring-brand-300" />
                                <input v-else v-model="answers[q.id]" type="radio" :name="`q${q.id}`" :value="oi" class="border-line text-brand-600 focus:ring-brand-300" />
                                <span class="grid size-6 shrink-0 place-items-center rounded-md bg-chip text-xs font-bold text-muted">{{ String.fromCharCode(65 + oi) }}</span>
                                {{ o }}
                            </label>
                        </div>
                    </div>
                    <div class="card sticky bottom-4 flex flex-wrap items-center justify-between gap-3 p-4 shadow-lg">
                        <p class="text-sm text-muted">Terjawab <b class="text-ink">{{ answeredCount }}/{{ questions.length }}</b><template v-if="remaining !== null"> · sisa kesempatan {{ remaining }}</template></p>
                        <div class="flex gap-2">
                            <button v-if="result" type="button" class="btn-ghost" @click="taking = false">Batal</button>
                            <button type="submit" class="btn-accent" :disabled="quizForm.processing || answeredCount === 0"><Icon name="check" :size="18" /> Kumpulkan jawaban</button>
                        </div>
                    </div>
                </form>
                <p v-if="!canAttempt && !result" class="card p-5 text-sm text-muted">Kesempatan mengerjakan kuis ini sudah habis.</p>
            </template>

            <!-- ========== KUIS (guru/admin) ========== -->
            <template v-if="lesson.type === 'quiz' && can.manage">
                <div class="card overflow-hidden">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="font-display font-bold text-ink">Hasil siswa</h2>
                        <p class="text-sm text-muted">Nilai lulus {{ lesson.pass_score }}<template v-if="lesson.max_attempts"> · maks. {{ lesson.max_attempts }} percobaan</template></p>
                    </div>
                    <ul v-if="attemptSummary.length" class="divide-y divide-line">
                        <li v-for="row in attemptSummary" :key="row.student.id" class="flex items-center gap-3 px-5 py-3">
                            <Avatar :initials="row.student.initials" :seed="row.student.id" size="sm" />
                            <span class="flex-1 font-semibold text-ink">{{ row.student.name }}</span>
                            <span class="text-xs text-muted">{{ row.tries }}× mencoba</span>
                            <Badge :tone="row.passed ? 'green' : 'amber'">{{ row.best }}</Badge>
                        </li>
                    </ul>
                    <p v-else class="px-5 py-8 text-center text-sm text-muted">Belum ada siswa yang mengerjakan.</p>
                </div>
                <div class="space-y-3">
                    <h2 class="font-display font-bold text-ink">Soal dan kunci jawaban</h2>
                    <div v-for="(q, qi) in questions" :key="q.id" class="card p-5">
                        <p class="font-semibold whitespace-pre-line text-ink">{{ qi + 1 }}. {{ q.prompt }} <span class="text-xs font-normal text-muted">({{ q.points }} poin)</span></p>
                        <ul class="mt-2 space-y-1.5">
                            <li v-for="(o, oi) in q.options" :key="oi" :class="answerKey[q.id].answer.includes(oi) ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-ink'" class="flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm">
                                <Icon v-if="answerKey[q.id].answer.includes(oi)" name="check" :size="14" /> {{ o }}
                            </li>
                        </ul>
                        <p v-if="answerKey[q.id].explanation" class="mt-2 text-sm text-muted">Pembahasan: {{ answerKey[q.id].explanation }}</p>
                    </div>
                </div>
            </template>

            <!-- ========== TUGAS (siswa) ========== -->
            <template v-if="lesson.type === 'assignment' && can.learn">
                <div v-if="submission && !editing" class="card space-y-4 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="font-display font-bold text-ink">Pengumpulanmu</h2>
                        <Badge :tone="subTone[submission.status]">{{ submission.status_label }}</Badge>
                    </div>
                    <SubmissionContent :submission="submission" />
                    <p class="text-xs text-muted">Dikumpulkan {{ submission.submitted_at }}</p>
                    <div v-if="submission.status === 'graded'" class="flex items-center gap-4 rounded-2xl bg-emerald-50 p-4">
                        <span class="font-display text-3xl font-black text-emerald-700">{{ submission.score }}<span class="text-base text-emerald-700/70">/{{ lesson.max_score }}</span></span>
                        <p v-if="submission.feedback" class="flex-1 text-sm text-ink"><b>{{ submission.grader ?? 'Guru' }}:</b> {{ submission.feedback }}</p>
                    </div>
                    <button v-if="submission.status === 'submitted'" class="btn-ghost text-sm" @click="editing = true"><Icon name="pencil" :size="15" /> Perbarui pengumpulan</button>
                </div>

                <div v-if="submission?.status === 'revision'" class="rounded-2xl border border-accent-200 bg-accent-50 p-4 text-sm text-ink">
                    <b class="text-accent-700">Perlu revisi:</b> {{ submission.feedback }}
                </div>

                <form v-if="editing" class="card space-y-4 p-5" @submit.prevent="submitWork">
                    <h2 class="font-display font-bold text-ink">{{ submission ? 'Kumpulkan ulang' : 'Kumpulkan tugas' }}</h2>
                    <Field id="body" label="Jawaban / catatan" :error="subForm.errors.body">
                        <textarea id="body" v-model="subForm.body" rows="5" class="field" placeholder="Ceritakan apa yang kamu buat, atau tempel kodenya di sini." />
                    </Field>
                    <Field id="link_url" label="Tautan proyek" :error="subForm.errors.link_url" hint="mis. link Scratch, Replit, Google Drive.">
                        <input id="link_url" v-model="subForm.link_url" type="url" class="field" placeholder="https://" />
                    </Field>
                    <Field id="file" label="Unggah berkas" :error="subForm.errors.file" :hint="submission?.file_name ? `Berkas saat ini: ${submission.file_name}` : 'Gambar, PDF, ZIP, .sb3, .py, .html. Maks. 10 MB.'">
                        <input id="file" type="file" class="field file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-700" @input="subForm.file = $event.target.files[0]" />
                    </Field>
                    <div class="flex justify-end gap-2">
                        <button v-if="submission && submission.status !== 'revision'" type="button" class="btn-ghost" @click="editing = false">Batal</button>
                        <button type="submit" class="btn-accent" :disabled="subForm.processing"><Icon name="upload" :size="18" /> Kumpulkan</button>
                    </div>
                </form>
            </template>

            <!-- ========== TUGAS (guru/admin) ========== -->
            <div v-if="lesson.type === 'assignment' && can.manage" class="card overflow-hidden">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="font-display font-bold text-ink">Pengumpulan siswa</h2>
                    <p class="text-sm text-muted">Nilai maksimal {{ lesson.max_score }}</p>
                </div>
                <ul v-if="submissions.length" class="divide-y divide-line">
                    <li v-for="s in submissions" :key="s.id" class="space-y-3 px-5 py-4">
                        <div class="flex flex-wrap items-center gap-3">
                            <Avatar :initials="s.student.initials" :seed="s.student.id" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-ink">{{ s.student.name }}</p>
                                <p class="text-xs text-muted">{{ s.submitted_at }}</p>
                            </div>
                            <span v-if="s.score !== null" class="font-display font-bold text-ink">{{ s.score }}/{{ lesson.max_score }}</span>
                            <Badge :tone="subTone[s.status]">{{ s.status_label }}</Badge>
                            <button class="btn-ghost px-3 py-1.5 text-xs" @click="grading = grading === s.id ? null : s.id">{{ s.status === 'submitted' ? 'Nilai' : 'Ubah nilai' }}</button>
                        </div>
                        <SubmissionContent :submission="s" class="pl-11" />
                        <p v-if="s.feedback && grading !== s.id" class="pl-11 text-sm text-muted">Umpan balik: {{ s.feedback }}</p>
                        <GradeForm v-if="grading === s.id" :submission="s" :max-score="lesson.max_score" @done="grading = null" />
                    </li>
                </ul>
                <p v-else class="px-5 py-8 text-center text-sm text-muted">Belum ada pengumpulan dari siswa kelasmu.</p>
            </div>

            <LessonQuestions :lesson-id="lesson.id" />

            <!-- Navigasi -->
            <nav class="flex items-center justify-between gap-3 border-t border-line pt-5">
                <Link v-if="prev" :href="route('learning.lessons.show', prev.id)" class="group flex min-w-0 items-center gap-2 text-sm">
                    <Icon name="arrow-left" :size="16" class="text-muted group-hover:text-brand-600" />
                    <span class="min-w-0"><span class="block text-xs text-muted">Sebelumnya</span><span class="block truncate font-semibold text-ink group-hover:text-brand-600">{{ prev.title }}</span></span>
                </Link>
                <span v-else />
                <Link v-if="next" :href="route('learning.lessons.show', next.id)" class="group flex min-w-0 items-center gap-2 text-right text-sm">
                    <span class="min-w-0"><span class="block text-xs text-muted">Berikutnya</span><span class="block truncate font-semibold text-ink group-hover:text-brand-600">{{ next.title }}</span></span>
                    <Icon name="arrow-right" :size="16" class="text-muted group-hover:text-brand-600" />
                </Link>
            </nav>
        </div>
    </div>
</template>
