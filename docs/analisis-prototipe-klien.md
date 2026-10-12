# Analisis prototipe klien vs aplikasi

Tanggal: 12 Oktober 2026 (diperbarui setelah Scratch, Presentation dan detail klien). Sumber: `docs/client-example/LMS-Alprogmatics/` (prototipe statis, data di localStorage).

Prinsip yang dipakai: **istilah, menu, logo, dan alur siswa mengikuti klien**, sedangkan logika dan fitur yang di aplikasi sudah lebih lengkap (akun nyata, penilaian di server, kelas Zoom, keuangan, rapor) tetap dipertahankan. Bila prototipe hanya placeholder, aplikasi mengisinya dengan versi yang berfungsi.

## Ringkasan

| Area | Prototipe klien | Aplikasi sebelum | Keputusan |
|---|---|---|---|
| Menu siswa | Home, Course, Online Lesson, Community, Laboratory, Archive (header atas) | Sidebar: Home, Classes, Schedule, Course, Community, Laboratory, My projects, Q&A, My achievements, My certificates, My monthly reports | **Ikuti klien**: header atas dengan 6 menu klien di urutan yang sama; menu tambahan aplikasi masuk ke menu **More** |
| Menu guru | Home, Course, Online Lesson, Community, Laboratory | Sidebar dengan nama lain (Courses & lessons, Sessions & attendance, …) | **Samakan istilah**: **Course**, **Online Lesson**; sidebar tetap karena guru dan admin punya jauh lebih banyak menu |
| Logo | Tinggi 62 px di header 84 px | 44 px di sidebar, 32 px di peta | **Ikuti klien**: 62 px di header siswa, lebih besar di sidebar guru/admin |
| Login | "WELCOME BACK / Log in to your learning space." + tombol coral "Log in →" | "Sign in to your learning space." | **Ikuti teks klien**, tetap username **atau** email |
| Katalog course | 9 kartu program (Data, AI, Python, Scratch, Computer & Networks, Web Development, Desktop Applications, Android, IoT) dengan tag, jumlah level dan lesson, panel "Selected course" | Kartu per level, dikelompokkan per program | **Ikuti klien**: kartu per program dengan tag (DATA, TECHNOLOGY, …); klik untuk melihat levelnya |
| Peta Python | Dunia langit, START · level, node bernomor, kastel, popup Tasks, berurutan, guru bisa membuka lesson | Sudah sama (PR #10, #11), plus per kelas | **Sudah**; membuka menu **Course** langsung masuk layar penuh browser |
| Halaman lesson | Bar gelap, lingkaran soal, background ungu, editor dengan Fullscreen | Mode fokus, bar gelap, titik task, fullscreen otomatis | **Sudah** (background tetap terang demi keterbacaan) |
| Task "Mixed" | Pilihan ganda dan kode dalam satu task | Dipecah jadi dua task | **Sudah diputuskan** (dipecah) |
| Online Lesson | 3 kartu statis (LIVE NOW, THIS WEEK, RECORDING) | Jadwal sesi nyata dengan Zoom dan rekaman | **Ikuti klien**: nama halaman, plus kartu **Live now** (kelas yang sedang/segera mulai, tombol Join on Zoom), **This week / Coming up** (kelas berikutnya), **Recording** (rekaman Zoom terakhir), semuanya dari data sesi nyata |
| Archive | Placeholder "Nothing archived yet" | Tidak ada | **Buat versi berfungsi**: level dan lesson yang sudah selesai, untuk diulang kapan saja |
| Laboratory | Python, Scratch (scratch-gui), Presentation | Python, JavaScript, Website | **Sudah**: Python, **Scratch** (editor Scratch resmi di-host sendiri, proyek `.sb3` disimpan di server), **Presentation** (slide markdown dipisah `---`, slideshow Back/Next), Website, JavaScript |
| Community | Trending Today/Week/Month, New/Popular/Discussed, like, view, komentar | Trending 7 hari, New/Popular/Most discussed, like, view unik, komentar, moderasi guru | **Sudah**: Trending **Today / Week / Month**, proyek Scratch diputar dengan bendera hijau, slide diputar langsung; plus moderasi guru |
| Home siswa | Banner profil + level XP, 6 statistik (Projects, Likes received, Views, Comments, Lessons done, Questions solved), 6 badge (First project, 3 projects, First like, First comment, Python starter, 10 questions solved), Popular in Community | Level dan lencana, lanjut belajar, proyek, sesi, kuota | **Sudah**: 6 statistik, Popular in Community, dan **6 badge klien** ditambahkan ke 13 badge yang ada |
| Home guru | "Teacher dashboard": jumlah course, lesson, dibuka/belum | Dashboard guru lengkap (sesi hari ini, penilaian, Q&A) | Tetap (lebih berguna); akses buka-kunci ada di **Lesson access** per kelas |
| Pencarian header | Ikon kaca pembesar, "Search for courses or lessons..." (hanya tampilan) | Tidak ada | **Sudah, berfungsi**: course dari katalog, lesson hanya dari level yang boleh dibuka siswa |
| Konten Python | Start 1, Start 2, Pro 1, Pro 2 (masing-masing 32 lesson); hanya Lesson 1 Start 1 yang berisi soal nyata | Start 1 dan Start 2 | **Data demo ikut klien**: 4 level Python dengan nama klien; Lesson 1 memakai soal dari prototipe |
| Program lain | 8 program, masing-masing 2 level (Data Explorer 1/2, Jr AI/Sr AI, Scratch Creator 1/2, Network Starter/Builder, Web Builder 1/2, Desktop Maker 1/2, Android Starter 1/2, IoT Maker 1/2) | Scratch dan Web saja | **Data demo ikut klien**: 9 program dengan nama level klien |

## Yang belum dan alasannya

| Butir | Status | Catatan |
|---|---|---|
| Daftar akun sendiri (Sign up) | Sengaja tidak | Keputusan pemilik: akun dibuat admin; formulir publik yang ada adalah **free trial** |
| 32 lesson per level | Data demo | Prototipe hanya mengisi 1 lesson nyata; data demo berisi beberapa lesson per level, guru menambah sisanya lewat aplikasi |
| Background ungu di halaman soal | Sengaja beda | Latar terang dipertahankan demi keterbacaan teks dan kode |

## Scratch: cara kerja

- Editor: paket resmi `@scratch/scratch-gui` (versi dikunci di `scripts/fetch-scratch-gui.mjs`) diunduh saat `npm run build` ke `public/vendor/scratch-gui` (±100 MB, tidak di-commit). Halaman `resources/scratch/host.html` memuatnya di iframe agar React dan CSS Scratch tidak bentrok dengan aplikasi.
- Laboratory: proyek tipe **Scratch** disimpan sebagai berkas `.sb3` di disk privat (`storage/app/private/lab`), maks. 20 MB, diperiksa harus arsip zip.
- Community: proyek Scratch yang dibagikan diputar dengan player (bendera hijau), tombol layar penuh dari aplikasi.
- Lesson: tugas **Assignment** bisa diatur **Students work in: Scratch editor**. Siswa membuat proyek di halaman lesson dan menekan **Submit**; berkas `.sb3` terlampir otomatis dan guru bisa memutarnya di daftar pengumpulan.
- Gambar sprite dari pustaka Scratch tetap diambil dari server aset Scratch (internet), proyek default dan proyek siswa dari server sendiri.

## Istilah yang diseragamkan

| Klien | Dipakai di aplikasi |
|---|---|
| Course (menu) | **Course** untuk siswa dan guru (sebelumnya "Courses & lessons" untuk guru) |
| Online Lesson | **Online Lesson**: jadwal kelas siswa; daftar sesi dan absensi guru |
| Archive | **Archive**: lesson dan level yang sudah selesai |
| Lesson / Task / Theory | **Lesson** (lingkaran di peta), **Task** (isi lesson), **Theory** (tipe materi) |
| Level (Python Start 1, …) | Course di dalam **Program** |
| Course catalog | **Course catalog** di halaman **Explore courses** |
