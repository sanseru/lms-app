<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import LessonQuestions from '@/Components/ui/LessonQuestions.vue';
import CodeEditor from '../../Components/CodeEditor.vue';
import ParsonsBoard from '../../Components/ParsonsBoard.vue';
import TestResultList from '../../Components/TestResultList.vue';
import { buildSrcdoc, checkHtml } from '../../lib/htmlCheck';
import { localResults, preparePython, runFree, runTests } from '../../lib/runner';

const props = defineProps({
    course: Object, lesson: Object, outline: Array, done: Array, prev: Object, next: Object, can: Object,
    exercise: Object, tests: Array, solution: String, lastResult: Object, history: Array, passed: Boolean, runtime: Object, classroom: Array,
});

const ex = props.exercise;
const opts = { timeLimitMs: ex.time_limit_ms, indexURL: props.runtime.pyodide_url };
const storageKey = `alp-code-${props.lesson.id}`;
const saved = (() => { try { return localStorage.getItem(storageKey); } catch { return null; } })();

const code = ref(props.can.learn && props.lastResult?.code && ex.mode !== 'parsons' ? props.lastResult.code : saved ?? ex.starter_code);
const lines = ref(ex.mode === 'parsons'
    ? (props.can.learn && props.lastResult?.code ? props.lastResult.code.split('\n') : [...(ex.parsons_lines ?? props.solution?.split('\n').filter((l) => l.trim()) ?? [])])
    : []);
watch(code, (v) => { try { localStorage.setItem(storageKey, v); } catch {} });

const busy = ref(null); // 'boot' | 'run' | 'submit'
const pyReady = ref(ex.language !== 'python');
const output = ref(null);
const stdin = ref('');
const results = ref(props.lastResult?.results ?? null);
const resultSource = ref(props.lastResult ? 'server' : null);
const hintsOpen = ref(0);
const showSolution = ref(false);
const tab = ref(props.can.manage ? 'try' : 'solve');

const visibleTests = computed(() => props.tests.filter((t) => !t.is_hidden));
const hiddenCount = computed(() => props.tests.filter((t) => t.is_hidden).length);
const errorMessage = ref(null);

onMounted(async () => {
    if (ex.language === 'python' && ex.mode !== 'parsons') {
        busy.value = 'boot';
        try {
            await preparePython(opts.indexURL);
            pyReady.value = true;
        } catch (e) {
            errorMessage.value = 'Python belum bisa dimuat di browser ini. Coba muat ulang halaman.';
        } finally {
            busy.value = null;
        }
    }
});

// ----- HTML: pratinjau langsung -----
const preview = ref(buildSrcdoc(code.value));
let previewTimer;
watch(code, (v) => {
    if (ex.mode !== 'html') return;
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => (preview.value = buildSrcdoc(v)), 400);
});

// ----- Jalankan (lokal) -----
async function run() {
    if (busy.value) return;
    errorMessage.value = null;
    busy.value = 'run';
    try {
        if (ex.mode === 'html') {
            const checks = await checkHtml(code.value, props.tests);
            results.value = props.tests.map((t) => ({ id: t.id, passed: checks[t.id], hidden: false, label: ruleLabel(t), weight: t.weight }));
        } else if (ex.mode === 'io' && stdin.value !== '') {
            const r = await runFree(ex.language, code.value, stdin.value, opts);
            output.value = r;
            return;
        } else {
            // Tutor menguji semua tes; siswa hanya tes contoh (jawaban tes tersembunyi tidak ada di browser).
            const list = props.can.manage ? props.tests : visibleTests.value;
            const outputs = await runTests(ex, code.value, list, opts);
            results.value = localResults(ex, list.map((t) => ({ ...t, is_hidden: props.can.manage ? false : t.is_hidden })), outputs);
            const first = outputs[list[0]?.id];
            output.value = first ? { output: first.output.replace(/\n?__RESULT__.*$/s, ''), error: first.error } : null;
        }
        resultSource.value = 'local';
    } catch (e) {
        errorMessage.value = 'Kode tidak bisa dijalankan: ' + e.message;
    } finally {
        busy.value = null;
    }
}

