// Ubah docs/panduan-uji-coba/README.md (+ gambar) menjadi PDF A4 untuk klien:
// sampul, daftar isi, nomor halaman. Dicetak dengan Chromium (Playwright), jadi
// tidak butuh software lain. Hasil: docs/panduan-uji-coba/dist/Panduan-Uji-Coba-Alprogmatics.pdf
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { pathToFileURL } from 'node:url';
import { marked } from 'marked';
import { chromium } from 'playwright';
import { ROOT } from './lib.mjs';

const DIR = join(ROOT, 'docs/panduan-uji-coba');
const OUT = join(DIR, 'dist/Panduan-Uji-Coba-Alprogmatics.pdf');
const logo = 'data:image/png;base64,' + readFileSync(join(ROOT, 'public/images/logo-header.png')).toString('base64');

// Judul H1 jadi sampul; H2/H3 jadi daftar isi dengan anchor.
const source = readFileSync(join(DIR, 'README.md'), 'utf8');
const [, title = 'Panduan Uji Coba'] = source.match(/^#\s+(.+)$/m) ?? [];
const body = source.replace(/^#\s+.+$/m, '');
const slug = (t) => t.toLowerCase().replace(/<[^>]+>/g, '').replace(/[^\w\s-]/g, '').trim().replace(/\s+/g, '-');
const toc = [];
const renderer = new marked.Renderer();
renderer.heading = function ({ tokens, depth }) {
    const text = this.parser.parseInline(tokens);
    const id = slug(text);
    if (depth <= 3) toc.push({ depth, text, id });
    return `<h${depth} id="${id}">${text}</h${depth}>`;
};
const html = marked.parse(body, { renderer });
const date = new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' });

const page = `<!doctype html>
<html lang="id"><head><meta charset="utf-8"><base href="${pathToFileURL(DIR + '/').href}">
<style>
  :root { --brand: #5b4185; --brand-dark: #3d2a5c; --accent: #f97b5f; --ink: #1d2934; --muted: #5d6b78; --line: #e3e7ee; --chip: #f0f3f7; }
  body { font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif; color: var(--ink); font-size: 10.5pt; line-height: 1.55; }
  .cover { height: 255mm; display: flex; flex-direction: column; justify-content: center; page-break-after: always; }
  .cover img { height: 26mm; width: auto; align-self: flex-start; border: 0; margin: 0; border-radius: 0; }
  .cover .band { margin-top: 18mm; background: var(--brand); color: #fff; border-radius: 6mm; padding: 14mm 12mm; }
  .cover h1 { font-size: 28pt; line-height: 1.15; margin: 0 0 4mm; }
  .cover p { margin: 0; font-size: 12pt; opacity: .9; }
  .cover .meta { margin-top: 10mm; color: var(--muted); font-size: 10pt; }
  .toc { page-break-after: always; }
  .toc h2 { margin-top: 0; }
  .toc ol { list-style: none; padding: 0; margin: 0; }
  .toc li { padding: 1.2mm 0; border-bottom: 1px dotted var(--line); }
  .toc li.d3 { padding-left: 7mm; font-size: 9.5pt; color: var(--muted); }
  .toc a { color: inherit; text-decoration: none; }
  h2 { font-size: 17pt; color: var(--brand-dark); border-bottom: 2px solid var(--brand); padding-bottom: 2mm; margin-top: 10mm; page-break-before: always; }
  h2:first-of-type { page-break-before: avoid; }
  h3 { font-size: 13pt; color: var(--brand); margin: 7mm 0 2mm; page-break-after: avoid; }
  h4 { font-size: 11pt; margin: 5mm 0 1.5mm; page-break-after: avoid; }
  p, li { orphans: 3; widows: 3; }
  a { color: var(--brand); }
  code { background: var(--chip); border-radius: 1mm; padding: 0 1mm; font-size: 9.5pt; }
  pre { background: var(--chip); border-radius: 2mm; padding: 3mm; white-space: pre-wrap; font-size: 9pt; }
  table { width: 100%; border-collapse: collapse; margin: 3mm 0; font-size: 9.5pt; page-break-inside: auto; }
  th { background: var(--brand); color: #fff; text-align: left; }
  th, td { border: 1px solid var(--line); padding: 1.6mm 2.2mm; vertical-align: top; }
  tr { page-break-inside: avoid; }
  img { display: block; max-width: 100%; max-height: 165mm; margin: 3mm auto 1mm; border: 1px solid var(--line); border-radius: 2mm; page-break-inside: avoid; }
  img + em, p > em:only-child { display: block; text-align: center; color: var(--muted); font-size: 9pt; }
  blockquote { margin: 3mm 0; padding: 2.5mm 4mm; border-left: 1.2mm solid var(--accent); background: #fff4f1; border-radius: 0 2mm 2mm 0; }
  blockquote p { margin: 0; }
  hr { border: 0; border-top: 1px solid var(--line); margin: 6mm 0; }
</style></head><body>
<section class="cover">
  <img src="${logo}" alt="Alprogmatics">
  <div class="band"><h1>${title}</h1><p>Langkah demi langkah mencoba aplikasi LMS Alprogmatics dari awal sampai akhir</p></div>
  <p class="meta">Dibuat otomatis dari data demo · ${date}</p>
</section>
<nav class="toc"><h2>Daftar isi</h2><ol>${toc.filter((t) => t.depth >= 2).map((t) => `<li class="d${t.depth}"><a href="#${t.id}">${t.text}</a></li>`).join('')}</ol></nav>
<main>${html}</main>
</body></html>`;

// Ditulis ke berkas dulu agar gambar (file://) boleh dimuat oleh Chromium.
mkdirSync(join(DIR, 'dist'), { recursive: true });
const htmlFile = join(DIR, 'dist/panduan.html');
writeFileSync(htmlFile, page);
const browser = await chromium.launch();
const tab = await browser.newPage();
await tab.goto(pathToFileURL(htmlFile).href, { waitUntil: 'load' });
await tab.pdf({
    path: OUT,
    format: 'A4',
    printBackground: true,
    displayHeaderFooter: true,
    headerTemplate: '<span></span>',
    footerTemplate: `<div style="width:100%;font-size:8pt;color:#5d6b78;padding:0 16mm;display:flex;justify-content:space-between"><span>${title}</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>`,
    margin: { top: '16mm', bottom: '18mm', left: '16mm', right: '16mm' },
});
await browser.close();
console.log(`PDF: ${OUT}`);
