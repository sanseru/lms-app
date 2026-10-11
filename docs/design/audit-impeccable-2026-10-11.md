# Audit UI dengan Impeccable (2026-10-11)

Skill: [pbakaus/impeccable](https://github.com/pbakaus/impeccable) v4.5.2 (engine 0.1.14), dipasang di `.claude/skills/impeccable`. Alur yang dijalankan: `impeccable context`, `init` (menulis `PRODUCT.md`), `audit` (referensi `reference/audit.md` + `craft-floor.md`), lalu perbaikan. Detektor deterministik dijalankan di kode sumber dan di halaman yang berjalan (Chromium, viewport 1280x800 dan 390x844).

Catatan jujur: wawancara `init` tidak dilakukan terpisah. `PRODUCT.md` diisi dari brief pemilik dan bukti di repo, dan setiap fakta yang disimpulkan diberi tanda _(disimpulkan)_.

Halaman yang diaudit: landing `/`, login, dashboard admin/guru/siswa/orang tua, Materi Saya, halaman kursus, coding lab (`/lessons/15`), papan kanban, Tanya Jawab (daftar + detail), Prestasiku. Screenshot: `/mnt/project-files/lms-screenshots/impeccable-audit/{sebelum,sesudah}/`.

## Skor kesehatan

| # | Dimensi | Sebelum | Sesudah | Temuan kunci |
|---|---|---|---|---|
| 1 | Aksesibilitas | 2 | 3 | Teks sekunder `#73808a` 3.9–4.1:1 di seluruh aplikasi; tombol coral teks putih 2.5:1; tombol ikon tanpa nama; tanpa skip link |
| 2 | Performa | 3 | 3 | Pyodide dan CodeMirror sudah dimuat terpisah; font dari Bunny (eksternal) |
| 3 | Theming | 2 | 2 | Token brand ada, tetapi banyak warna palet Tailwind langsung (emerald/amber/rose) dan belum ada mode gelap |
| 4 | Responsif | 3 | 3 | Dashboard guru di HP: 4 kartu statistik menutup layar pertama sebelum tugas yang perlu dikerjakan; target sentuh 36–40px |
| 5 | Integritas implementasi | 1 | 3 | Gradien ungu→violet→coral di 20+ tempat, lonceng notifikasi palsu dengan titik "belum dibaca" permanen, emoji sebagai sistem ikon, label kicker di atas judul |
| **Total** | | **11/20** (Acceptable) | **14/20** (Good) | |

Detektor URL (landing + login): **67 temuan → 0** (desktop), **59 → 0** (HP). Detektor kode: 5 → 2, keduanya positif palsu (aturan `bounce-easing` terpicu oleh selektor yang justru mematikan `animate-bounce`; `ResponsiveNavLink.vue` sisa Breeze yang tidak dipakai).

## Yang diperbaiki

**P1 Kontras (WCAG 1.4.3)**
- `--color-muted` `#73808a` → `#5f6b75` (5.3:1 di paper, 4.9:1 di chip).
- `--color-accent-600/700` digelapkan ke `#b84b27`/`#9a3d1f` sehingga teks coral (eyebrow, label tanggal) ≥ 4.6:1.
- `btn-accent`: coral `#f58464` tetap, teks diganti ink (5.9:1, sebelumnya putih 2.5:1). Hal sama di CTA landing, lencana harga, tombol dukungan Tanya Jawab.
- Token baru `success-ink` dan `sky-ink` untuk teks hijau/biru di atas latar lembut.
- Banner hasil (lulus/belum lulus, proyek selesai, pertanyaan terjawab, verifikasi sertifikat) pindah dari gradien terang + teks putih ke `emerald-700`/`amber-700`/`rose-700`.
- Ukuran teks 10–11px dinaikkan ke 12px.

**P1 Keyboard dan pembaca layar**
- Fokus `:focus-visible` global untuk tautan dan kontrol, skip link "Langsung ke konten", `aria-current="page"` di menu samping.
- Nama aksesibel untuk tombol tutup menu, tutup pemberitahuan, ubah/hapus jawaban, pasang kursus ke kelas, menu akun di HP. Teks tombol "Masuk Zoom"/"Isi absensi" kini `sr-only` di HP, bukan `hidden`.
- Banner skor diberi `role="status"`.

**P1 Integritas**
- Lonceng notifikasi dihapus: tidak ada fitur notifikasi, dan titik oranye permanen menyesatkan (prinsip "Jujur" di PRODUCT.md).
- Gradien ungu/violet diganti satu permukaan `panel-brand` (ungu dalam `brand-700` + pola titik halus) untuk sapaan siswa/guru, kartu level, panel login, tips bertanya, rekening transfer.
- Warna sampul kelas (`classColors.grad`) jadi warna solid 700, teks putih ≥ 5:1 untuk semua enam warna.
- Kartu promosi "Learn. Create. Grow." di menu samping dihapus (dekorasi tanpa fungsi).
- Emoji yang berperan sebagai ikon UI (🎒 ✅ 🔥 🚀 🌤️ 💡 👋) diganti ikon Lucide. Emoji yang merupakan konten (lencana, ikon proyek pilihan guru) tetap.
- Landing: label kicker di atas judul dihapus, tumpukan ikon-tile di kartu fitur jadi ikon sejajar judul, tanda program ▶ ⛏ 📱 diganti monogram (Sc, GM, An) seperti Py/AI.

**P2 Responsif dan hierarki**
- Dashboard guru: pekerjaan yang menunggu (tugas dinilai, pertanyaan, kartu proyek, absensi) naik ke atas; statistik pindah ke bawah dan jadi 2 kolom di HP. Admin dan Keuangan juga 2 kolom.
- Target sentuh ≥ 44px untuk tombol menu, filter usia, dan toggle tipe kelas di landing.
- Salin teks: "Kamu punya 0 sesi hari ini" → "Tidak ada sesi hari ini. Waktunya menyiapkan materi."

**P3 Permukaan browser**
- `::selection`, `caret-color`, warna scrollbar dari palet; angka tabel `tabular-nums`; `text-wrap: balance` untuk h1/h2.
- `prefers-reduced-motion`: menghentikan animasi berulang dan efek naik saat hover, tanpa mematikan semua transisi.

## Sisa (belum dikerjakan)

1. **Label eyebrow di dalam aplikasi** (P2). Impeccable melarang kicker di atas judul, tetapi `eyebrow` adalah konvensi repo di CLAUDE.md dan dipakai di 40+ halaman lewat `PageHeader`. Perlu keputusan: hapus, atau ubah jadi breadcrumb. `/impeccable typeset`.
2. **Theming** (P2). Banyak warna status masih palet Tailwind langsung; belum ada token status semantik dan belum ada mode gelap. `/impeccable extract` lalu `/impeccable colorize`.
3. **DESIGN.md** belum ada. Jalankan `/impeccable document` untuk merekam sistem visual yang sekarang.
4. **Font eksternal** (P3). Plus Jakarta Sans dan DM Sans dimuat dari fonts.bunny.net; sebaiknya di-host sendiri. `/impeccable optimize`.
5. **Sentuhan di kanban HP** belum diuji dengan gestur sentuh sungguhan (hanya viewport). Tombol panah sudah ada sebagai alternatif.
6. `ResponsiveNavLink.vue` sisa Breeze tidak dipakai; bisa dihapus.

## Cara mengulang

```bash
.claude/skills/impeccable/scripts/impeccable detect --json resources/js resources/css Modules
# halaman berjalan (butuh Chromium; di root tambahkan --no-sandbox lewat wrapper IMPECCABLE_BROWSER)
.claude/skills/impeccable/scripts/impeccable detect --json --viewport 390x844 http://127.0.0.1:8000/
```