// ----- Kumpulkan (dinilai server) -----
async function submit() {
    if (busy.value) return;
    errorMessage.value = null;
    busy.value = 'submit';
    try {
        let outputs = {};
        let styles = {};
        const submitted = ex.mode === 'parsons' ? lines.value.join('\n') : code.value;
        if (ex.mode === 'html') styles = await checkHtml(code.value, props.tests);
        else if (ex.mode !== 'parsons' && props.runtime.driver === 'browser') outputs = await runTests(ex, code.value, props.tests, opts);

        router.post(route('coding.exercises.submit', props.lesson.id), { code: submitted, outputs, styles, hints_used: hintsOpen.value }, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                results.value = page.props.lastResult?.results ?? null;
                resultSource.value = 'server';
            },
            onFinish: () => (busy.value = null),
        });
    } catch (e) {
        errorMessage.value = 'Kode tidak bisa dijalankan: ' + e.message;
        busy.value = null;
    }
}

const ruleLabel = (t) => {
    const p = t.property ? ` ${t.property}` : '';
    return { exists: `Ada elemen ${t.input}`, text: `${t.input} berisi "${t.expected}"`, attr: `${t.input} punya atribut${p}${t.expected ? ` = "${t.expected}"` : ''}`, count: `Minimal ${t.expected} elemen ${t.input}`, style: `${t.input} memakai${p}: ${t.expected}` }[t.kind] ?? t.kind;
};
const displayResults = computed(() => {
    if (!results.value) return null;
    return results.value.map((r) => {
        const t = props.tests.find((x) => x.id === r.id);
        return ex.mode === 'html' && t ? { ...r, label: ruleLabel(t) } : r;
    });
});
const scoreBanner = computed(() => (resultSource.value === 'server' ? props.lastResult : null));
const reset = () => {
    if (ex.mode === 'parsons') lines.value = [...ex.parsons_lines];
    else code.value = ex.starter_code;
    results.value = null;
    output.value = null;
};
const isDone = computed(() => props.done?.includes(props.lesson.id));
const user = usePage().props.auth.user;
</script>

