<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import ConfirmDialog from '@/Components/ui/ConfirmDialog.vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import { useConfirm } from '@/composables/useConfirm';
import LessonTypeIcon from '../../Components/LessonTypeIcon.vue';

const props = defineProps({ course: Object, lesson: Object, defaultType: String, types: Object, questionTypes: Object });
const l = props.lesson ?? {};

const blankQuestion = () => ({ id: null, prompt: '', type: 'single', points: 1, explanation: '', options: [{ text: '', correct: true }, { text: '', correct: false }, { text: '', correct: false }] });

const form = useForm({
    title: l.title ?? '',
    type: l.type ?? props.defaultType,
    body: l.body ?? '',
    video_url: l.video_url ?? '',
    duration_minutes: l.duration_minutes ?? '',
    is_published: l.is_published ?? true,
    pass_score: l.pass_score ?? 70,
    max_attempts: l.max_attempts ?? '',
    max_score: l.max_score ?? 100,
    questions: l.questions?.length ? JSON.parse(JSON.stringify(l.questions)) : [blankQuestion()],
});

const isQuiz = computed(() => form.type === 'quiz');
const bodyLabel = computed(() => ({ material: 'Isi materi', quiz: 'Petunjuk kuis', assignment: 'Instruksi tugas' })[form.type]);

const toggleCorrect = (q, i) => {
    if (q.type === 'single') q.options.forEach((o, j) => (o.correct = i === j));
    else q.options[i].correct = !q.options[i].correct;
};
const setType = (q, type) => {
    q.type = type;
    if (type === 'single' && q.options.filter((o) => o.correct).length > 1) {
        const first = q.options.findIndex((o) => o.correct);
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
        questions: data.type === 'quiz' ? data.questions : [],
    }));
    props.lesson ? form.put(route('learning.lessons.update', props.lesson.id)) : form.post(route('learning.lessons.store', props.course.id));
};

const confirm = useConfirm();
const err = (key) => form.errors[key];
</script>

