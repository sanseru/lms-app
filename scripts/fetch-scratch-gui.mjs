// Pasang editor Scratch (build standalone @scratch/scratch-gui) ke public/vendor/scratch-gui
// agar dilayani dari server sendiri, sama seperti Pyodide. Paketnya besar (±150 MB unduhan,
// banyak dependensi React/webpack), jadi tidak masuk node_modules: tarball versi yang dikunci
// diunduh sekali lewat `npm pack`, hanya berkas build yang dipakai yang disalin.
//
// - Lewati unduhan bila versi yang sama sudah terpasang (berkas VERSION).
// - SCRATCH_GUI_SKIP=1 melewati semuanya (CI: test tidak butuh editor Scratch).
// - Gagal unduh (offline) tidak menggagalkan build: halaman Scratch menampilkan pesan.
import { execFileSync } from 'node:child_process';
import { copyFileSync, cpSync, existsSync, mkdirSync, mkdtempSync, readFileSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const VERSION = '15.2.0';
const PUBLIC_PATH = '/vendor/scratch-gui/';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const to = join(root, 'public/vendor/scratch-gui');
const host = join(root, 'resources/scratch/host.html');
const stamp = join(to, 'VERSION');

if (process.env.SCRATCH_GUI_SKIP === '1') {
    console.log('Scratch GUI: dilewati (SCRATCH_GUI_SKIP=1)');
    process.exit(0);
}

const installed = existsSync(stamp) && readFileSync(stamp, 'utf8').trim() === VERSION;

if (!installed) {
    const work = mkdtempSync(join(tmpdir(), 'scratch-gui-'));
    try {
        console.log(`Scratch GUI ${VERSION}: mengunduh paket…`);
        const npm = process.platform === 'win32' ? 'npm.cmd' : 'npm';
        execFileSync(npm, ['pack', `@scratch/scratch-gui@${VERSION}`, '--pack-destination', work, '--silent'], { stdio: ['ignore', 'ignore', 'inherit'], shell: process.platform === 'win32' });
        const tarball = readdirSync(work).find((f) => f.endsWith('.tgz'));
        if (!tarball) throw new Error('tarball tidak ditemukan');
        execFileSync('tar', ['-xzf', join(work, tarball), '-C', work]);

        const dist = join(work, 'package/dist');
        rmSync(to, { recursive: true, force: true });
        mkdirSync(to, { recursive: true });

        // Build berkas yang dimuat browser saja; source map (.map) dilewati.
        const noMaps = (src) => !src.endsWith('.map');
        for (const file of readdirSync(dist)) {
            const src = join(dist, file);
            if (file === 'types' || file === 'scratch-gui.js' || file.startsWith('scratch-gui.js.') || !noMaps(src)) continue;
            cpSync(src, join(to, file), { recursive: true, filter: noMaps });
        }

        // Build ini memuat chunk dan aset dari "/" (akar domain), termasuk loader webpack
        // bersarang milik scratch-storage (fetch-worker); arahkan semuanya ke folder vendor.
        const bundle = join(to, 'scratch-gui-standalone.js');
        const publicPath = /(__\w*webpack_require\w*__)\.p="\/"/g;
        const code = readFileSync(bundle, 'utf8');
        if (!publicPath.test(code)) throw new Error('publicPath build Scratch berubah, periksa skrip ini');
        writeFileSync(bundle, code.replace(publicPath, `$1.p="${PUBLIC_PATH}"`));
        writeFileSync(stamp, VERSION + '\n');
        console.log(`Scratch GUI ${VERSION} dipasang di public/vendor/scratch-gui`);
    } catch (e) {
        console.warn(`Scratch GUI: gagal dipasang (${e.message}). Editor Scratch tidak tersedia sampai skrip ini berhasil: node scripts/fetch-scratch-gui.mjs`);
    } finally {
        rmSync(work, { recursive: true, force: true });
    }
}

// Halaman host (milik aplikasi) selalu diperbarui, juga saat build Scratch sudah ada.
mkdirSync(to, { recursive: true });
copyFileSync(host, join(to, 'host.html'));
