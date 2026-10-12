// Helper untuk skrip walkthrough panduan uji coba klien (lihat walkthrough.mjs).
import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

export const ROOT = join(dirname(fileURLToPath(import.meta.url)), '../..');
export const BASE = process.env.APP_URL ?? 'http://127.0.0.1:8000';
export const OUT = process.env.OUT_DIR ?? join(ROOT, 'docs/panduan-uji-coba/img');
export const PASSWORD = 'password';

const shots = [];
let browser;

export async function start() {
    mkdirSync(OUT, { recursive: true });
    mkdirSync(join(OUT, '..', 'dist'), { recursive: true });
    browser = await chromium.launch();
}

/** Halaman baru yang sudah login sebagai `username` (null = tamu). */
export async function as(username, { mobile = false } = {}) {
    const context = await browser.newContext(
        mobile
            ? { viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true, deviceScaleFactor: 2 }
            : { viewport: { width: 1366, height: 900 } },
    );
    const page = await context.newPage();
    page.on('pageerror', (e) => console.warn(`  ! error di browser (${username ?? 'tamu'}): ${e.message.slice(0, 160)}`));
    page.on('dialog', (d) => d.accept());
    if (username) {
        await page.goto(`${BASE}/login`);
        await page.fill('#login', username);
        await page.fill('#password', PASSWORD);
        await Promise.all([page.waitForURL('**/dashboard'), page.click('button[type=submit]')]);
    }
    return page;
}

/** Buka path dan tunggu Inertia selesai memuat. */
export async function go(page, path) {
    const res = await page.goto(BASE + path, { waitUntil: 'networkidle' });
    if (res && res.status() >= 400) throw new Error(`${path} -> HTTP ${res.status()}`);
    await page.waitForTimeout(300);
}

/**
 * Simpan screenshot JPEG bernomor. `full` = seluruh halaman (maks. 2400 px),
 * `el` = hanya satu elemen (selector atau locator).
 */
export async function shot(page, name, { full = false, el = null } = {}) {
    await page.waitForTimeout(250);
    const file = `${name}.jpg`;
    const opts = { path: join(OUT, file), type: 'jpeg', quality: 78 };
    if (el) {
        await (typeof el === 'string' ? page.locator(el).first() : el).screenshot(opts);
    } else if (full) {
        const h = await page.evaluate(() => document.documentElement.scrollHeight);
        const vp = page.viewportSize();
        await page.setViewportSize({ width: vp.width, height: Math.min(Math.max(h, vp.height), 2400) });
        await page.waitForTimeout(250);
        await page.screenshot(opts);
        await page.setViewportSize(vp);
    } else {
        await page.screenshot(opts);
    }
    shots.push(file);
    console.log(`  ✓ ${file}`);
}

/** Jalankan satu langkah; gagal = berhenti dengan nama langkahnya. */
export async function step(title, fn) {
    console.log(`• ${title}`);
    try {
        await fn();
    } catch (e) {
        console.error(`✗ Gagal di langkah "${title}": ${e.message}`);
        await browser?.close();
        process.exit(1);
    }
}

export async function finish() {
    await browser.close();
    writeFileSync(join(OUT, 'manifest.json'), JSON.stringify({ base: BASE, created_at: new Date().toISOString(), shots }, null, 2) + '\n');
    console.log(`Selesai: ${shots.length} screenshot di ${OUT}`);
}
