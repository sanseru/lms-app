import { extractResult, jsHarness, pythonHarness } from './harness';

/**
 * Menjalankan kode siswa di browser. Python lewat Pyodide, JavaScript lewat Web Worker,
 * HTML/CSS di iframe sandbox. Kode tidak pernah dijalankan di server Laravel.
 */
let pyWorker = null;
let pyReady = null;
let seq = 0;

const makePyWorker = () => new Worker(new URL('./python.worker.js', import.meta.url), { type: 'module' });

function call(worker, message, timeoutMs, onTimeout) {
    return new Promise((resolve, reject) => {
        const id = ++seq;
        const timer = setTimeout(() => {
            worker.removeEventListener('message', handler);
            onTimeout?.();
            reject(new Error('timeout'));
        }, timeoutMs);
        const handler = ({ data }) => {
            if (data.id !== id) return;
            clearTimeout(timer);
            worker.removeEventListener('message', handler);
            data.ok ? resolve(data) : reject(new Error(data.error));
        };
        worker.addEventListener('message', handler);
        worker.postMessage({ ...message, id });
    });
}

/** Muat Pyodide lebih awal supaya tombol Jalankan terasa cepat. */
export function preparePython(indexURL) {
    if (!pyWorker) pyWorker = makePyWorker();
    if (!pyReady) {
        pyReady = call(pyWorker, { type: 'boot', indexURL }, 120000).catch((e) => {
            pyReady = null;
            throw e;
        });
    }
    return pyReady;
}

function resetPython() {
    pyWorker?.terminate();
    pyWorker = null;
    pyReady = null;
}

const timeoutResults = (jobs) => Object.fromEntries(jobs.map((j) => [j.key, { output: '', error: 'Waktu habis' }]));

async function runJobs(language, jobs, { timeLimitMs, indexURL }) {
    const budget = timeLimitMs * Math.max(1, jobs.length) + 500;
    if (language === 'python') {
        await preparePython(indexURL);
        try {
            return (await call(pyWorker, { type: 'run', jobs, indexURL }, budget, resetPython)).results;
        } catch (e) {
            if (e.message === 'timeout') return timeoutResults(jobs);
            throw e;
        }
    }
    const worker = new Worker(new URL('./js.worker.js', import.meta.url), { type: 'module' });
    try {
        return (await call(worker, { jobs }, budget)).results;
    } catch (e) {
        if (e.message === 'timeout') return timeoutResults(jobs);
        throw e;
    } finally {
        worker.terminate();
    }
}

/** Jalankan bebas (tanpa tes): untuk tombol "Jalankan". */
export async function runFree(language, code, stdin, opts) {
    return (await runJobs(language, [{ key: 'free', code, stdin }], opts)).free;
}

/**
 * Jalankan semua tes input→output / fungsi.
 * @returns {Promise<Record<number, {output: string, error: string|null}>>} output mentah per id tes
 */
export async function runTests(exercise, code, tests, opts) {
    const jobs = tests.map((t) => {
        if (exercise.mode === 'function') {
            const wrap = exercise.language === 'python' ? pythonHarness : jsHarness;
            return { key: t.id, code: wrap(code, exercise.function_name, t.input || '[]'), stdin: '' };
        }
        return { key: t.id, code, stdin: t.input ?? '' };
    });
    return runJobs(exercise.language, jobs, opts);
}

// ---------- Pemeriksaan lokal (hanya tes yang terlihat; nilai resmi dari server) ----------

const normLines = (s, cs) => {
    const lines = String(s ?? '').replace(/\r\n?/g, '\n').split('\n').map((l) => (cs ? l : l.toLowerCase()).trimEnd());
    while (lines.length && lines[lines.length - 1] === '') lines.pop();
    return lines;
};
const numEq = (a, b) => Math.abs(a - b) <= 1e-6 * Math.max(1, Math.abs(a));
const tokEq = (a, b) => a === b || (a !== '' && b !== '' && !isNaN(a) && !isNaN(b) && numEq(Number(a), Number(b)));

export function textEqual(expected, actual, caseSensitive) {
    const a = normLines(expected, caseSensitive);
    const b = normLines(actual, caseSensitive);
    if (a.length !== b.length) return false;
    return a.every((line, i) => {
        const x = line.split(/\s+/).filter(Boolean);
        const y = b[i].split(/\s+/).filter(Boolean);
        return x.length === y.length && x.every((t, j) => tokEq(t, y[j]));
    });
}

function deepEq(a, b) {
    if (typeof a === 'number' && typeof b === 'number') return numEq(a, b);
    if (Array.isArray(a) && Array.isArray(b)) return a.length === b.length && a.every((v, i) => deepEq(v, b[i]));
    if (a && b && typeof a === 'object' && typeof b === 'object' && !Array.isArray(a) && !Array.isArray(b)) {
        const ka = Object.keys(a);
        return ka.length === Object.keys(b).length && ka.every((k) => k in b && deepEq(a[k], b[k]));
    }
    return a === b;
}

export function jsonEqual(expected, actual) {
    try {
        return deepEq(JSON.parse(expected), JSON.parse(actual));
    } catch {
        return false;
    }
}

/** Ubah output mentah menjadi hasil per tes untuk tampilan (tes tersembunyi: belum diketahui). */
export function localResults(exercise, tests, outputs) {
    return tests.map((t) => {
        const raw = outputs[t.id] ?? { output: '', error: 'Tidak dijalankan' };
        const actual = exercise.mode === 'function' ? extractResult(raw.output) : raw.output;
        let passed = null;
        if (!t.is_hidden && t.expected != null) {
            passed = !raw.error && (exercise.mode === 'function' ? jsonEqual(t.expected, actual) : textEqual(t.expected, actual, exercise.case_sensitive));
        }
        return { id: t.id, passed, hidden: t.is_hidden, error: raw.error === 'Waktu habis' ? 'Waktu habis. Mungkin ada perulangan yang tidak pernah berhenti.' : raw.error, expected: t.expected, actual, input: t.input, weight: t.weight };
    });
}
