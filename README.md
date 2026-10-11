# Alprogmatics LMS

Platform manajemen kursus coding untuk anak: siswa, guru, orang tua, kelas online via Zoom, jadwal, dan keuangan (tagihan + transfer manual), dalam satu aplikasi.

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
php artisan schedule:work         # produksi: cron `schedule:run` tiap menit (membuat sesi 4 minggu ke depan)
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
| **Dashboard** | Satu URL `/dashboard`, tampilan berbeda per role (admin, guru, siswa, orang tua) |
| **People** | CRUD siswa (kode otomatis `SIS-xxxxx`), guru (`GRU-xxxx`), orang tua; relasi orang tua ↔ anak |
| **Classes** | CRUD kelas dengan link Zoom, jadwal mingguan, kapasitas, cek bentrok jadwal guru; daftarkan siswa; halaman jadwal mingguan |
| **Landing** | Halaman promosi di `/` dengan tombol Masuk ke login bersama. Data kontak, harga, dan testimoni ada di `Modules/Landing/resources/js/site.js` |
| **Sessions** | Sesi dibuat otomatis dari jadwal kelas (`sessions:generate`), link Zoom per sesi; guru mengisi absensi + materi, PR, catatan ortu dalam satu layar; tandai untuk admin; batal sesi |
| **Learning** | Kursus (kurikulum) yang dipasang ke kelas, berisi materi Markdown + video, kuis pilihan ganda yang dinilai otomatis (nilai lulus, batas percobaan, kunci dibuka setelah lulus), dan tugas (teks/tautan/berkas) yang dinilai guru atau diminta revisi. Rapor progres per siswa untuk siswa, orang tua, dan guru. **Misi Pemanasan/Akhir** (pre/post-test) dengan laporan kenaikan (normalized gain) per konsep |
| **Coding** | Soal coding sebagai isi kursus: Python, JavaScript, HTML/CSS, dan susun baris (Parsons). Mode input→output, fungsi, dan cek HTML/CSS. Kode dijalankan di browser siswa (Pyodide/Web Worker/iframe sandbox), lalu server membandingkan dengan jawaban yang hanya disimpan di server (tes tersembunyi tidak pernah dikirim). Tutor membuat soal lewat form dengan **Uji solusi** wajib sebelum terbit, petunjuk bertingkat, dan solusi yang terbuka setelah N kali gagal. Driver `judge0` tersedia untuk penilaian di server terpisah (`CODING_DRIVER=judge0`, `JUDGE0_URL`) |
| **Forum** | **Tanya Jawab** di `/tanya`: siswa bertanya per kelas, opsional terkait pelajaran, dengan kode dan tangkapan layar. Pilihan "hanya guru" (privat) dan "sembunyikan namaku dari teman" (guru dan orang tua tetap tahu). Guru dan teman sekelas menjawab; jawaban guru ditandai dan tampil paling atas; penanya atau guru memilih jawaban terbaik; dukungan "aku juga bingung" dan "membantu"; guru menyematkan pertanyaan penting. Orang tua bisa membaca pertanyaan anaknya. Kartu "Bingung? Tanya guru" muncul di setiap halaman pelajaran, dan dashboard guru menampilkan pertanyaan yang belum dijawab |
| **Kanban** | **Proyek** di `/proyek`: guru membuat proyek untuk kelas (ikon, tenggat, penjelasan, langkah bawaan, atau pakai contoh Game Scratch/Program Python/Website). Setiap siswa otomatis mendapat papan kanban sendiri dengan kolom Mau dikerjakan → Sedang dikerjakan → Minta dicek guru → Selesai. Siswa menyeret kartu (atau memakai tombol panah di HP), menambah kartu sendiri, dan menempel link hasil karya. Guru menyetujui atau mengembalikan kartu dengan catatan dari antrean "Perlu dicek", dan melihat progres semua siswa. Pengecekan guru bisa dimatikan per proyek; proyek yang diarsipkan mengunci papan siswa. Orang tua melihat papan anaknya |
| **Gamification** | XP, level (Pemula Koding sampai Legenda), dan 13 lencana yang dihitung otomatis dari aktivitas: menyelesaikan pelajaran, lulus kuis/soal coding, tugas dinilai, hadir di kelas, bertanya dan jawaban terpilih, langkah dan proyek selesai, sertifikat. Halaman **Prestasiku** (siswa), prestasi anak (orang tua), tabel XP per kelas (guru), "Bintang kelas bulan ini" (bisa dimatikan dengan `GAMIFICATION_LEADERBOARD=false`). XP dihitung ulang dari data sumber (`xp:sync`, terjadwal tiap malam), jadi modul lain tidak perlu memanggil apa pun. Modul lain bisa menambah aturan XP lewat `XpRules` |
| **Certificates** | Sertifikat A4 lanskap dengan nomor, QR, dan kode verifikasi. Daftar siswa yang sudah menyelesaikan semua pelajaran kursus muncul otomatis untuk diterbitkan sekali klik; guru juga bisa menerbitkan manual (mis. juara lomba). Cetak atau simpan PDF dari browser. Halaman publik `/sertifikat/{kode}` untuk memeriksa keaslian (nama belakang disingkat). Sertifikat bisa dicabut |
| **Finance** | Tagihan per siswa atau massal per kelas; orang tua unggah bukti transfer; admin verifikasi/tolak; status lunas/sebagian otomatis; ringkasan 6 bulan. **Paket kuota** (mis. 8/16/24 sesi) yang aktif saat tagihan lunas dan terpotong otomatis saat sesi selesai (hadir/alpa memotong, izin/sakit tidak). **Honor tutor** tercatat otomatis per sesi selesai sesuai tarif guru, direkap jadi slip bulanan, ditandai dibayar dengan bukti transfer |

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

## Test

```bash
php artisan test
```

## Rencana modul berikutnya

Peta belajar ala game, Judge0 untuk C++/Java, soal blok (Blockly), reschedule/makeup sesi, notifikasi WhatsApp/email (termasuk saat pertanyaan dijawab).
