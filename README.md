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
npm run build                   # atau: npm run dev
php artisan serve
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
| **Finance** | Tagihan per siswa atau massal per kelas; orang tua unggah bukti transfer; admin verifikasi/tolak; status lunas/sebagian otomatis; ringkasan 6 bulan |

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

### Menambah modul baru (contoh: Tanya Jawab)

1. Buat `Modules/Forum/Providers/ForumServiceProvider.php` yang meng-extend `App\Support\Modules\ModuleServiceProvider`.
2. Tambahkan migration, model, controller, dan `routes/web.php` di dalam folder modul.
3. Buat halaman di `Modules/Forum/resources/js/Pages/`.
4. Daftarkan provider di `config/modules.php`, jalankan migrate dan seeder permission.

## Test

```bash
php artisan test
```

## Rencana modul berikutnya

Absensi + catatan sesi, paket kuota sesi, portal orang tua lebih lengkap (rapor), tanya jawab per pelajaran, peta belajar ala game dengan XP/lencana, kanban proyek siswa, notifikasi WhatsApp/email.
