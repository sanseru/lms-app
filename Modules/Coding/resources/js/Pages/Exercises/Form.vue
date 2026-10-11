<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import Field from '@/Components/ui/Field.vue';
import Icon from '@/Components/ui/Icon.vue';
import PageHeader from '@/Components/ui/PageHeader.vue';
import CodeEditor from '../../Components/CodeEditor.vue';
import TestResultList from '../../Components/TestResultList.vue';
import { checkHtml } from '../../lib/htmlCheck';
import { runTests } from '../../lib/runner';

const props = defineProps({
    course: Object, lesson: Object, exercise: Object, tests: Array,
    languages: Object, modes: Object, modesByLanguage: Object, htmlRules: Object, runtime: Object, autoVerify: Boolean, autoPublish: Boolean,
});

const e = props.exercise ?? {};
const starters = {
    python: { io: 'angka = int(input())\n# tulis kodemu di sini\n', function: 'def luas(sisi):\n    # tulis kodemu di sini\n    pass\n', parsons: '' },
    javascript: { io: 'const angka = Number(input());\n// tulis kodemu di sini\n', function: 'function luas(sisi) {\n  // tulis kodemu di sini\n}\n', parsons: '' },
    html: { html: '<!doctype html>\n<html>\n  <head>\n    <style>\n    </style>\n  </head>\n  <body>\n\n  </body>\n</html>\n' },
};
const blankTest = (mode) => (mode === 'html' ? { id: null, kind: 'exists', input: 'h1', expected: '', property: '', is_hidden: false, weight: 1, fail_message: '' } : { id: null, kind: mode === 'function' ? 'function' : 'io', input: '', expected: '', property: '', is_hidden: false, weight: 1, fail_message: '' });

const form = useForm({
    title: props.lesson?.title ?? '',
    body: props.lesson?.body ?? '',
    duration_minutes: props.lesson?.duration_minutes ?? 15,
    is_published: props.lesson?.is_published ?? true,
    language: e.language ?? 'python',
    mode: e.mode ?? 'io',
    starter_code: e.starter_code ?? starters.python.io,
    solution_code: e.solution_code ?? '',
    function_name: e.function_name ?? 'luas',
    hints: e.hints?.length ? [...e.hints] : [''],
    time_limit_ms: e.time_limit_ms ?? 2000,
    show_solution_after: e.show_solution_after ?? 3,
    case_sensitive: e.case_sensitive ?? false,
    concept: e.concept ?? '',
    level: e.level ?? 1,
    rules: (e.rules ?? []).map((r) => ({ ...r, message: r.message ?? '' })),
    tests: props.tests.length ? props.tests.map((t) => ({ ...t, property: t.property ?? '', expected: t.expected ?? '', input: t.input ?? '', fail_message: t.fail_message ?? '' })) : [blankTest('io'), { ...blankTest('io'), is_hidden: true }],
});

const allowedModes = computed(() => props.modesByLanguage[form.language]);
watch(() => form.language, (lang) => {
    if (!allowedModes.value.includes(form.mode)) form.mode = allowedModes.value[0];
    if (!props.exercise) form.starter_code = starters[lang][form.mode] ?? '';
});
watch(() => form.mode, (mode, old) => {
    const kindOf = (m) => (m === 'html' ? 'html' : m === 'function' ? 'function' : 'io');
    if (kindOf(mode) !== kindOf(old)) form.tests = mode === 'parsons' ? [] : [blankTest(mode)];
    if (!props.exercise) form.starter_code = starters[form.language][mode] ?? '';
});

const err = (k) => form.errors[k];
const submit = () => {
    form.transform((d) => ({ ...d, show_solution_after: d.show_solution_after || null, level: d.level || null, hints: d.hints.filter((h) => h.trim()) }));
    props.lesson ? form.put(route('coding.exercises.update', props.lesson.id)) : form.post(route('coding.exercises.store', props.course.id));
};

