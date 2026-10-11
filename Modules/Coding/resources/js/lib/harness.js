// Pembungkus kode siswa untuk mode "fungsi". Harus sama dengan Modules/Coding/Grading/Harness.php.
export const pythonHarness = (code, fn, argsJson) =>
    `${code}\n\nimport json as __json\nprint('\\n__RESULT__' + __json.dumps(${fn}(*__json.loads(${JSON.stringify(argsJson)}))))\n`;

export const jsHarness = (code, fn, argsJson) => `${code}\n;console.log('\\n__RESULT__' + JSON.stringify(${fn}(...${argsJson})));\n`;

export const extractResult = (output) => {
    if (output == null || !output.includes('__RESULT__')) return null;
    return output.slice(output.lastIndexOf('__RESULT__') + 10).trim();
};
