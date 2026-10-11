// Salin runtime Pyodide (Python di browser) ke public/ agar dilayani dari server sendiri, bukan CDN.
import { copyFileSync, existsSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const from = join(root, 'node_modules/pyodide');
const to = join(root, 'public/vendor/pyodide');
const files = ['pyodide.mjs', 'pyodide.asm.mjs', 'pyodide.asm.wasm', 'python_stdlib.zip', 'pyodide-lock.json'];

mkdirSync(to, { recursive: true });
for (const file of files) {
    if (!existsSync(join(from, file))) throw new Error(`Pyodide: berkas ${file} tidak ditemukan. Jalankan npm install.`);
    copyFileSync(join(from, file), join(to, file));
}
console.log(`Pyodide disalin ke public/vendor/pyodide (${files.length} berkas)`);