// ---------- Uji solusi ----------
const verifying = ref(false);
const verification = computed(() => usePage().props.flash?.verification);
const localError = ref(null);
const dirty = computed(() => form.isDirty);

async function verify(publish = false) {
    if (!props.lesson || verifying.value) return;
    verifying.value = true;
    localError.value = null;
    try {
        const ex = { ...props.exercise };
        let outputs = {};
        let styles = {};
        if (ex.mode === 'html') styles = await checkHtml(ex.solution_code, props.tests);
        else if (ex.mode !== 'parsons' && props.runtime.driver === 'browser') {
            outputs = await runTests(ex, ex.solution_code, props.tests, { timeLimitMs: ex.time_limit_ms, indexURL: props.runtime.pyodide_url });
        }
        router.post(route('coding.exercises.verify', props.lesson.id), { outputs, styles, publish }, { preserveScroll: true, onFinish: () => (verifying.value = false) });
    } catch (x) {
        localError.value = 'Solusi tidak bisa dijalankan: ' + x.message;
        verifying.value = false;
    }
}
onMounted(() => props.autoVerify && verify(props.autoPublish));
</script>

<template>
    <Head :title="lesson ? 'Ubah soal coding' : 'Soal coding baru'" />

    <PageHeader :eyebrow="course.title" :title="lesson ? 'Ubah soal coding' : 'Soal coding baru'" description="Tulis cerita soal, kode awal, solusi, dan tes. Soal baru bisa terbit setelah solusimu lulus semua tes (Uji solusi).">
        <template #back>
            <Link :href="lesson ? route('learning.lessons.show', lesson.id) : route('learning.courses.show', course.id)" class="mb-3 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600"><Icon name="arrow-left" :size="16" /> Kembali</Link>
        </template>
    </PageHeader>

    <!-- Status uji solusi -->
    <div v-if="lesson" :class="exercise.verified && !dirty ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'" class="mb-6 rounded-2xl border p-4">
        <div class="flex flex-wrap items-center gap-3">
            <Icon :name="exercise.verified && !dirty ? 'badge-check' : 'flask'" :class="exercise.verified && !dirty ? 'text-emerald-700' : 'text-amber-600'" />
            <p class="flex-1 text-sm text-ink">
                <template v-if="dirty">Ada perubahan yang belum disimpan. Simpan dulu, lalu solusi akan diuji otomatis.</template>
                <template v-else-if="exercise.verified">Solusi lulus semua tes. {{ lesson.is_published ? 'Soal sudah terbit.' : 'Soal siap diterbitkan.' }}</template>
                <template v-else>Solusi belum lulus Uji solusi, jadi soal masih draf dan belum terlihat siswa.</template>
            </p>
            <button v-if="!dirty" type="button" class="btn-ghost py-2 text-sm" :disabled="verifying" @click="verify(!lesson.is_published)">
                <Icon name="flask" :size="15" /> {{ verifying ? 'Menguji…' : exercise.verified && !lesson.is_published ? 'Uji & terbitkan' : 'Uji solusi' }}
            </button>
        </div>
        <p v-if="localError" class="mt-2 text-sm text-rose-700">{{ localError }}</p>
        <TestResultList v-if="verification && !verification.results.every((r) => r.passed)" :results="verification.results.map((r) => ({ ...r, hidden: false }))" :mode="exercise.mode" class="mt-3" />
    </div>

    <form class="grid gap-6 xl:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 xl:col-span-2">
            <div class="card space-y-5 p-6">
                <Field id="title" label="Judul soal" :error="err('title')" required>
                    <input id="title" v-model="form.title" type="text" class="field" placeholder="mis. Bantu kucing menghitung ikan" />
                </Field>
                <Field id="body" label="Cerita soal" :error="err('body')" hint="Markdown didukung. Jelaskan apa yang harus dibuat dan beri contoh.">
                    <textarea id="body" v-model="form.body" rows="6" class="field font-mono text-sm" />
                </Field>

                <div>
                    <p class="label">Bahasa</p>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="(label, key) in languages" :key="key" type="button" :class="form.language === key ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200' : 'border-line hover:border-brand-200'" class="rounded-2xl border p-3 text-sm font-semibold text-ink" @click="form.language = key">{{ label }}</button>
                    </div>
                </div>
                <div>
                    <p class="label">Cara menilai</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="m in allowedModes" :key="m" type="button" :class="form.mode === m ? 'bg-ink text-white' : 'bg-chip text-muted hover:text-ink'" class="rounded-xl px-3 py-2 text-sm font-semibold" @click="form.mode = m">{{ modes[m] }}</button>
                    </div>
                    <p class="mt-1.5 text-xs text-muted">
                        <template v-if="form.mode === 'io'">Program membaca input lalu mencetak output. Cocok untuk pemula.</template>
                        <template v-else-if="form.mode === 'function'">Siswa menulis fungsi; tiap tes berisi argumen dan nilai return yang diharapkan.</template>
                        <template v-else-if="form.mode === 'html'">Halaman siswa dicek: elemen, teks, atribut, dan gaya CSS.</template>
                        <template v-else>Siswa menyusun baris solusi yang diacak. Tidak perlu tes.</template>
                    </p>
                </div>
                <Field v-if="form.mode === 'function'" id="function_name" label="Nama fungsi" :error="err('function_name')" required>
                    <input id="function_name" v-model="form.function_name" type="text" class="field font-mono" />
                </Field>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div v-if="form.mode !== 'parsons'" class="card space-y-2 p-5">
                    <p class="label">Kode awal untuk siswa</p>
                    <CodeEditor v-model="form.starter_code" :language="form.language" min-height="200px" label="Kode awal" />
                </div>
                <div :class="form.mode === 'parsons' && 'lg:col-span-2'" class="card space-y-2 p-5">
                    <p class="label">{{ form.mode === 'parsons' ? 'Solusi (baris akan diacak untuk siswa)' : 'Solusi referensi' }} <span class="text-accent-600">*</span></p>
                    <CodeEditor v-model="form.solution_code" :language="form.language" min-height="200px" label="Solusi" />
                    <p v-if="err('solution_code')" class="text-sm text-rose-600">{{ err('solution_code') }}</p>
                    <p class="text-xs text-muted">Siswa tidak melihat solusi, kecuali setelah lulus atau gagal beberapa kali (atur di samping).</p>
                </div>
            </div>

            <!-- Tes -->
            <div v-if="form.mode !== 'parsons'" class="card p-5">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold text-ink">{{ form.mode === 'html' ? 'Aturan pengecekan' : 'Tes' }} ({{ form.tests.length }})</h2>
                    <p v-if="err('tests')" class="text-sm text-rose-600">{{ err('tests') }}</p>
                </div>
                <div class="space-y-3">
                    <div v-for="(t, i) in form.tests" :key="i" class="rounded-2xl border border-line p-4">
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="grid size-7 place-items-center rounded-lg bg-chip text-xs font-bold text-muted">{{ i + 1 }}</span>
                            <select v-if="form.mode === 'html'" v-model="t.kind" class="field w-auto py-1.5 text-sm" :aria-label="`Jenis aturan ${i + 1}`">
                                <option v-for="(label, key) in htmlRules" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <label v-else class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm" :class="t.is_hidden ? 'bg-amber-50 text-amber-800' : 'text-muted'">
                                <input v-model="t.is_hidden" type="checkbox" class="rounded border-line text-amber-500 focus:ring-amber-300" /> Tersembunyi
                            </label>
                            <label class="ml-auto flex items-center gap-1.5 text-xs text-muted">Bobot <input v-model.number="t.weight" type="number" min="1" max="10" class="field w-16 px-2 py-1 text-xs" /></label>
                            <button type="button" class="rounded-lg p-1.5 text-muted hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30" :disabled="form.tests.length === 1" aria-label="Hapus tes" @click="form.tests.splice(i, 1)"><Icon name="trash" :size="15" /></button>
                        </div>

                        <div v-if="form.mode === 'html'" class="grid gap-3 sm:grid-cols-3">
                            <Field :error="err(`tests.${i}.input`)" label="Selector CSS"><input v-model="t.input" type="text" class="field font-mono text-sm" placeholder="h1, img, .kartu" /></Field>
                            <Field v-if="['attr', 'style'].includes(t.kind)" :error="err(`tests.${i}.property`)" :label="t.kind === 'attr' ? 'Atribut' : 'Properti CSS'"><input v-model="t.property" type="text" class="field font-mono text-sm" :placeholder="t.kind === 'attr' ? 'alt' : 'background-color'" /></Field>
                            <Field v-if="t.kind !== 'exists'" :error="err(`tests.${i}.expected`)" :label="t.kind === 'count' ? 'Jumlah minimal' : t.kind === 'attr' ? 'Berisi (opsional)' : 'Nilai'"><input v-model="t.expected" type="text" class="field font-mono text-sm" :placeholder="t.kind === 'style' ? 'blue' : ''" /></Field>
                        </div>
                        <div v-else class="grid gap-3 sm:grid-cols-2">
                            <Field :error="err(`tests.${i}.input`)" :label="form.mode === 'function' ? 'Argumen (JSON)' : 'Input (stdin)'">
                                <textarea v-model="t.input" rows="2" class="field font-mono text-sm" :placeholder="form.mode === 'function' ? '[4]' : 'satu nilai per baris'" />
                            </Field>
                            <Field :error="err(`tests.${i}.expected`)" :label="form.mode === 'function' ? 'Nilai return (JSON)' : 'Output yang diharapkan'">
                                <textarea v-model="t.expected" rows="2" class="field font-mono text-sm" :placeholder="form.mode === 'function' ? '16' : ''" />
                            </Field>
                        </div>
                        <input v-model="t.fail_message" type="text" class="field mt-3 text-sm" placeholder="Pesan bila gagal (opsional), mis. Ingat, sisi bisa desimal" />
                    </div>
                </div>
                <button type="button" class="mt-3 w-full rounded-2xl border-2 border-dashed border-line py-3 text-sm font-semibold text-muted hover:border-brand-300 hover:text-brand-600" @click="form.tests.push(blankTest(form.mode))">
                    + Tambah {{ form.mode === 'html' ? 'aturan' : 'tes' }}
                </button>
                <p v-if="form.mode !== 'html'" class="mt-2 text-xs text-muted">Tes tersembunyi tidak pernah dikirim jawabannya ke browser siswa. Pakai untuk kasus tepi agar siswa tidak sekadar menyesuaikan dengan contoh.</p>
            </div>

            <!-- Aturan kode -->
            <div v-if="form.mode === 'io' || form.mode === 'function'" class="card p-5">
                <h2 class="mb-1 font-display text-lg font-bold text-ink">Code rules</h2>
                <p class="mb-3 text-sm text-muted">Checked on the server when the student submits. Comments and text inside quotes are ignored.</p>
                <div class="space-y-3">
                    <div v-for="(r, i) in form.rules" :key="i" class="grid gap-2 rounded-xl bg-chip/50 p-3 sm:grid-cols-[11rem_9rem_1fr_auto]">
                        <select v-model="r.type" class="field" :aria-label="`Rule ${i + 1} type`">
                            <option value="require">Must use</option>
                            <option value="forbid">Must not use</option>
                            <option value="max_lines">At most N lines</option>
                        </select>
                        <div>
                            <input v-model="r.value" type="text" class="field font-mono" :placeholder="r.type === 'max_lines' ? '6' : r.type === 'require' ? 'for' : 'sum'" :aria-label="`Rule ${i + 1} value`" />
                            <p v-if="err(`rules.${i}.value`)" class="mt-1 text-xs text-rose-600">{{ err(`rules.${i}.value`) }}</p>
                        </div>
                        <input v-model="r.message" type="text" class="field" placeholder="Message when it fails (optional)" :aria-label="`Rule ${i + 1} message`" />
                        <button type="button" class="rounded-lg p-2 text-muted hover:text-rose-600" aria-label="Remove rule" @click="form.rules.splice(i, 1)"><Icon name="x" :size="15" /></button>
                    </div>
                </div>
                <button v-if="form.rules.length < 10" type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:underline" @click="form.rules.push({ type: 'require', value: '', message: '' })">+ Add rule</button>
            </div>

            <!-- Petunjuk -->
            <div class="card p-5">
                <h2 class="mb-1 font-display text-lg font-bold text-ink">Petunjuk bertingkat</h2>
                <p class="mb-3 text-sm text-muted">Terbuka satu per satu saat siswa memintanya.</p>
                <div class="space-y-2">
                    <div v-for="(h, i) in form.hints" :key="i" class="flex gap-2">
                        <input v-model="form.hints[i]" type="text" class="field" :placeholder="`Petunjuk ${i + 1}`" />
                        <button type="button" class="rounded-lg p-2 text-muted hover:text-rose-600" aria-label="Hapus petunjuk" @click="form.hints.splice(i, 1)"><Icon name="x" :size="15" /></button>
                    </div>
                </div>
                <button v-if="form.hints.length < 5" type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:underline" @click="form.hints.push('')">+ Tambah petunjuk</button>
            </div>
        </div>

        <!-- Pengaturan -->
        <div>
            <div class="card space-y-4 p-5 xl:sticky xl:top-24">
                <h2 class="font-display font-bold text-ink">Pengaturan</h2>
                <p v-if="exercise" class="rounded-xl bg-chip/60 p-3 text-xs text-muted">
                    <span class="font-semibold text-ink">Version {{ exercise.version }}</span>
                    <template v-if="exercise.graded_on_version"> · {{ exercise.graded_on_version }} submissions. Changing the solution, tests or rules creates version {{ exercise.version + 1 }}; earlier scores stay on version {{ exercise.version }}.</template>
                </p>
                <div class="grid grid-cols-2 gap-3">
                    <Field id="concept" label="Konsep" :error="err('concept')"><input id="concept" v-model="form.concept" type="text" class="field" placeholder="perulangan" /></Field>
                    <Field id="level" label="Level (1–5)" :error="err('level')"><input id="level" v-model.number="form.level" type="number" min="1" max="5" class="field" /></Field>
                    <Field id="duration" label="Durasi (menit)" :error="err('duration_minutes')"><input id="duration" v-model.number="form.duration_minutes" type="number" min="1" class="field" /></Field>
                    <Field id="time_limit" label="Batas waktu (ms)" :error="err('time_limit_ms')"><input id="time_limit" v-model.number="form.time_limit_ms" type="number" min="500" max="10000" step="500" class="field" /></Field>
                </div>
                <Field id="show_solution_after" label="Solusi terbuka setelah gagal" :error="err('show_solution_after')" hint="Kosongkan agar solusi hanya terlihat setelah lulus.">
                    <div class="flex items-center gap-2"><input id="show_solution_after" v-model="form.show_solution_after" type="number" min="1" class="field w-24" /> <span class="text-sm text-muted">kali</span></div>
                </Field>
                <label v-if="form.mode === 'io'" class="flex items-center gap-2 text-sm text-ink"><input v-model="form.case_sensitive" type="checkbox" class="rounded border-line text-brand-600" /> Bedakan huruf besar/kecil</label>
                <label class="flex items-start gap-3 rounded-xl border border-line p-3">
                    <input v-model="form.is_published" type="checkbox" class="mt-0.5 rounded border-line text-brand-600 focus:ring-brand-300" />
                    <span><span class="block text-sm font-semibold text-ink">Terbitkan setelah lulus uji</span><span class="text-xs text-muted">Soal baru terlihat siswa bila solusi lulus semua tes.</span></span>
                </label>
                <button type="submit" class="btn-primary w-full" :disabled="form.processing"><Icon name="check" :size="18" /> Simpan & uji solusi</button>
            </div>
        </div>
    </form>
</template>