<template>
    <Head :title="lesson ? 'Ubah ' + types[form.type].toLowerCase() : types[form.type] + ' baru'" />

    <PageHeader :eyebrow="course.title" :title="lesson ? `Ubah ${types[form.type].toLowerCase()}` : `${types[form.type]} baru`">
        <template #back>
            <Link :href="route('learning.courses.show', course.id)" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600"><Icon name="arrow-left" :size="16" /> {{ course.title }}</Link>
        </template>
        <template v-if="lesson" #actions>
            <button type="button" class="btn-ghost text-rose-600" @click="confirm.ask({ title: 'Hapus?', message: 'Nilai dan pengumpulan siswa untuk bagian ini ikut terhapus.', url: route('learning.lessons.destroy', lesson.id) })"><Icon name="trash" :size="16" /> Hapus</button>
        </template>
    </PageHeader>

    <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <div class="card space-y-5 p-6">
                <div v-if="!lesson" class="grid grid-cols-3 gap-2">
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
                </div>

                <Field id="title" label="Judul" :error="err('title')" required>
                    <input id="title" v-model="form.title" type="text" class="field" :placeholder="isQuiz ? 'mis. Kuis: Variabel dan tipe data' : 'mis. Mengenal variabel'" />
                </Field>

                <Field id="body" :label="bodyLabel" :error="err('body')" hint="Mendukung Markdown: **tebal**, # judul, - daftar, `kode`, dan blok kode dengan ```.">
                    <textarea id="body" v-model="form.body" :rows="form.type === 'material' ? 14 : 5" class="field font-mono text-sm" />
                </Field>

                <Field v-if="form.type === 'material'" id="video_url" label="Link video (YouTube/Vimeo)" :error="err('video_url')">
                    <input id="video_url" v-model="form.video_url" type="url" class="field" placeholder="https://youtu.be/…" />
                </Field>
            </div>

            <!-- Pembuat soal kuis -->
            <div v-if="isQuiz" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold text-ink">Soal ({{ form.questions.length }})</h2>
                    <p v-if="err('questions')" class="text-sm font-medium text-rose-600">{{ err('questions') }}</p>
                </div>

                <div v-for="(q, qi) in form.questions" :key="qi" class="card p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <span class="grid size-7 place-items-center rounded-lg bg-amber-100 text-xs font-bold text-amber-700">{{ qi + 1 }}</span>
                        <div class="flex gap-1">
                            <button v-for="(label, key) in questionTypes" :key="key" type="button" :class="q.type === key ? 'bg-ink text-white' : 'bg-chip text-muted hover:text-ink'" class="rounded-lg px-2.5 py-1 text-xs font-semibold" @click="setType(q, key)">{{ label }}</button>
                        </div>
                        <label class="ml-auto flex items-center gap-1.5 text-xs text-muted">Poin <input v-model.number="q.points" type="number" min="1" max="20" class="field w-16 px-2 py-1 text-xs" /></label>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-chip disabled:opacity-30" :disabled="qi === 0" aria-label="Naikkan soal" @click="moveQuestion(qi, -1)"><Icon name="arrow-up" :size="14" /></button>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-chip disabled:opacity-30" :disabled="qi === form.questions.length - 1" aria-label="Turunkan soal" @click="moveQuestion(qi, 1)"><Icon name="arrow-down" :size="14" /></button>
                        <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30" :disabled="form.questions.length === 1" aria-label="Hapus soal" @click="form.questions.splice(qi, 1)"><Icon name="trash" :size="14" /></button>
                    </div>

                    <textarea v-model="q.prompt" rows="2" class="field" placeholder="Tulis pertanyaan…" :aria-label="`Pertanyaan ${qi + 1}`" />
                    <p v-if="err(`questions.${qi}.prompt`)" class="mt-1 text-sm text-rose-600">{{ err(`questions.${qi}.prompt`) }}</p>

                    <p class="mt-4 mb-2 text-xs font-semibold text-muted">Pilihan jawaban. Klik lingkaran untuk menandai jawaban benar.</p>
                    <ul class="space-y-2">
                        <li v-for="(o, oi) in q.options" :key="oi" class="flex items-center gap-2">
                            <button
                                type="button"
                                :class="[o.correct ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-line text-transparent hover:border-emerald-300', q.type === 'single' ? 'rounded-full' : 'rounded-md']"
                                class="grid size-6 shrink-0 place-items-center border-2 transition"
                                :aria-label="`Tandai pilihan ${oi + 1} benar`"
                                :aria-pressed="o.correct"
                                @click="toggleCorrect(q, oi)"
                            >
                                <Icon name="check" :size="13" />
                            </button>
                            <input v-model="o.text" type="text" class="field py-2" :placeholder="`Pilihan ${String.fromCharCode(65 + oi)}`" />
                            <button type="button" class="rounded-lg p-1.5 text-muted hover:text-rose-600 disabled:opacity-30" :disabled="q.options.length <= 2" aria-label="Hapus pilihan" @click="q.options.splice(oi, 1)"><Icon name="x" :size="14" /></button>
                        </li>
                    </ul>
                    <p v-if="err(`questions.${qi}.options`)" class="mt-1 text-sm text-rose-600">{{ err(`questions.${qi}.options`) }}</p>
                    <button v-if="q.options.length < 6" type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:underline" @click="q.options.push({ text: '', correct: false })">+ Tambah pilihan</button>

                    <input v-model="q.explanation" type="text" class="field mt-4 text-sm" placeholder="Pembahasan (opsional, tampil setelah siswa lulus)" />
                </div>

                <button type="button" class="w-full rounded-2xl border-2 border-dashed border-line py-4 text-sm font-semibold text-muted transition hover:border-brand-300 hover:text-brand-600" @click="form.questions.push(blankQuestion())">
                    <Icon name="plus" :size="16" class="inline" /> Tambah soal
                </button>
            </div>
        </div>

        <!-- Pengaturan -->
        <div class="space-y-6">
            <div class="card space-y-4 p-5 lg:sticky lg:top-24">
                <h2 class="font-display font-bold text-ink">Pengaturan</h2>
                <Field id="duration" label="Perkiraan durasi (menit)" :error="err('duration_minutes')">
                    <input id="duration" v-model="form.duration_minutes" type="number" min="1" class="field" />
                </Field>
                <template v-if="isQuiz">
                    <Field id="pass_score" label="Nilai lulus (0–100)" :error="err('pass_score')">
                        <input id="pass_score" v-model.number="form.pass_score" type="number" min="0" max="100" class="field" />
                    </Field>
                    <Field id="max_attempts" label="Batas percobaan" :error="err('max_attempts')" hint="Kosongkan agar siswa bisa mencoba berkali-kali.">
                        <input id="max_attempts" v-model="form.max_attempts" type="number" min="1" class="field" />
                    </Field>
                </template>
                <Field v-if="form.type === 'assignment'" id="max_score" label="Nilai maksimal" :error="err('max_score')">
                    <input id="max_score" v-model.number="form.max_score" type="number" min="1" class="field" />
                </Field>
                <label class="flex items-start gap-3 rounded-xl border border-line p-3">
                    <input v-model="form.is_published" type="checkbox" class="mt-0.5 rounded border-line text-brand-600 focus:ring-brand-300" />
                    <span>
                        <span class="block text-sm font-semibold text-ink">Tampilkan ke siswa</span>
                        <span class="text-xs text-muted">Matikan untuk menyimpan sebagai draf.</span>
                    </span>
                </label>
                <button type="submit" class="btn-primary w-full" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan</button>
            </div>
        </div>
    </form>

    <ConfirmDialog v-bind="confirm.state" @close="confirm.close" @confirm="confirm.run" />
</template>
