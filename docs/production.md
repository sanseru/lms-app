# Dokumentasi Production

Panduan memasang, mengonfigurasi, mengoperasikan, dan memperbarui Alprogmatics LMS di server production. Untuk cara memakai aplikasi per peran, lihat [panduan pengguna](panduan-pengguna/README.md).

Contoh perintah memakai Ubuntu 24.04, Nginx, PHP-FPM 8.3, dan MySQL 8. Sesuaikan bila memakai distro atau database lain.

## Daftar isi

1. [Gambaran arsitektur](#gambaran-arsitektur)
2. [Persyaratan server](#persyaratan-server)
3. [Instalasi pertama](#instalasi-pertama)
4. [Konfigurasi .env](#konfigurasi-env)
5. [Database](#database)
6. [Build aset frontend](#build-aset-frontend)
7. [Web server (Nginx dan PHP)](#web-server-nginx-dan-php)
8. [Scheduler (cron) dan queue](#scheduler-cron-dan-queue)
9. [Storage dan berkas unggahan](#storage-dan-berkas-unggahan)
10. [Optimasi](#optimasi)
11. [Backup dan restore](#backup-dan-restore)
12. [Keamanan](#keamanan)
13. [Deploy dan update](#deploy-dan-update)
14. [Log dan pemantauan](#log-dan-pemantauan)
15. [Menyesuaikan halaman depan](#menyesuaikan-halaman-depan)
16. [Troubleshooting](#troubleshooting)
17. [Belum siap dan keterbatasan](#belum-siap-dan-keterbatasan)

## Gambaran arsitektur

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 13 (PHP 8.3+), modular monolith di `Modules/<Nama>`, modul aktif di `config/modules.php` |
| Frontend | Inertia.js 2 + Vue 3 + Tailwind CSS 4, dibundel Vite ke `public/build` |
| Hak akses | spatie/laravel-permission: role `admin`, `teacher`, `student`, `guardian` |
| Database | SQLite (bawaan), MySQL/MariaDB, atau PostgreSQL |
| Session, cache, queue | Driver `database` (tanpa Redis) |
| Berkas unggahan | Disk `local` privat di `storage/app/private`, dilayani lewat controller dengan cek hak akses |
| Python di browser | Pyodide disajikan dari server sendiri di `public/vendor/pyodide` (bukan CDN) |
| Tugas terjadwal | Laravel Scheduler lewat cron |

Kode siswa di modul Coding **tidak pernah dijalankan di server Laravel**. Kode berjalan di browser siswa (Pyodide, Web Worker, iframe sandbox), lalu server membandingkan output dengan jawaban yang hanya tersimpan di server. Opsi `judge0` mengirim kode ke server Judge0 terpisah.

## Persyaratan server

| Komponen | Minimum | Catatan |
|---|---|---|
| CPU / RAM | 1 vCPU / 1 GB | 2 vCPU / 2 GB nyaman untuk ratusan siswa. Build aset butuh RAM lebih; bisa dibuild di mesin lain |
| Disk | 10 GB | Bertambah sesuai unggahan (bukti transfer ≤ 4 MB, tugas ≤ 10 MB per berkas) dan backup |
| PHP | 8.3 atau lebih baru | Ekstensi: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `intl`, `mbstring`, `openssl`, `pdo` + driver database (`pdo_mysql`/`pdo_pgsql`/`pdo_sqlite`), `session`, `tokenizer`, `xml`, `zip` |
| Composer | 2.x | |
| Node.js | 22 LTS (min. 20.19) | Hanya untuk build aset. Tidak perlu berjalan di production bila aset dibuild di tempat lain |
| Database | MySQL 8 / MariaDB 10.6+ / PostgreSQL 14+ / SQLite 3.35+ | Lihat [Database](#database) |
| Web server | Nginx atau Apache + PHP-FPM | HTTPS wajib |
| Lainnya | `git`, `cron`, `supervisor` (opsional, untuk queue) | |

Contoh pemasangan paket di Ubuntu 24.04:

```bash
sudo apt update
sudo apt install -y nginx git unzip cron supervisor mysql-server \
  php8.3-fpm php8.3-cli php8.3-bcmath php8.3-curl php8.3-intl php8.3-mbstring \
  php8.3-mysql php8.3-sqlite3 php8.3-xml php8.3-zip
# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
# Node 22 (untuk build)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

## Instalasi pertama

Contoh direktori aplikasi `/var/www/lms`, dijalankan sebagai user `www-data`.

```bash
# 1. Ambil kode
sudo mkdir -p /var/www/lms && sudo chown $USER:www-data /var/www/lms
git clone https://github.com/sanseru/lms-app.git /var/www/lms
cd /var/www/lms

# 2. Dependensi PHP (tanpa paket dev)
composer install --no-dev --optimize-autoloader

# 3. Konfigurasi
cp .env.example .env
php artisan key:generate
nano .env            # isi sesuai bagian "Konfigurasi .env"

# 4. Database: buat database dan user dulu (lihat bagian Database), lalu
php artisan migrate --force
php artisan db:seed --force     # hanya role & permission bila APP_ENV=production

# 5. Admin pertama (lihat bagian Database)
php artisan tinker

# 6. Build aset (juga menyalin Pyodide ke public/vendor/pyodide)
npm ci
npm run build

# 7. Izin folder
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache

# 8. Optimasi
php artisan optimize
```

Lanjutkan dengan [Nginx](#web-server-nginx-dan-php), [cron](#scheduler-cron-dan-queue), dan [backup](#backup-dan-restore).

> **Penting:** jalankan seeder hanya setelah `APP_ENV=production` diset. `DatabaseSeeder` membuat data demo (akun `admin`/`password`, guru, siswa, orang tua, tagihan contoh) bila environment **bukan** production. Data demo di server production adalah celah keamanan.

## Konfigurasi .env

Nilai yang wajib diubah dari `.env.example` untuk production:

| Kunci | Nilai production | Keterangan |
|---|---|---|
| `APP_NAME` | `Alprogmatics` | Nama di judul halaman dan email |
| `APP_ENV` | `production` | Mematikan data demo saat seeding dan mengaktifkan mode production (lazy loading tidak dipaksa error) |
| `APP_KEY` | hasil `php artisan key:generate` | Jangan pernah diganti setelah berjalan: session dan data terenkripsi menjadi tidak terbaca |
| `APP_DEBUG` | `false` | **Wajib** `false`. `true` membocorkan konfigurasi dan isi `.env` saat error |
| `APP_URL` | `https://lms.domainanda.id` | Dipakai untuk tautan di email, feed kalender iCal, dan QR sertifikat |
| `APP_LOCALE` | `id` | Bahasa Indonesia |
| `APP_TIMEZONE` | `Asia/Jakarta` | Jadwal, sesi, dan scheduler memakai zona ini |
| `LOG_STACK` | `daily` | Log dirotasi harian (lihat `LOG_DAILY_DAYS`, bawaan 14 hari) |
| `LOG_LEVEL` | `warning` | `debug` terlalu banyak untuk production |
| `DB_CONNECTION` | `mysql` / `pgsql` / `sqlite` | Lihat [Database](#database) |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | sesuai server | Tidak perlu untuk SQLite (cukup `DB_DATABASE` berisi path absolut, atau kosong untuk `database/database.sqlite`) |
| `SESSION_DRIVER` | `database` | Bawaan, tidak perlu Redis |
| `SESSION_LIFETIME` | `120` | Menit sampai login kedaluwarsa bila tidak aktif |
| `SESSION_SECURE_COOKIE` | `true` | Tambahkan baris ini; cookie hanya dikirim lewat HTTPS |
| `SESSION_ENCRYPT` | `false` atau `true` | Opsional |
| `QUEUE_CONNECTION` | `database` | Lihat [queue](#queue) |
| `CACHE_STORE` | `database` | Bawaan |
| `FILESYSTEM_DISK` | `local` | Semua unggahan disimpan privat |
| `MAIL_MAILER` | `smtp` | Bawaan `log` (email hanya ditulis ke log). Isi agar **Lupa kata sandi** bisa mengirim email |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | dari penyedia SMTP | Mis. Mailgun, Brevo, SES, atau SMTP hosting |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | `noreply@domainanda.id`, `${APP_NAME}` | |
| `FINANCE_BANK_NAME` | mis. `BCA` | Rekening tujuan transfer yang tampil ke orang tua |
| `FINANCE_BANK_ACCOUNT` | nomor rekening | **Ganti** dari contoh `1234567890` |
| `FINANCE_BANK_HOLDER` | nama pemilik rekening | |
| `CODING_DRIVER` | `browser` | `judge0` hanya bila server Judge0 sudah ada |
| `JUDGE0_URL`, `JUDGE0_TOKEN` | kosong | Diisi bila `CODING_DRIVER=judge0` (token dikirim sebagai header `X-Auth-Token`) |
| `PYODIDE_URL` | `/vendor/pyodide/` (bawaan) | Ubah hanya bila Pyodide disajikan dari CDN/domain lain |
| `GAMIFICATION_LEADERBOARD` | `true` | `false` menyembunyikan "Bintang kelas bulan ini" |

Kunci lain (`REDIS_*`, `AWS_*`, `MEMCACHED_*`, `BROADCAST_CONNECTION`) tidak dipakai aplikasi saat ini dan boleh dibiarkan.

Setelah mengubah `.env` di server yang sudah dioptimasi, jalankan `php artisan optimize` lagi; konfigurasi di-cache dan perubahan `.env` tidak terbaca sebelum cache dibuat ulang.

## Database

### Pilihan database

- **MySQL 8 / MariaDB atau PostgreSQL**: disarankan untuk production (backup lebih mudah, aman untuk akses bersamaan).
- **SQLite**: cukup untuk kursus kecil di satu server. Pastikan file database dan foldernya bisa ditulis `www-data`, dan ikutkan file itu di backup.

Aplikasi ini dikembangkan dan seluruh test Pest-nya dijalankan dengan SQLite. Query memakai query builder Laravel dan SQL standar, tetapi **uji dulu di staging** dengan database yang sama dengan production sebelum go-live.

### Membuat database MySQL

```sql
CREATE DATABASE lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lms'@'localhost' IDENTIFIED BY 'sandi-yang-kuat';
GRANT ALL PRIVILEGES ON lms.* TO 'lms'@'localhost';
FLUSH PRIVILEGES;
```

Lalu di `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lms
DB_USERNAME=lms
DB_PASSWORD=sandi-yang-kuat
```

### Migrasi dan seeding

```bash
php artisan migrate --force      # membuat semua tabel, termasuk tabel tiap modul
php artisan db:seed --force      # role + permission (dan data demo bila BUKAN production)
```

Migration setiap modul ada di `Modules/<Nama>/Database/Migrations` dan dijalankan otomatis oleh `migrate`.

Permission dideklarasikan di provider tiap modul. Setiap kali modul baru ditambahkan atau permission berubah, jalankan seeder permission saja (aman diulang):

```bash
php artisan db:seed --force --class="Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder"
php artisan permission:cache-reset
```

### Membuat admin pertama

Di production tidak ada akun bawaan. Buat admin lewat Tinker:

```bash
php artisan tinker
```

```php
$u = App\Models\User::create([
    'name' => 'Administrator',
    'username' => 'admin',
    'email' => 'admin@domainanda.id',
    'password' => 'GantiDenganSandiKuat!',   // otomatis di-hash
    'status' => 'active',
]);
$u->assignRole('admin');
```

Setelah login, admin membuat akun guru, siswa, dan orang tua dari menu **Pengguna**. Akun admin tambahan dibuat dengan cara yang sama.

Bila admin lupa kata sandi dan email belum dikonfigurasi:

```bash
php artisan tinker --execute='App\Models\User::where("username","admin")->first()->update(["password" => "SandiBaru123!"]);'
```

## Build aset frontend

```bash
npm ci            # pasang dependensi persis sesuai package-lock.json
npm run build     # menyalin Pyodide ke public/vendor/pyodide, lalu vite build ke public/build
```

Catatan:

- `.npmrc` berisi `ignore-scripts=true`, jadi skrip instal paket tidak dijalankan. Penyalinan Pyodide dilakukan oleh `npm run build` (`scripts/copy-pyodide.mjs`), bukan saat `npm install`.
- `public/build` dan `public/vendor/pyodide` tidak masuk git. Keduanya harus ada di server: build di server, atau build di CI/laptop lalu unggah kedua folder itu.
- Pyodide sekitar 13 MB dan diunduh browser siswa sekali saat pertama membuka soal Python, lalu di-cache browser.
- Node tidak perlu berjalan terus. Setelah build, `node_modules` boleh dihapus dari server untuk menghemat disk (pasang lagi saat build berikutnya).

## Web server (Nginx dan PHP)

### Nginx

`/etc/nginx/sites-available/lms`:

```nginx
server {
    listen 80;
    server_name lms.domainanda.id;
    root /var/www/lms/public;
    index index.php;

    # Bukti transfer 4 MB, berkas tugas 10 MB
    client_max_body_size 12M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Aset hasil build dan Pyodide: cache panjang
    location ~* ^/(build|vendor/pyodide)/ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/lms /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
# HTTPS gratis dengan Let's Encrypt
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d lms.domainanda.id
```

Pastikan `/etc/nginx/mime.types` memetakan `application/wasm wasm;` dan `.mjs` sebagai JavaScript (`application/javascript js mjs;`). Tanpa itu, Python di browser gagal dimuat. Nginx versi baru sudah menyertakannya; tambahkan manual bila belum.

### PHP

Di `/etc/php/8.3/fpm/php.ini` (nilai bawaan PHP `upload_max_filesize=2M` terlalu kecil):

```ini
upload_max_filesize = 10M
post_max_size = 12M
memory_limit = 256M
expose_php = Off

opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 0   ; wajib reload php-fpm setelah deploy
```

```bash
sudo systemctl restart php8.3-fpm
```

### Di belakang proxy / Cloudflare / load balancer

`bootstrap/app.php` belum mengatur trusted proxies. Bila aplikasi berada di belakang reverse proxy yang mengakhiri HTTPS, tambahkan di `withMiddleware`:

```php
$middleware->trustProxies(at: '*');   // atau daftar IP proxy Anda
```

Tanpa ini, URL yang dibuat aplikasi bisa memakai `http://` dan IP yang tercatat untuk rate limit adalah IP proxy.

## Scheduler (cron) dan queue

### Scheduler

Tugas terjadwal didefinisikan di `routes/console.php`:

| Perintah | Jadwal | Fungsi |
|---|---|---|
| `sessions:generate` | Harian 01:00 WIB | Membuat sesi kelas dari jadwal mingguan untuk 4 minggu ke depan (`config/sessions.php`, `generate_weeks_ahead`) |
| `xp:sync` | Harian 01:30 WIB | Menghitung ulang XP dan lencana dari data sumber |
| `sessions:remind-reports` | Tiap jam | Mengingatkan guru yang belum mengisi laporan sesi 2 jam setelah sesi berakhir |

Pasang satu baris cron untuk user web:

```bash
sudo crontab -u www-data -e
```

```cron
* * * * * cd /var/www/lms && php artisan schedule:run >> /dev/null 2>&1
```

Periksa dengan `php artisan schedule:list`. Tanpa cron, sesi baru tidak muncul, XP tidak diperbarui otomatis, dan pengingat guru tidak terkirim.

Perintah ini juga bisa dijalankan manual:

```bash
php artisan sessions:generate --weeks=8     # mis. setelah membuat banyak kelas baru
php artisan xp:sync                          # semua siswa; --user=ID untuk satu siswa
php artisan sessions:remind-reports --hours=2
```

### Queue

Saat ini **tidak ada job yang diantrekan**: notifikasi in-app ditulis langsung ke tabel `notifications`, dan email reset kata sandi dikirim langsung. Worker queue belum wajib.

Worker tetap disarankan agar siap ketika kanal WhatsApp/email ditambahkan. Contoh Supervisor `/etc/supervisor/conf.d/lms-worker.conf`:

```ini
[program:lms-worker]
command=php /var/www/lms/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
numprocs=1
autostart=true
autorestart=true
stopwaitsecs=3600
redirect_stderr=true
stdout_logfile=/var/www/lms/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start lms-worker
```

## Storage dan berkas unggahan

Semua unggahan disimpan di disk `local` (`storage/app/private`), **tidak** bisa diakses langsung lewat URL, dan hanya dilayani lewat controller yang mengecek hak akses. Karena itu `php artisan storage:link` **tidak diperlukan**.

| Isi | Lokasi | Batas |
|---|---|---|
| Bukti transfer orang tua | `storage/app/private/payment-proofs/{tagihan}` | JPG/PNG/WEBP/PDF, 4 MB |
| Bukti transfer honor tutor | `storage/app/private/payout-proofs/{id}` | JPG/PNG/WEBP/PDF, 4 MB |
| Berkas tugas siswa | `storage/app/private/submissions/{pelajaran}` | JPG, PNG, PDF, ZIP, SB3, PY, HTML, TXT, JS, CSS; 10 MB |
| Gambar Tanya Jawab | `storage/app/private/forum/{kelas}` | Gambar, 4 MB |

Folder `storage` dan `bootstrap/cache` harus bisa ditulis user PHP-FPM. Seluruh `storage/app/private` wajib masuk backup. Bila ingin memindahkan unggahan ke object storage (S3/R2), ubah disk di controller modul terkait; saat ini kode memakai `Storage::disk('local')` secara eksplisit.

## Optimasi

```bash
php artisan optimize        # cache config, route, event, dan view
php artisan optimize:clear  # hapus semua cache (saat debugging)
```

Jalankan `php artisan optimize` setiap deploy dan setiap mengubah `.env`. Aktifkan OPcache (lihat [PHP](#php)).

## Backup dan restore

Yang wajib dibackup:

1. **Database** (semua data akademik, keuangan, nilai).
2. **`storage/app/private`** (bukti transfer, tugas, gambar).
3. **`.env`** (terutama `APP_KEY`). Simpan terpisah dan aman; tanpa `APP_KEY` yang sama, session dan data terenkripsi tidak terbaca.

Contoh skrip `/usr/local/bin/lms-backup.sh`:

```bash
#!/usr/bin/env bash
set -euo pipefail
APP=/var/www/lms
DEST=/var/backups/lms
STAMP=$(date +%Y%m%d-%H%M)
mkdir -p "$DEST"

# MySQL (simpan kredensial di ~/.my.cnf user backup, bukan di skrip)
mysqldump --single-transaction --routines lms | gzip > "$DEST/db-$STAMP.sql.gz"
# PostgreSQL: pg_dump -Fc lms > "$DEST/db-$STAMP.dump"
# SQLite:     sqlite3 "$APP/database/database.sqlite" ".backup '$DEST/db-$STAMP.sqlite'"

tar -czf "$DEST/files-$STAMP.tar.gz" -C "$APP" storage/app/private .env

# Simpan 14 hari terakhir
find "$DEST" -type f -mtime +14 -delete
```

```cron
# crontab root: setiap hari 02:30 WIB
30 2 * * * /usr/local/bin/lms-backup.sh
```

Salin hasil backup ke lokasi lain (object storage, server lain, atau Google Drive dengan `rclone`). Backup yang hanya ada di server yang sama ikut hilang bila server rusak.

**Restore:**

```bash
php artisan down
gunzip < db-YYYYMMDD-HHMM.sql.gz | mysql lms
tar -xzf files-YYYYMMDD-HHMM.tar.gz -C /var/www/lms
php artisan optimize && php artisan up
```

Uji restore secara berkala di server staging.

## Keamanan

Checklist sebelum go-live:

- [ ] `APP_ENV=production`, `APP_DEBUG=false`.
- [ ] Tidak ada data demo. Periksa: `php artisan tinker --execute='echo App\Models\User::where("username","budi")->exists() ? "ADA DATA DEMO" : "bersih";'`
- [ ] Admin memakai kata sandi kuat; tidak ada akun dengan kata sandi `password`.
- [ ] HTTPS aktif, `SESSION_SECURE_COOKIE=true`, HTTP dialihkan ke HTTPS.
- [ ] Rekening `FINANCE_BANK_*` sudah benar.
- [ ] `.env` tidak bisa diakses dari web (root Nginx harus `public/`), izin file `640`.
- [ ] `storage/` dan `bootstrap/cache` bisa ditulis PHP; folder lain milik user deploy, bukan `www-data`.
- [ ] Firewall hanya membuka port 22, 80, 443 (`ufw allow OpenSSH; ufw allow 'Nginx Full'; ufw enable`). Port database tidak terbuka ke publik.
- [ ] Backup harian berjalan dan pernah diuji restore.
- [ ] `composer install --no-dev` (paket dev tidak terpasang).

Yang sudah ditangani aplikasi:

- Hak akses ditegakkan di backend dengan middleware `can:`/`role:` dan Policy, bukan hanya menyembunyikan menu. Admin lolos semua cek lewat `Gate::before`, sementara aturan status bisnis dicek eksplisit.
- Pendaftaran publik dimatikan; akun nonaktif tidak bisa login.
- Rate limit: login (bawaan Breeze), kirim soal coding (30/menit), tanya (20/menit), jawab (30/menit), kartu kanban (60/menit), verifikasi sertifikat (30/menit), feed kalender (60/menit).
- Unggahan disimpan privat dan divalidasi jenis serta ukurannya.
- Feed iCal memakai token acak 40 karakter per user yang bisa dibuat ulang; verifikasi sertifikat publik menyingkat nama belakang.
- Tes tersembunyi soal coding tidak pernah dikirim ke browser; kode siswa tidak dijalankan di server.

## Deploy dan update

Skrip deploy sederhana (`deploy.sh` di server):

```bash
#!/usr/bin/env bash
set -euo pipefail
cd /var/www/lms

php artisan down --retry=15          # halaman pemeliharaan
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan db:seed --force --class="Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder"
php artisan optimize
php artisan queue:restart            # bila worker queue dipakai
sudo systemctl reload php8.3-fpm     # wajib bila opcache.validate_timestamps=0
php artisan up
```

Catatan:

- Backup database sebelum update yang membawa migration baru.
- Seeder permission aman diulang dan memastikan permission modul baru terpasang ke role yang benar.
- Untuk deploy tanpa downtime, gunakan pola rilis per folder (mis. Laravel Envoyer, Deployer, atau symlink `current` → `releases/<tanggal>`) dengan `storage` dan `.env` yang dibagi antar rilis.
- **Menambah atau menonaktifkan modul**: ubah daftar di `config/modules.php`, lalu jalankan migrate, seeder permission, dan `php artisan optimize`. Menonaktifkan modul tidak menghapus tabelnya.

## Log dan pemantauan

- Log aplikasi: `storage/logs/laravel.log` (atau `laravel-YYYY-MM-DD.log` bila `LOG_STACK=daily`). Pantau langsung dengan `tail -f storage/logs/laravel*.log`.
- Health check: `GET /up` mengembalikan 200 bila aplikasi hidup. Pakai untuk uptime monitor (UptimeRobot, Better Stack, dll.).
- Log Nginx: `/var/log/nginx/error.log`; log PHP-FPM: `/var/log/php8.3-fpm.log`.
- Cek scheduler: `php artisan schedule:list` (waktu berikutnya tiap tugas).

## Menyesuaikan halaman depan

Isi halaman promosi di `/` (nomor WhatsApp, email, jam operasional, program, harga, testimoni, FAQ) ada di `Modules/Landing/resources/js/site.js`. Nilai bawaan masih **contoh** (mis. `waNumber: '6281234567890'`). Ubah, commit, lalu build ulang aset (`npm run build`).

Nama brand, logo, dan warna ada di `resources/css/app.css` dan `public/`. Rekening transfer diatur lewat `.env` (`FINANCE_BANK_*`).

## Troubleshooting

| Gejala | Penyebab umum | Solusi |
|---|---|---|
| Halaman putih atau 500 | Izin `storage`/`bootstrap/cache`, `APP_KEY` kosong, atau ekstensi PHP kurang | Lihat `storage/logs/laravel.log`; `chmod -R ug+rwX storage bootstrap/cache`; `php artisan key:generate` (hanya pada instalasi baru) |
| `Vite manifest not found` | Aset belum dibuild | `npm ci && npm run build` |
| Perubahan `.env` tidak berpengaruh | Config di-cache | `php artisan optimize` |
| Login demo `admin`/`password` tidak bisa | Benar di production: data demo tidak dibuat | Buat admin lewat [Tinker](#membuat-admin-pertama) |
| Menu atau halaman modul hilang, 403 | Permission belum di-seed atau cache permission | Jalankan seeder permission lalu `php artisan permission:cache-reset` |
| "Akun nonaktif" / tidak bisa login | Status user `inactive` atau terhapus | Admin mengubah status di menu Pengguna |
| Lupa kata sandi tidak mengirim email | `MAIL_MAILER=log` atau SMTP salah; user tanpa email | Isi `MAIL_*`; cek log; untuk user tanpa email, admin mengganti sandi |
| Unggah bukti/tugas gagal ("413" atau "gagal diunggah") | Batas Nginx/PHP | `client_max_body_size 12M`, `upload_max_filesize=10M`, `post_max_size=12M`, restart php-fpm |
| Soal Python macet di "Menyiapkan Python…" | `public/vendor/pyodide` tidak ada, MIME `.wasm`/`.mjs` salah, atau diblokir CSP | Jalankan `npm run build`; cek `curl -I https://domain/vendor/pyodide/pyodide.asm.wasm` harus `application/wasm` |
| Sesi kelas tidak muncul untuk minggu depan | Cron scheduler tidak jalan | Pasang cron; jalankan `php artisan sessions:generate` manual |
| XP/lencana tidak bertambah | `xp:sync` belum jalan | Halaman Prestasiku juga menyinkronkan saat dibuka; cek cron |
| Jam sesi bergeser 7 jam | Zona waktu | `APP_TIMEZONE=Asia/Jakarta`, lalu `php artisan optimize` |
| Tautan kalender/QR sertifikat memakai domain salah | `APP_URL` salah | Perbaiki `APP_URL`, `php artisan optimize` |
| Redirect ke `http://` di belakang Cloudflare | Trusted proxies belum diatur | Lihat [Di belakang proxy](#di-belakang-proxy--cloudflare--load-balancer) |
| Error `419 Page Expired` | Session kedaluwarsa atau cookie secure di HTTP | Muat ulang halaman; pastikan HTTPS bila `SESSION_SECURE_COOKIE=true` |

## Belum siap dan keterbatasan

Hal berikut belum tersedia atau perlu keputusan/infrastruktur tambahan. Detail lengkap ada di [audit kelengkapan](audit-kelengkapan.md).

| Butir | Status | Catatan |
|---|---|---|
| Notifikasi WhatsApp | Belum | Notifikasi hanya in-app (lonceng). Tabel `notifications` sudah siap dibaca kanal pengirim; butuh akun penyedia WA API (mis. Fonnte, Wablas, WhatsApp Cloud API) dan nomor pengirim, lalu job antrean yang mengirim pesan. Setelah itu worker queue menjadi wajib |
| Notifikasi email | Belum | Email hanya dipakai untuk reset kata sandi |
| Audit log | Belum | Belum ada jejak siapa mengubah nilai, memverifikasi pembayaran, atau mengubah role |
| Laporan berkala orang tua (PDF) | Belum | Rapor tersedia online; belum ada rekap bulanan PDF |
| Judge0 (C++/Java, penilaian di server) | Sebagian | Driver siap (`CODING_DRIVER=judge0`), tetapi server Judge0 belum dipasang. Judge0 butuh VPS terpisah (Docker) dan jangan dipasang di server yang sama dengan aplikasi |
| Integrasi Zoom API | Belum | Link Zoom diisi manual per kelas |
| Payment gateway (QRIS/VA) | Sengaja tidak | Sesuai keputusan: transfer manual + verifikasi admin |
| Login Google, MFA admin | Belum | Login username/email + kata sandi |
| PWA, streak harian, pipeline calon siswa | Belum | Lihat audit kelengkapan |
| Trusted proxies | Perlu diatur | Bila memakai Cloudflare/load balancer, lihat bagian Nginx |
| Database selain SQLite | Perlu diuji | Test otomatis berjalan di SQLite; uji MySQL/PostgreSQL di staging |
| Unggahan di object storage | Belum | Disk `local` ditulis langsung di kode; berkas ada di disk server |
| Tautan rapor untuk guru | Belum | Guru membuka rapor lewat alamat `/progress/{id}`; orang tua punya tautan di menu |
| Isi halaman depan | Contoh | Kontak, harga, dan testimoni di `site.js` masih placeholder |
