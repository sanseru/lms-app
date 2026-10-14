# Alprogmatics LMS

Platform manajemen kursus coding untuk anak (antarmuka berbahasa Inggris): siswa, guru, orang tua, kelas online via Zoom, jadwal, dan keuangan (tagihan + transfer manual), dalam satu aplikasi.

**Stack:** Laravel 13 · Inertia.js 2 · Vue 3 · Tailwind CSS 4 · Spatie Permission · Pest

## Menjalankan

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # role, permission, dan data demo
npm run build                   # atau: npm run dev (keduanya juga menyalin Pyodide ke public/vendor)
php artisan serve
php artisan schedule:work         # produksi: cron `schedule:run` tiap menit (membuat sesi 4 minggu ke depan, pengingat laporan sesi)
```

Default database SQLite. Untuk PostgreSQL/MySQL, ubah `DB_CONNECTION` dan kredensial di `.env`.

### Akun demo (kata sandi: `password`)

| Role | Username |
|---|---|
| Admin | `admin` |
| Guru | `budi`, `sari`, `rizky` |
| Siswa | `aldi`, `nadia`, `raka`, … |
| Orang tua | `ortu1` … `ortu5` |

Login memakai username **atau** email, jadi anak tidak wajib punya email. Pendaftaran publik dimatikan; akun dibuat admin.

## Fitur saat ini

| Modul | Isi |
|---|---|
| **Identity** | Role `admin`, `teacher`, `student`, `guardian`; permission per modul; login username/email; akun nonaktif tidak bisa login |
| **Dashboard** | Satu URL `/dashboard`, tampilan berbeda per role (admin, guru, siswa, orang tua). Siswa memakai header atas seperti prototipe klien (logo besar; menu **Home, Course, Online Lesson, Community, Laboratory, Archive**, sisanya di **More**); beranda siswa menampilkan 6 statistik klien (Projects, Likes received, Views, Comments, Lessons done, Questions solved) dan **Popular in Community**; ikon kaca pembesar di header mencari course dan lesson (`/search`); **Online Lesson** menampilkan kartu Live now / This week / Recording dari sesi nyata |
| **People** | CRUD siswa (kode otomatis `SIS-xxxxx`), guru (`GRU-xxxx`), orang tua; relasi orang tua ↔ anak |
| **Classes** | CRUD kelas dengan link Zoom, jadwal mingguan, kapasitas, cek bentrok jadwal guru; daftarkan siswa; halaman jadwal mingguan |
| **Landing** | Halaman promosi di `/` dengan tombol login ke halaman login bersama, plus formulir publik `/free-trial`. Data kontak, harga, dan testimoni ada di `Modules/Landing/resources/js/site.js` |
| **Sessions** | Sesi dibuat otomatis dari jadwal kelas (`sessions:generate`), link Zoom per sesi; guru mengisi absensi + materi, PR, catatan ortu dalam satu layar; tandai untuk admin; batal sesi. **Pindah jadwal / sesi pengganti** yang tertaut ke sesi asal (cek bentrok jadwal guru). **Feed iCal** pribadi per user di `/calendar/{token}.ics` untuk Google/Apple Calendar |
| **Learning** | Kursus (kurikulum) yang dipasang ke kelas, berisi materi Markdown + video, kuis yang dinilai otomatis (pilihan tunggal/ganda, benar/salah, isian singkat; nilai lulus, batas percobaan, timer opsional, kunci dibuka setelah lulus), dan tugas (teks/tautan/berkas) yang dinilai guru atau diminta revisi. Rapor progres per siswa untuk siswa, orang tua, dan guru. **Misi Pemanasan/Akhir** (pre/post-test) dengan laporan kenaikan (normalized gain) per konsep. Menu **Course** siswa menampilkan kelas yang sedang diikuti (guru, jadwal, sesi berikutnya, Zoom, progres) dan peta belajar berkelok dari START sampai Finish. Struktur **Program → level → Lesson → task**: program (`programs`, menu **Programs**) berisi course sebagai level berurutan (`level_order`); Lesson bernomor (`course_units`) mengelompokkan item course sebagai task; siswa naik level step by step (`LevelAccess`: level berikutnya terbuka setelah semua task level sebelumnya selesai, tanpa pindah kelas). Lesson dalam satu level juga berurutan (`LessonGate`); guru kelas bisa membuka Lesson lebih awal per kelas di **Classes → Lesson access** (`class_lesson_opens`, termasuk Lesson di level berikutnya). Peta course siswa tampil penuh tanpa sidebar (`WorldLayout`); layar penuh browser hanya lewat tombol fullscreen (tidak otomatis, permintaan klien). Halaman pelajaran siswa terbuka dalam **mode fokus** (`FocusLayout`: tanpa sidebar, bar atas dengan tombol kembali ke peta dan satu titik per pelajaran, tombol fullscreen); editor soal coding dan Laboratory bisa **fullscreen** (`resources/js/lib/fullscreen.js`). Tugas (**Assignment**) bisa diatur **Students work in: Scratch editor**: siswa membuat proyek Scratch langsung di halaman lesson dan **Submit** melampirkan `.sb3`, guru memutarnya di daftar pengumpulan. **Katalog kursus** di `/courses/explore`: siswa melihat semua kursus terbit, membuka pratinjau read-only kursus lain (pelajaran terkunci), dan menekan **I'm interested** yang memberi tahu admin dan orang tua serta tampil sebagai **Interested students** di halaman kursus |
| **Coding** | Soal coding sebagai isi kursus: Python, JavaScript, HTML/CSS, dan susun baris (Parsons). Mode input→output, fungsi, dan cek HTML/CSS. Kode dijalankan di browser siswa (Pyodide/Web Worker/iframe sandbox), lalu server membandingkan dengan jawaban yang hanya disimpan di server (tes tersembunyi tidak pernah dikirim). Tutor membuat soal lewat form dengan **Uji solusi** wajib sebelum terbit, petunjuk bertingkat, solusi yang terbuka setelah N kali gagal, **aturan kode** (wajib/dilarang memakai konstruksi tertentu, maks. baris), dan **versi soal** (nilai lama tetap pada versi lama). Siswa bisa **Report a problem** pada pelajaran; laporan masuk ke `/lesson-reports`. Driver `judge0` tersedia untuk penilaian di server terpisah (`CODING_DRIVER=judge0`, `JUDGE0_URL`) |
| **Forum** | **Q&A** di `/questions`: siswa bertanya per kelas, opsional terkait pelajaran, dengan kode dan tangkapan layar. Pilihan "hanya guru" (privat) dan "sembunyikan namaku dari teman" (guru dan orang tua tetap tahu). Guru dan teman sekelas menjawab; jawaban guru ditandai dan tampil paling atas; penanya atau guru memilih jawaban terbaik; dukungan "aku juga bingung" dan "membantu"; guru menyematkan pertanyaan penting. Orang tua bisa membaca pertanyaan anaknya. Kartu "Ask your teacher" muncul di setiap halaman pelajaran, dan dashboard guru menampilkan pertanyaan yang belum dijawab |
| **Kanban** | **Projects** di `/projects`: guru membuat proyek untuk kelas (ikon, tenggat, penjelasan, langkah bawaan, atau pakai contoh Game Scratch/Program Python/Website). Setiap siswa otomatis mendapat papan kanban sendiri dengan kolom To do → Doing → Ready for review → Done. Siswa menyeret kartu (atau memakai tombol panah di HP), menambah kartu sendiri, dan menempel link hasil karya. Guru menyetujui atau mengembalikan kartu dengan catatan dari antrean "Perlu dicek", dan melihat progres semua siswa. Pengecekan guru bisa dimatikan per proyek; proyek yang diarsipkan mengunci papan siswa. Orang tua melihat papan anaknya |
| **Gamification** | XP, level (Pemula Koding sampai Legenda), dan 19 lencana yang dihitung otomatis dari aktivitas: menyelesaikan pelajaran, lulus kuis/soal coding, tugas dinilai, hadir di kelas, bertanya dan jawaban terpilih, langkah dan proyek selesai, sertifikat, serta 6 lencana klien (First project, 3 projects, First like, First comment, Python starter, 10 questions solved) dari proyek Laboratory yang dibagikan, like, komentar, dan pelajaran Python. Halaman **My achievements** di `/achievements` (siswa), prestasi anak (orang tua), tabel XP per kelas (guru), papan peringkat kelas (bisa dimatikan dengan `GAMIFICATION_LEADERBOARD=false`). XP dihitung ulang dari data sumber (`xp:sync`, terjadwal tiap malam), jadi modul lain tidak perlu memanggil apa pun. Modul lain bisa menambah aturan XP lewat `XpRules` |
| **Certificates** | Sertifikat A4 lanskap dengan nomor, QR, dan kode verifikasi. Daftar siswa yang sudah menyelesaikan semua pelajaran kursus muncul otomatis untuk diterbitkan sekali klik; guru juga bisa menerbitkan manual (mis. juara lomba). Cetak atau simpan PDF dari browser. Halaman publik `/certificates/{kode}` untuk memeriksa keaslian (nama belakang disingkat). Sertifikat bisa dicabut |
| **Finance** | Tagihan per siswa atau massal per kelas; orang tua unggah bukti transfer; admin verifikasi/tolak; status lunas/sebagian otomatis; ringkasan 6 bulan. **Paket kuota** (mis. 8/16/24 sesi) yang aktif saat tagihan lunas dan terpotong otomatis saat sesi selesai (hadir/alpa memotong, izin/sakit tidak). **Honor tutor** tercatat otomatis per sesi selesai sesuai tarif guru, direkap jadi slip bulanan, ditandai dibayar dengan bukti transfer. **Kuitansi** siap cetak (dengan terbilang) untuk pembayaran terverifikasi. Ringkasan keuangan menampilkan **paket hampir habis** (sisa ≤ 2 sesi atau kedaluwarsa ≤ 14 hari) |
| **Lab** | **Laboratory** di `/lab`: siswa dan guru membuat proyek bebas **Python, Scratch, Presentation, Website, atau JavaScript**. Kode dijalankan di browser memakai runner modul Coding; **Scratch** memakai editor Scratch resmi yang di-host sendiri (`public/vendor/scratch-gui`, dipasang `scripts/fetch-scratch-gui.mjs` saat `npm run build`) dan disimpan sebagai berkas `.sb3` di disk privat; **Presentation** berupa slide markdown dipisah `---` yang diputar sebagai slideshow (Back/Next, layar penuh). Tombol **Save & share** mengirim proyek ke **Community** di `/community`: galeri lintas kelas dengan trending **Today/Week/Month**, urutan New/Popular/Most discussed, cari, filter jenis, like, view unik per akun, dan komentar (semua yang login boleh like dan komentar). Proyek Scratch diputar dengan bendera hijau. Nama siswa ditampilkan sebagai nama depan + inisial. Guru/admin bisa menghapus komentar dan menurunkan proyek dari Community |
| **Notifications** | Lonceng di header dan halaman `/notifications`. Modul ini mendengarkan event modul lain: tagihan baru dan hasil verifikasi pembayaran (orang tua), bukti transfer masuk (admin), ringkasan sesi (orang tua) dan PR (siswa), tugas dinilai/revisi, pertanyaan baru (guru) dan jawaban (penanya). `sessions:remind-reports` (tiap jam) mengingatkan guru yang belum mengisi laporan sesi. Kirim dari modul mana pun dengan `Notify::send()`; kunci unik mencegah notifikasi ganda |
| **Reports** | **Laporan bulanan orang tua** di `/monthly-reports/{siswa}/{YYYY-MM}`: kehadiran, pelajaran, nilai, proyek, dan sisa kuota sebulan, plus catatan guru per kelas per bulan. Dicetak ke PDF A4 dari browser. `reports:monthly` (tanggal 1 pukul 08:00) memberi tahu orang tua bahwa laporan bulan lalu siap |
| **Leads** | Formulir publik **free trial** di `/free-trial` membuat calon siswa (lead). Admin mengelola pipeline di `/leads` (New → Contacted → Trial scheduled → Trial done → Enrolled / Not continuing), menjadwalkan kelas trial dengan guru, dan guru mengisi hasil trial di `/leads/trials`. `ConvertLead` membuat akun siswa dan orang tua (memakai ulang orang tua yang nomor HP/email-nya sama) |
| **Audit** | **Audit log** di `/audit`: login dan gagal login, data akun, tarif guru, tagihan, pembayaran, paket kuota, slip honor, nilai tugas, absensi, jadwal, sertifikat, dan calon siswa. Tabel `activity_log` append-only (model menolak update/delete), kata sandi disamarkan. Modul lain mendaftarkan model lewat `AuditTrail::watch(...)` |

Hak akses ditegakkan di backend (middleware `can:` dan Policy), bukan hanya menyembunyikan menu.

## Struktur modul

```
Modules/<Nama>/
  Providers/<Nama>ServiceProvider.php   permission, menu, policy
  Http/Controllers, Http/Requests
  Models, Policies, Actions
  Database/Migrations
  routes/web.php                        otomatis middleware web + auth
  resources/js/Pages/                   halaman Vue
