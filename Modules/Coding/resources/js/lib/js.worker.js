// Runs student JavaScript in a Web Worker: it cannot touch the page, and can be killed if it hangs.
const fmt = (v) => (typeof v === 'string' ? v : (() => { try { return JSON.stringify(v); } catch { return String(v); } })());

function runOne(code, stdin) {
    const out = [];
    const lines = (stdin ?? '').split('\n');
    let i = 0;
    const consoleProxy = { log: (...a) => out.push(a.map(fmt).join(' ')), error: (...a) => out.push(a.map(fmt).join(' ')), info: (...a) => out.push(a.map(fmt).join(' ')) };
    const input = () => {
        if (i >= lines.length) throw new Error('No more input left');
        return lines[i++];
    };
    try {
        // eslint-disable-next-line no-new-func
        new Function('console', 'input', 'prompt', '"use strict";\n' + code)(consoleProxy, input, input);
        return { output: out.join('\n'), error: null };
    } catch (e) {
        const line = String(e?.stack ?? '').match(/<anonymous>:(\d+):\d+/)?.[1];
        return { output: out.join('\n'), error: `${line ? `Line ${Number(line) - 3}: ` : ''}${e?.name ?? 'Error'}: ${e?.message ?? e}` };
    }
}

self.onmessage = ({ data }) => {
    const results = {};
    for (const job of data.jobs) results[job.key] = runOne(job.code, job.stdin);
    self.postMessage({ id: data.id, ok: true, results });
};
