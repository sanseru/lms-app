<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Lightbulb } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Badge from '@/Components/ui/Badge.vue';
import Icon from '@/Components/ui/Icon.vue';
import lessonLayout from '@/Layouts/lessonLayout';
import { useFullscreen } from '@/lib/fullscreen';
import LessonQuestions from '@/Components/ui/LessonQuestions.vue';
import LessonReport from '@/Components/ui/LessonReport.vue';
import CodeEditor from '../../Components/CodeEditor.vue';
import CodeLabel from '../../Components/CodeLabel.vue';
import ParsonsBoard from '../../Components/ParsonsBoard.vue';
import TestResultList from '../../Components/TestResultList.vue';
import { buildSrcdoc, checkHtml } from '../../lib/htmlCheck';
import { localResults, preparePython, runFree, runTests } from '../../lib/runner';

defineOptions({ layout: lessonLayout });

const props = defineProps({
    course: Object, lesson: Object, outline: Array, done: Array, prev: Object, next: Object, can: Object, reports: Object,
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
const pyReady = ref(ex.language !== 'python' || ex.mode === 'parsons');
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
            // The reason helps the teacher or admin find a server problem (missing files, wrong MIME type).
            console.error('Pyodide failed to load', e);
            errorMessage.value = `Python could not load in this browser. Try reloading the page. If it keeps happening, tell your teacher. (Details: ${e.message})`;
        } finally {
            busy.value = null;
        }
    }
});

// ----- HTML: live preview -----
const preview = ref(buildSrcdoc(code.value));
let previewTimer;
watch(code, (v) => {
    if (ex.mode !== 'html') return;
    clearTimeout(previewTimer);
    previewTimer = setTimeout(() => (preview.value = buildSrcdoc(v)), 400);
});

// ----- Run (local) -----
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
            // Teachers run every test; students only the sample tests (hidden test answers are not in the browser).
            const list = props.can.manage ? props.tests : visibleTests.value;
            const outputs = await runTests(ex, code.value, list, opts);
            results.value = localResults(ex, list.map((t) => ({ ...t, is_hidden: props.can.manage ? false : t.is_hidden })), outputs);
            const first = outputs[list[0]?.id];
            output.value = first ? { output: first.output.replace(/\n?__RESULT__.*$/s, ''), error: first.error } : null;
        }
        resultSource.value = 'local';
    } catch (e) {
        errorMessage.value = 'Your code could not run: ' + e.message;
    } finally {
        busy.value = null;
    }
}

// ----- Submit (graded on the server) -----
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
        errorMessage.value = 'Your code could not run: ' + e.message;
        busy.value = null;
    }
}

