// Runs Python in the browser with Pyodide (WebAssembly). One worker per page;
// if code takes too long, the page kills this worker and creates a new one.
let pyodide = null;

async function boot(indexURL) {
    if (pyodide) return;
    const { loadPyodide } = await import(/* @vite-ignore */ new URL('pyodide.mjs', new URL(indexURL, self.location.origin)).href);
    pyodide = await loadPyodide({ indexURL: new URL(indexURL, self.location.origin).href });
}

const PRELUDE = `
import sys, builtins
def __kid_input(prompt=''):
    line = sys.stdin.readline()
    if not line:
        raise EOFError('No more input left: the program asked for more input than it was given')
    return line.rstrip('\\n')
builtins.input = __kid_input
`;

function friendly(err) {
    const text = String(err?.message ?? err);
    const lines = text.trim().split('\n');
    const last = lines[lines.length - 1];
    const lineNo = [...text.matchAll(/File "<exec>", line (\d+)/g)].pop()?.[1];
    return lineNo ? `Line ${lineNo}: ${last}` : last;
}

async function runOne(code, stdin) {
    const out = [];
    const input = (stdin ?? '').split('\n');
    let i = 0;
    pyodide.setStdout({ batched: (s) => out.push(s) });
    pyodide.setStderr({ batched: (s) => out.push(s) });
    pyodide.setStdin({ stdin: () => (i < input.length ? input[i++] + '\n' : undefined), isatty: false });
    const ns = pyodide.globals.get('dict')();
    try {
        pyodide.runPython(PRELUDE, { globals: ns });
        await pyodide.runPythonAsync(code, { globals: ns, filename: '<exec>' });
        return { output: out.join('\n'), error: null };
    } catch (e) {
        return { output: out.join('\n'), error: friendly(e) };
    } finally {
        ns.destroy();
    }
}

self.onmessage = async ({ data }) => {
    try {
        if (data.type === 'boot') {
            await boot(data.indexURL);
            self.postMessage({ id: data.id, ok: true });
            return;
        }
        await boot(data.indexURL);
        const results = {};
        for (const job of data.jobs) {
            results[job.key] = await runOne(job.code, job.stdin);
        }
        self.postMessage({ id: data.id, ok: true, results });
    } catch (e) {
        self.postMessage({ id: data.id, ok: false, error: String(e?.message ?? e) });
    }
};