<template>
    <Head :title="lesson.title" />

    <Link :href="route('learning.courses.show', course.id)" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
        <Icon name="arrow-left" :size="16" /> {{ course.title }}
    </Link>

    <div class="mb-5 flex flex-wrap items-start gap-4">
        <span :class="isDone ? 'bg-emerald-500 text-white' : 'bg-ink text-white'" class="grid size-12 shrink-0 place-items-center rounded-2xl">
            <Icon :name="isDone ? 'check' : 'code'" :size="22" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="eyebrow">Coding · {{ ex.language_label }} · {{ ex.mode_label }}</p>
            <h1 class="text-2xl font-extrabold tracking-tight text-ink sm:text-3xl">{{ lesson.title }}</h1>
            <div class="mt-2 flex flex-wrap gap-2">
                <Badge v-if="ex.concept" tone="brand">{{ ex.concept }}</Badge>
                <Badge v-if="ex.level" tone="gray">Level {{ ex.level }}</Badge>
                <Badge v-if="passed" tone="green">Sudah lulus</Badge>
                <Badge v-if="can.manage && !ex.verified" tone="red">Belum lulus Uji solusi</Badge>
                <Badge v-if="can.manage && !lesson.is_published" tone="gray">Draf</Badge>
            </div>
        </div>
        <Link v-if="can.manage" :href="route('learning.lessons.edit', lesson.id)" class="btn-ghost"><Icon name="pencil" :size="16" /> Ubah soal</Link>
    </div>

    <div v-if="can.manage" class="mb-5 flex flex-wrap gap-2">
        <button
            v-for="(label, key) in { try: 'Coba soal', key: 'Kunci & tes', class: `Pekerjaan siswa (${classroom.length})` }"
            :key="key"
            :class="tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="rounded-full px-4 py-2 text-sm font-semibold transition"
            @click="tab = key"
        >
            {{ label }}
        </button>
    </div>

    <!-- ================= Mengerjakan / mencoba ================= -->
    <div v-show="tab === 'solve' || tab === 'try'" class="grid gap-6 xl:grid-cols-5">
        <!-- Soal -->
        <div class="space-y-5 xl:col-span-2">
            <article v-if="lesson.body_html" class="card lesson-prose p-6" v-html="lesson.body_html" />

            <div v-if="ex.mode === 'function'" class="card p-5 text-sm text-ink">
                <p>Tulis fungsi <code class="rounded bg-brand-50 px-1.5 py-0.5 font-mono text-brand-700">{{ ex.function_name }}</code>. Sistem akan memanggilnya dengan argumen dari setiap tes dan mencocokkan nilai yang di-<i>return</i>.</p>
            </div>
            <div v-else-if="ex.mode === 'io'" class="card p-5 text-sm text-ink">
                <p>Program membaca input dengan <code class="font-mono">input()</code> lalu mencetak hasilnya. Spasi di akhir baris dan huruf besar/kecil<template v-if="ex.case_sensitive"> <b>diperhatikan</b></template><template v-else> tidak dipermasalahkan</template>.</p>
            </div>
            <div v-else-if="ex.mode === 'parsons'" class="card p-5 text-sm text-ink">
                <p>Susun baris-baris kode di samping sampai urutannya benar. Seret barisnya, atau pakai tombol panah.</p>
            </div>

            <!-- Tes contoh -->
            <div v-if="ex.mode === 'io' || ex.mode === 'function'" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Tes contoh</h2>
                <ul class="space-y-2">
                    <li v-for="(t, i) in visibleTests" :key="t.id" class="grid gap-2 rounded-xl bg-chip/60 p-3 text-xs sm:grid-cols-2">
                        <div><p class="text-muted">{{ ex.mode === 'function' ? `${ex.function_name}(…)` : 'Input' }} #{{ i + 1 }}</p><pre class="font-mono whitespace-pre-wrap text-ink">{{ ex.mode === 'function' ? t.input?.slice(1, -1) : t.input || '(tanpa input)' }}</pre></div>
                        <div><p class="text-muted">Hasil yang diharapkan</p><pre class="font-mono whitespace-pre-wrap text-ink">{{ t.expected }}</pre></div>
                    </li>
                </ul>
                <p v-if="hiddenCount && !can.manage" class="mt-3 flex items-center gap-1.5 text-xs text-muted"><Icon name="lock" :size="13" /> Ada {{ hiddenCount }} tes tersembunyi yang dinilai saat kamu mengumpulkan.</p>
            </div>
            <div v-if="ex.mode === 'html'" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Yang dicek</h2>
                <ul class="space-y-1.5 text-sm text-ink">
                    <li v-for="t in tests" :key="t.id" class="flex items-start gap-2"><Icon name="target" :size="15" class="mt-0.5 text-brand-500" /> {{ ruleLabel(t) }}</li>
                </ul>
            </div>

            <!-- Petunjuk bertingkat -->
            <div v-if="ex.hints.length" class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display font-bold text-ink">Petunjuk</h2>
                    <button v-if="hintsOpen < ex.hints.length" class="text-sm font-semibold text-brand-600 hover:underline" @click="hintsOpen++">Buka petunjuk {{ hintsOpen + 1 }}</button>
                </div>
                <ol class="mt-3 space-y-2">
                    <li v-for="(h, i) in ex.hints.slice(0, hintsOpen)" :key="i" class="rounded-xl bg-amber-50 p-3 text-sm text-ink">💡 {{ h }}</li>
                </ol>
                <p v-if="!hintsOpen" class="mt-1 text-sm text-muted">Coba sendiri dulu. Petunjuk terbuka satu per satu.</p>
            </div>

            <!-- Solusi -->
            <div v-if="solution" class="card p-5">
                <button class="flex w-full items-center justify-between font-display font-bold text-ink" @click="showSolution = !showSolution">
                    {{ can.manage ? 'Solusi referensi' : 'Lihat solusi' }} <Icon :name="showSolution ? 'chevron-down' : 'chevron-right'" :size="18" />
                </button>
                <CodeEditor v-if="showSolution" :model-value="solution" :language="ex.language" readonly min-height="120px" class="mt-3" label="Solusi" />
            </div>
            <p v-else-if="can.learn && ex.show_solution_after" class="text-xs text-muted">Solusi bisa dilihat setelah {{ ex.show_solution_after }}× mencoba.</p>

            <!-- Riwayat -->
            <div v-if="history?.length" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Riwayatmu</h2>
                <ul class="space-y-1.5 text-sm">
                    <li v-for="h in history" :key="h.id" class="flex items-center justify-between gap-2">
                        <span class="text-muted">{{ h.at }}</span>
                        <span class="text-xs text-muted">{{ h.passed_tests }}/{{ h.total_tests }} tes</span>
                        <Badge :tone="h.status === 'passed' ? 'green' : h.status === 'error' ? 'red' : 'amber'">{{ h.score }}</Badge>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Editor & hasil -->
        <div class="space-y-4 xl:col-span-3">
            <div v-if="scoreBanner && can.learn" :class="scoreBanner.status === 'passed' ? 'bg-emerald-700' : 'bg-amber-700'" role="status" class="flex items-center gap-4 rounded-3xl p-5 text-white">
                <span class="grid size-16 place-items-center rounded-full bg-white/20 font-display text-2xl font-black ring-4 ring-white/40 tabular-nums">{{ scoreBanner.score }}</span>
                <div>
                    <p class="font-display text-xl font-extrabold">{{ scoreBanner.status === 'passed' ? 'Semua tes lulus! 🎉' : scoreBanner.status === 'error' ? 'Ada error, ayo perbaiki' : 'Hampir! Coba lagi' }}</p>
                    <p class="text-sm text-white">{{ scoreBanner.passed_tests }} dari {{ scoreBanner.total_tests }} tes lulus · dinilai {{ scoreBanner.at }}</p>
                </div>
            </div>

            <ParsonsBoard v-if="ex.mode === 'parsons'" v-model="lines" :results="resultSource === 'server' ? displayResults : null" :disabled="!!busy" />
            <CodeEditor v-else v-model="code" :language="ex.language" min-height="320px" @run="run" />

            <div class="flex flex-wrap items-center gap-2">
                <button v-if="ex.mode !== 'parsons'" class="btn-ghost" :disabled="!!busy || !pyReady" @click="run">
                    <Icon name="play" :size="16" /> {{ busy === 'run' ? 'Menjalankan…' : ex.mode === 'html' ? 'Cek tampilan' : 'Jalankan tes contoh' }}
                </button>
                <button v-if="can.learn" class="btn-accent" :disabled="!!busy || !pyReady" @click="submit">
                    <Icon name="check" :size="16" /> {{ busy === 'submit' ? 'Menilai…' : 'Kumpulkan' }}
                </button>
                <button class="btn-ghost ml-auto px-3" :disabled="!!busy" title="Kembali ke kode awal" @click="reset"><Icon name="rotate-ccw" :size="16" /></button>
                <span v-if="busy === 'boot'" class="flex w-full items-center gap-2 text-xs text-muted"><span class="size-3 animate-spin rounded-full border-2 border-brand-300 border-t-transparent" /> Menyiapkan Python di browser (hanya lama pertama kali)…</span>
                <span v-else-if="ex.mode !== 'parsons'" class="w-full text-xs text-muted">Tips: tekan Ctrl+Enter untuk menjalankan.</span>
            </div>

            <p v-if="errorMessage" class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ errorMessage }}</p>

            <details v-if="ex.mode === 'io'" class="card p-4 text-sm">
                <summary class="cursor-pointer font-semibold text-ink">Coba dengan input sendiri</summary>
                <textarea v-model="stdin" rows="3" class="field mt-3 font-mono text-sm" placeholder="Satu nilai per baris" />
                <p class="mt-1 text-xs text-muted">Bila diisi, tombol Jalankan memakai input ini.</p>
            </details>

            <div v-if="ex.mode === 'html'" class="card overflow-hidden">
                <p class="border-b border-line px-4 py-2 text-xs font-semibold text-muted">Pratinjau</p>
                <iframe :srcdoc="preview" sandbox="allow-scripts" title="Pratinjau halaman" class="h-72 w-full bg-white" />
            </div>

            <div v-if="output" class="overflow-hidden rounded-2xl bg-[#1d1528] text-[#ebe3f7]">
                <p class="border-b border-white/10 px-4 py-2 text-xs font-semibold text-white/60">Output</p>
                <pre class="max-h-60 overflow-auto p-4 font-mono text-sm whitespace-pre-wrap">{{ output.output || '(tidak ada output)' }}</pre>
                <p v-if="output.error" class="border-t border-white/10 px-4 py-2 font-mono text-xs text-rose-300">{{ output.error }}</p>
            </div>

            <div v-if="displayResults && ex.mode !== 'parsons'">
                <p class="mb-2 text-xs font-semibold text-muted">{{ resultSource === 'server' ? 'Hasil penilaian' : 'Hasil uji coba (belum dinilai)' }}</p>
                <TestResultList :results="displayResults" :mode="ex.mode" />
            </div>
            <p v-if="ex.mode === 'parsons' && scoreBanner && scoreBanner.status !== 'passed'" class="text-sm text-muted">Baris merah belum di posisi yang benar.</p>
        </div>
    </div>

    <!-- ================= Kunci & tes (tutor) ================= -->
    <div v-if="can.manage && tab === 'key'" class="grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="mb-3 font-display font-bold text-ink">Solusi referensi</h2>
            <CodeEditor :model-value="solution" :language="ex.language" readonly min-height="160px" label="Solusi" />
        </div>
        <div class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-display font-bold text-ink">Tes ({{ tests.length }})</h2>
            <ul class="divide-y divide-line">
                <li v-for="(t, i) in tests" :key="t.id" class="px-5 py-3 text-sm">
                    <p class="flex items-center gap-2 font-semibold text-ink">
                        {{ ex.mode === 'html' ? ruleLabel(t) : `Tes ${i + 1}` }}
                        <Badge v-if="t.is_hidden" tone="amber">tersembunyi</Badge>
                        <span v-if="t.weight > 1" class="text-xs text-muted">bobot {{ t.weight }}</span>
                    </p>
                    <div v-if="ex.mode !== 'html'" class="mt-1 grid gap-2 sm:grid-cols-2">
                        <pre class="rounded-lg bg-chip/60 p-2 font-mono text-xs whitespace-pre-wrap">{{ t.input || '(tanpa input)' }}</pre>
                        <pre class="rounded-lg bg-emerald-50 p-2 font-mono text-xs whitespace-pre-wrap">{{ t.expected }}</pre>
                    </div>
                    <p v-if="t.fail_message" class="mt-1 text-xs text-muted">Pesan bila gagal: {{ t.fail_message }}</p>
                </li>
                <li v-if="!tests.length" class="px-5 py-6 text-center text-sm text-muted">Soal susun baris dinilai dari urutan baris solusi.</li>
            </ul>
        </div>
    </div>

    <!-- ================= Pekerjaan siswa (mode kelas Zoom) ================= -->
    <div v-if="can.manage && tab === 'class'" class="space-y-3">
        <p class="text-sm text-muted">Kode terbaru tiap siswa, cocok untuk dibahas bersama saat sesi Zoom.</p>
        <details v-for="row in classroom" :key="row.student.id" class="card overflow-hidden">
            <summary class="flex cursor-pointer items-center gap-3 px-5 py-3">
                <Avatar :initials="row.student.initials" :seed="row.student.id" size="sm" />
                <span class="flex-1 font-semibold text-ink">{{ row.student.name }}</span>
                <span class="text-xs text-muted">{{ row.tries }}× · terakhir {{ row.latest.at }}</span>
                <Badge :tone="row.passed ? 'green' : 'amber'">{{ row.best }}</Badge>
            </summary>
            <div class="border-t border-line p-4">
                <pre class="max-h-80 overflow-auto rounded-xl bg-[#282c34] p-4 font-mono text-sm whitespace-pre text-[#e6e6e6]">{{ row.latest.code }}</pre>
            </div>
        </details>
        <p v-if="!classroom.length" class="card px-5 py-8 text-center text-sm text-muted">Belum ada siswa yang mengumpulkan.</p>
    </div>

    <LessonQuestions :lesson-id="lesson.id" class="mt-8" />

    <nav class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-5">
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
</template>
