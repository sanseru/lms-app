// Cek panduan uji coba: setiap gambar yang dirujuk README.md harus ada, dan setiap
// screenshot dari walkthrough (manifest.json) sebaiknya dipakai di README.md.
import { existsSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { ROOT } from './lib.mjs';

const dir = join(ROOT, 'docs/panduan-uji-coba');
const readme = readFileSync(join(dir, 'README.md'), 'utf8');
const used = [...readme.matchAll(/!\[[^\]]*\]\((img\/[^)]+)\)/g)].map((m) => m[1]);
const missing = used.filter((p) => !existsSync(join(dir, p)));
const shots = JSON.parse(readFileSync(join(dir, 'img/manifest.json'), 'utf8')).shots;
const unused = shots.filter((f) => !used.includes(`img/${f}`));

console.log(`Gambar dirujuk: ${used.length}, screenshot walkthrough: ${shots.length}`);
if (unused.length) console.warn(`Belum dipakai di README.md: ${unused.join(', ')}`);
if (missing.length) {
    console.error(`Gambar hilang: ${missing.join(', ')}`);
    process.exit(1);
}
console.log('OK: semua gambar yang dirujuk ada.');