const ruleLabel = (t) => {
    const p = t.property ? ` ${t.property}` : '';
    return { exists: `Has a ${t.input} element`, text: `${t.input} contains "${t.expected}"`, attr: `${t.input} has the attribute${p}${t.expected ? ` = "${t.expected}"` : ''}`, count: `At least ${t.expected} ${t.input} elements`, style: `${t.input} uses${p}: ${t.expected}` }[t.kind] ?? t.kind;
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

// Fullscreen workspace: instructions + editor fill the screen while solving.
const workspace = ref(null);
const { active: fullscreen, native: nativeFullscreen, toggle: toggleFullscreen } = useFullscreen(workspace);
</script>

<template>
    <Head :title="lesson.title" />

    <Link v-if="!can.learn" :href="route('learning.courses.show', course.id)" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-muted hover:text-brand-600">
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
                <Badge v-if="passed" tone="green">Passed</Badge>
                <Badge v-if="can.manage && !ex.verified" tone="red">Solution not tested yet</Badge>
                <Badge v-if="can.manage && !lesson.is_published" tone="gray">Draft</Badge>
            </div>
        </div>
        <Link v-if="can.manage" :href="route('learning.lessons.edit', lesson.id)" class="btn-ghost"><Icon name="pencil" :size="16" /> Edit exercise</Link>
    </div>

    <div v-if="can.manage" class="mb-5 flex flex-wrap gap-2">
        <button
            v-for="(label, key) in { try: 'Try it', key: 'Answer key & tests', class: `Student work (${classroom.length})` }"
            :key="key"
            :class="tab === key ? 'bg-brand-600 text-white' : 'bg-white text-muted ring-1 ring-line hover:text-ink'"
            class="rounded-full px-4 py-2 text-sm font-semibold transition"
            @click="tab = key"
        >
            {{ label }}
        </button>
    </div>

    <!-- ================= Solve / try ================= -->
    <div
        v-show="tab === 'solve' || tab === 'try'"
        ref="workspace"
        :class="fullscreen ? ['bg-paper p-4 sm:p-6 lg:grid-cols-5 overflow-y-auto', !nativeFullscreen && 'fixed inset-0 z-[55]'] : 'xl:grid-cols-5'"
        class="grid gap-6"
    >
        <!-- Title bar, only while fullscreen -->
        <div v-if="fullscreen" class="flex items-center gap-3 lg:col-span-5">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-ink text-white"><Icon name="code" :size="18" /></span>
            <p class="min-w-0 flex-1 truncate font-display text-lg font-extrabold text-ink">{{ lesson.title }}</p>
            <button type="button" class="btn-ghost" @click="toggleFullscreen"><Icon name="minimize" :size="16" /> Exit fullscreen</button>
        </div>
        <!-- Exercise -->
        <div :class="fullscreen ? 'lg:col-span-2' : 'xl:col-span-2'" class="space-y-5">
            <article v-if="lesson.body_html" class="card lesson-prose p-6" v-html="lesson.body_html" />

            <div v-if="ex.mode === 'function'" class="card p-5 text-sm text-ink">
                <p>Write the function <code class="rounded bg-brand-50 px-1.5 py-0.5 font-mono text-brand-700">{{ ex.function_name }}</code>. We will call it with the arguments from each test and check the value it <i>returns</i>.</p>
            </div>
            <div v-else-if="ex.mode === 'io'" class="card p-5 text-sm text-ink">
                <p>The program reads input with <code class="font-mono">input()</code> and prints the result. Extra spaces at the end of a line are ignored, and upper/lowercase letters<template v-if="ex.case_sensitive"> <b>do matter</b></template><template v-else> don't matter</template>.</p>
            </div>
            <div v-else-if="ex.mode === 'parsons'" class="card p-5 text-sm text-ink">
                <p>Put the code lines alongside in the right order. Drag the lines, or use the arrow buttons.</p>
            </div>

            <!-- Sample tests -->
            <div v-if="ex.mode === 'io' || ex.mode === 'function'" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Sample tests</h2>
                <ul class="space-y-2">
                    <li v-for="(t, i) in visibleTests" :key="t.id" class="grid gap-2 rounded-xl bg-chip/60 p-3 text-xs sm:grid-cols-2">
                        <div><p class="text-muted">{{ ex.mode === 'function' ? `${ex.function_name}(…)` : 'Input' }} #{{ i + 1 }}</p><pre class="font-mono whitespace-pre-wrap text-ink">{{ ex.mode === 'function' ? t.input?.slice(1, -1) : t.input || '(no input)' }}</pre></div>
                        <div><p class="text-muted">Expected result</p><pre class="font-mono whitespace-pre-wrap text-ink">{{ t.expected }}</pre></div>
                    </li>
                </ul>
                <p v-if="hiddenCount && !can.manage" class="mt-3 flex items-center gap-1.5 text-xs text-muted"><Icon name="lock" :size="13" /> There {{ hiddenCount === 1 ? 'is 1 hidden test' : `are ${hiddenCount} hidden tests` }}, graded when you submit.</p>
            </div>
            <div v-if="ex.rules?.length" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Code rules</h2>
                <ul class="space-y-1.5 text-sm text-ink">
                    <li v-for="(r, i) in ex.rules" :key="i" class="flex items-start gap-2"><Icon name="list-checks" :size="15" class="mt-0.5 text-brand-500" /> <CodeLabel :text="r.label" /></li>
                </ul>
                <p class="mt-2 text-xs text-muted">Checked when you submit.</p>
            </div>
            <div v-if="ex.mode === 'html'" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">What gets checked</h2>
                <ul class="space-y-1.5 text-sm text-ink">
                    <li v-for="t in tests" :key="t.id" class="flex items-start gap-2"><Icon name="target" :size="15" class="mt-0.5 text-brand-500" /> {{ ruleLabel(t) }}</li>
                </ul>
            </div>

            <!-- Step-by-step hints -->
            <div v-if="ex.hints.length" class="card p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-display font-bold text-ink">Hints</h2>
                    <button v-if="hintsOpen < ex.hints.length" class="text-sm font-semibold text-brand-600 hover:underline" @click="hintsOpen++">Show hint {{ hintsOpen + 1 }}</button>
                </div>
                <ol class="mt-3 space-y-2">
                    <li v-for="(h, i) in ex.hints.slice(0, hintsOpen)" :key="i" class="flex gap-2 rounded-xl bg-amber-50 p-3 text-sm text-ink"><Lightbulb :size="16" class="mt-0.5 shrink-0 text-amber-600" aria-hidden="true" /><span>{{ h }}</span></li>
                </ol>
                <p v-if="!hintsOpen" class="mt-1 text-sm text-muted">Give it a go yourself first. Hints open one at a time.</p>
            </div>

            <!-- Solution -->
            <div v-if="solution" class="card p-5">
                <button class="flex w-full items-center justify-between font-display font-bold text-ink" @click="showSolution = !showSolution">
                    {{ can.manage ? 'Reference solution' : 'See solution' }} <Icon :name="showSolution ? 'chevron-down' : 'chevron-right'" :size="18" />
                </button>
                <CodeEditor v-if="showSolution" :model-value="solution" :language="ex.language" readonly min-height="120px" class="mt-3" label="Solution" />
            </div>
            <p v-else-if="can.learn && ex.show_solution_after" class="text-xs text-muted">The solution unlocks after {{ ex.show_solution_after }} tries.</p>

            <!-- History -->
            <div v-if="history?.length" class="card p-5">
                <h2 class="mb-3 font-display font-bold text-ink">Your history</h2>
                <ul class="space-y-1.5 text-sm">
                    <li v-for="h in history" :key="h.id" class="flex items-center justify-between gap-2">
                        <span class="text-muted">{{ h.at }}<span v-if="h.version !== ex.version" class="ml-1 rounded bg-chip px-1.5 py-0.5 text-[11px] font-semibold" :title="`Graded on version ${h.version}. The exercise has changed since; this score is kept.`">v{{ h.version }}</span></span>
                        <span class="text-xs text-muted">{{ h.passed_tests }}/{{ h.total_tests }} tests</span>
                        <Badge :tone="h.status === 'passed' ? 'green' : h.status === 'error' ? 'red' : 'amber'">{{ h.score }}</Badge>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Editor & hasil -->
        <div :class="fullscreen ? 'lg:col-span-3 [&_.cm-editor]:min-h-[60vh]! [&_.cm-scroller]:min-h-[60vh]!' : 'xl:col-span-3'" class="space-y-4">
            <div v-if="scoreBanner && can.learn" :class="scoreBanner.status === 'passed' ? 'bg-emerald-700' : 'bg-amber-700'" role="status" class="flex items-center gap-4 rounded-3xl p-5 text-white">
                <span class="grid size-16 place-items-center rounded-full bg-white/20 font-display text-2xl font-black ring-4 ring-white/40 tabular-nums">{{ scoreBanner.score }}</span>
                <div>
                    <p class="font-display text-xl font-extrabold">{{ scoreBanner.status === 'passed' ? 'All tests passed!' : scoreBanner.status === 'error' ? 'There is an error, let\'s fix it' : 'So close! Try again' }}</p>
                    <p class="text-sm text-white">{{ scoreBanner.passed_tests }} of {{ scoreBanner.total_tests }} tests passed · graded {{ scoreBanner.at }}</p>
                </div>
            </div>

            <ParsonsBoard v-if="ex.mode === 'parsons'" v-model="lines" :results="resultSource === 'server' ? displayResults : null" :disabled="!!busy" />
            <CodeEditor v-else v-model="code" :language="ex.language" min-height="320px" @run="run" />

            <div class="flex flex-wrap items-center gap-2">
                <button v-if="ex.mode !== 'parsons'" class="btn-ghost" :disabled="!!busy || !pyReady" @click="run">
                    <Icon name="play" :size="16" /> {{ busy === 'run' ? 'Running…' : ex.mode === 'html' ? 'Check my page' : 'Run sample tests' }}
                </button>
                <button v-if="can.learn" class="btn-accent" :disabled="!!busy || !pyReady" @click="submit">
                    <Icon name="check" :size="16" /> {{ busy === 'submit' ? 'Grading…' : 'Submit' }}
                </button>
                <button class="btn-ghost ml-auto px-3" :disabled="!!busy" title="Back to starter code" aria-label="Back to starter code" @click="reset"><Icon name="rotate-ccw" :size="16" /></button>
                <button class="btn-ghost px-3" :title="fullscreen ? 'Exit fullscreen' : 'Fullscreen'" :aria-label="fullscreen ? 'Exit fullscreen' : 'Fullscreen'" :aria-pressed="fullscreen" @click="toggleFullscreen"><Icon :name="fullscreen ? 'minimize' : 'maximize'" :size="16" /></button>
                <span v-if="busy === 'boot'" class="flex w-full items-center gap-2 text-xs text-muted"><span class="size-3 animate-spin rounded-full border-2 border-brand-300 border-t-transparent" /> Getting Python ready in your browser (only slow the first time)…</span>
                <span v-else-if="ex.mode !== 'parsons'" class="w-full text-xs text-muted">Tip: press Ctrl+Enter to run.<template v-if="fullscreen"> Press Esc to leave fullscreen.</template></span>
            </div>

            <p v-if="errorMessage" class="rounded-xl bg-rose-50 p-3 text-sm text-rose-700">{{ errorMessage }}</p>

            <details v-if="ex.mode === 'io'" class="card p-4 text-sm">
                <summary class="cursor-pointer font-semibold text-ink">Try your own input</summary>
                <textarea v-model="stdin" rows="3" class="field mt-3 font-mono text-sm" placeholder="One value per line" />
                <p class="mt-1 text-xs text-muted">When filled in, the Run button uses this input.</p>
            </details>

            <div v-if="ex.mode === 'html'" class="card overflow-hidden">
                <p class="border-b border-line px-4 py-2 text-xs font-semibold text-muted">Preview</p>
                <iframe :srcdoc="preview" sandbox="allow-scripts" title="Page preview" class="h-72 w-full bg-white" />
            </div>

            <div v-if="output" class="overflow-hidden rounded-2xl bg-[#1d1528] text-[#ebe3f7]">
                <p class="border-b border-white/10 px-4 py-2 text-xs font-semibold text-white/60">Output</p>
                <pre class="max-h-60 overflow-auto p-4 font-mono text-sm whitespace-pre-wrap">{{ output.output || '(no output)' }}</pre>
                <p v-if="output.error" class="border-t border-white/10 px-4 py-2 font-mono text-xs text-rose-300">{{ output.error }}</p>
            </div>

            <div v-if="displayResults && ex.mode !== 'parsons'">
                <p class="mb-2 text-xs font-semibold text-muted">{{ resultSource === 'server' ? 'Graded result' : 'Practice run (not graded yet)' }}</p>
                <TestResultList :results="displayResults" :mode="ex.mode" />
            </div>
            <p v-if="ex.mode === 'parsons' && scoreBanner && scoreBanner.status !== 'passed'" class="text-sm text-muted">Red lines are not in the right spot yet.</p>
        </div>
    </div>

    <!-- ================= Answer key & tests (teacher) ================= -->
    <div v-if="can.manage && tab === 'key'" class="grid gap-6 lg:grid-cols-2">
        <div class="card p-5">
            <h2 class="mb-3 font-display font-bold text-ink">Reference solution</h2>
            <CodeEditor :model-value="solution" :language="ex.language" readonly min-height="160px" label="Solution" />
        </div>
        <div class="card overflow-hidden">
            <h2 class="border-b border-line px-5 py-4 font-display font-bold text-ink">Tests ({{ tests.length }})</h2>
            <ul class="divide-y divide-line">
                <li v-for="(t, i) in tests" :key="t.id" class="px-5 py-3 text-sm">
                    <p class="flex items-center gap-2 font-semibold text-ink">
                        {{ ex.mode === 'html' ? ruleLabel(t) : `Test ${i + 1}` }}
                        <Badge v-if="t.is_hidden" tone="amber">hidden</Badge>
                        <span v-if="t.weight > 1" class="text-xs text-muted">weight {{ t.weight }}</span>
                    </p>
                    <div v-if="ex.mode !== 'html'" class="mt-1 grid gap-2 sm:grid-cols-2">
                        <pre class="rounded-lg bg-chip/60 p-2 font-mono text-xs whitespace-pre-wrap">{{ t.input || '(no input)' }}</pre>
                        <pre class="rounded-lg bg-emerald-50 p-2 font-mono text-xs whitespace-pre-wrap">{{ t.expected }}</pre>
                    </div>
                    <p v-if="t.fail_message" class="mt-1 text-xs text-muted">Message if it fails: {{ t.fail_message }}</p>
                </li>
                <li v-if="!tests.length" class="px-5 py-6 text-center text-sm text-muted">Arrange-the-lines exercises are graded on the order of the solution lines.</li>
            </ul>
        </div>
    </div>

    <!-- ================= Student work (Zoom class mode) ================= -->
    <div v-if="can.manage && tab === 'class'" class="space-y-3">
        <p class="text-sm text-muted">Each student's latest code, handy for going through together in a Zoom session.</p>
        <details v-for="row in classroom" :key="row.student.id" class="card overflow-hidden">
            <summary class="flex cursor-pointer items-center gap-3 px-5 py-3">
                <Avatar :initials="row.student.initials" :seed="row.student.id" size="sm" />
                <span class="flex-1 font-semibold text-ink">{{ row.student.name }}</span>
                <span class="text-xs text-muted">{{ row.tries }}× · latest {{ row.latest.at }}</span>
                <Badge :tone="row.passed ? 'green' : 'amber'">{{ row.best }}</Badge>
            </summary>
            <div class="border-t border-line p-4">
                <pre class="max-h-80 overflow-auto rounded-xl bg-[#282c34] p-4 font-mono text-sm whitespace-pre text-[#e6e6e6]">{{ row.latest.code }}</pre>
            </div>
        </details>
        <p v-if="!classroom.length" class="card px-5 py-8 text-center text-sm text-muted">No students have submitted yet.</p>
    </div>

    <LessonReport :lesson-id="lesson.id" :can="can" :reports="reports" class="mt-6" />
    <LessonQuestions :lesson-id="lesson.id" class="mt-8" />

    <nav class="mt-8 flex items-center justify-between gap-3 border-t border-line pt-5">
        <Link v-if="prev" :href="route('learning.lessons.show', prev.id)" class="group flex min-w-0 items-center gap-2 text-sm">
            <Icon name="arrow-left" :size="16" class="text-muted group-hover:text-brand-600" />
            <span class="min-w-0"><span class="block text-xs text-muted">Previous</span><span class="block truncate font-semibold text-ink group-hover:text-brand-600">{{ prev.title }}</span></span>
        </Link>
        <span v-else />
        <Link v-if="next" :href="route('learning.lessons.show', next.id)" class="group flex min-w-0 items-center gap-2 text-right text-sm">
            <span class="min-w-0"><span class="block text-xs text-muted">Next</span><span class="block truncate font-semibold text-ink group-hover:text-brand-600">{{ next.title }}</span></span>
            <Icon name="arrow-right" :size="16" class="text-muted group-hover:text-brand-600" />
        </Link>
    </nav>
</template>