```

- Daftar modul aktif ada di `config/modules.php`.
- Halaman modul dirender dengan `Inertia::render('NamaModul::Folder/Halaman')`.
- Menu sidebar didaftarkan modul lewat `navigation()` dan otomatis disaring sesuai role/permission.
- Permission modul dideklarasikan di `permissions()`, lalu dibuat oleh `php artisan db:seed --class="Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder"`.
- Komponen UI bersama ada di `resources/js/Components/ui` (Badge, StatCard, ClassCard, EmptyState, ConfirmDialog, dll.). Token warna dan utilitas (`btn-primary`, `card`, `field`, …) ada di `resources/css/app.css`.

### Menambah modul baru (contoh: Peta Belajar)

Lihat `Modules/Forum` sebagai contoh modul lengkap yang kecil.

1. Buat `Modules/Map/Providers/MapServiceProvider.php` yang meng-extend `App\Support\Modules\ModuleServiceProvider`.
2. Tambahkan migration, model, controller, dan `routes/web.php` di dalam folder modul.
3. Buat halaman di `Modules/Map/resources/js/Pages/`.
4. Daftarkan provider di `config/modules.php`, jalankan migrate dan seeder permission.

## Dokumentasi

- [Analisis prototipe klien vs aplikasi](docs/analisis-prototipe-klien.md) (apa yang sudah, belum, dan istilah yang diseragamkan)

- [Panduan pengguna per peran](docs/panduan-pengguna/README.md) (admin, guru, siswa, orang tua)
- [Dokumentasi production](docs/production.md) (instalasi server, `.env`, cron, backup, keamanan, deploy)

## Test

```bash
php artisan test
```

## Rencana modul berikutnya

Urutan lengkap ada di [`AI_CONTEXT.md`](AI_CONTEXT.md) bagian 9. Ringkasnya:

- CI GitHub Actions (pint, test, build).
- Tautan guru ke rapor progres siswa dari halaman kelas.
- Uji kompatibilitas MySQL/PostgreSQL.
- Pengerasan production: trusted proxies, disk unggahan yang bisa diatur (S3), health check.
- Streak harian dan PWA.
- Kanal notifikasi email dan WhatsApp (membaca tabel `notifications` yang sama).
- Permintaan pindah jadwal dari orang tua.
- Soal blok (Blockly) untuk usia 7–10 tahun.
- Opsional: Judge0 untuk C++/Java, Zoom API, login Google + MFA admin, multi cabang, dark mode.

Status kelengkapan terhadap riset ada di [`docs/audit-kelengkapan.md`](docs/audit-kelengkapan.md).
