# Fitur yang bisa ditambahkan

Daftar fitur yang belum dibuat. Semua yang ada di daftar ini boleh dikerjakan kapan saja; urutan di bawah adalah saran prioritas. Fitur yang sudah selesai dicatat di `AI_CONTEXT.md` (bagian 9) dan di PR masing-masing.

Status per 13 Oktober 2026. Perkiraan ukuran: **S** (≤ 1 hari), **M** (2–4 hari), **L** (≥ 1 minggu).

## Ringkasan

| No. | Fitur | Untuk siapa | Prioritas | Ukuran | Butuh dari pemilik |
|---|---|---|---|---|---|
| 1 | [Persiapan produksi](#1-persiapan-produksi) | Tim teknis | Tinggi (sebelum deploy) | M | — |
| 2 | [Deploy produksi](#2-deploy-produksi) | Semua | Tinggi | M | Hosting, domain, database, SMTP, rekening bank |
| 3 | [Isi landing page asli](#3-isi-landing-page-asli) | Calon orang tua | Tinggi (sebelum deploy) | S | WhatsApp, email, harga, testimoni |
| 4 | [Notifikasi email dan WhatsApp](#4-notifikasi-email-dan-whatsapp) | Orang tua, guru | Sedang | M | Akun provider WhatsApp |
| 5 | [Streak harian dan PWA](#5-streak-harian-dan-pwa) | Siswa | Sedang | M | — |
| 6 | [Orang tua mengajukan jadwal ulang](#6-orang-tua-mengajukan-jadwal-ulang) | Orang tua, admin | Sedang | M | — |
| 7 | [Soal Scratch/Blockly dinilai otomatis](#7-soal-scratchblockly-dinilai-otomatis) | Siswa usia 7–10, guru | Sedang | L | — |
| 8 | [Fitur lanjutan lain](#8-fitur-lanjutan-lain) | Bervariasi | Rendah / opsional | Bervariasi | Keputusan bisnis |

## 1. Persiapan produksi

Supaya aplikasi aman dan stabil di server sungguhan.

- **Trusted proxies** lewat `.env`, agar URL HTTPS benar di belakang Cloudflare atau load balancer.
- **Penyimpanan unggahan yang bisa diganti**: disk `local` atau S3-compatible (misalnya Cloudflare R2, Wasabi) lewat `.env`. Berlaku untuk bukti transfer, tugas, proyek Scratch, dan sertifikat.
- **Health check** (`/up` atau sejenisnya) untuk monitoring uptime.
- Pastikan langkah cron dan antrean (queue) di `docs/production.md` lengkap.

**Selesai bila:** aplikasi berjalan di belakang proxy dengan URL HTTPS yang benar, unggahan berfungsi di disk `local` dan `s3` (diuji), dan dokumentasinya diperbarui.

## 2. Deploy produksi

Menjalankan aplikasi di domain pemilik mengikuti `docs/production.md`: HTTPS, cron, backup harian, akun admin pertama, `npm run build` (termasuk editor Scratch).

**Butuh dari pemilik:** VPS/hosting, domain, pilihan database (MySQL 8 atau PostgreSQL 16; keduanya sudah diuji di CI), SMTP untuk reset kata sandi, dan data rekening bank untuk tagihan (`FINANCE_BANK_*`).

## 3. Isi landing page asli

Mengganti data contoh di `Modules/Landing/resources/js/site.js`: nomor WhatsApp, email, jam operasional, harga per program, daftar program, dan testimoni. Testimoni hanya ditampilkan bila asli dan disetujui; kalau belum ada, bagian testimoni disembunyikan.

**Butuh dari pemilik:** semua data di atas.

## 4. Notifikasi email dan WhatsApp

Saat ini notifikasi hanya muncul di lonceng aplikasi. Fitur ini mengirim notifikasi yang sama lewat:

- **Email** (bisa langsung dibuat): PR baru dari sesi, tugas dinilai, tagihan baru dan pembayaran terverifikasi, laporan bulanan siap, jawaban Q&A.
- **WhatsApp**: lewat provider pilihan pemilik (Fonnte, Wablas, atau WhatsApp Cloud API).
- Setiap pengguna bisa memilih saluran yang diinginkan. Pengiriman memakai antrean, jadi server butuh queue worker.

**Butuh dari pemilik:** provider WhatsApp dan nomor pengirim (bagian email bisa dikerjakan lebih dulu).

## 5. Streak harian dan PWA

- **Streak harian**: jumlah hari berturut-turut siswa belajar (menyelesaikan task, hadir, atau berkarya), tampil di beranda dan halaman prestasi, dihitung dengan zona waktu WIB.
- **PWA**: aplikasi bisa "dipasang" ke layar utama HP dengan ikon Alprogmatics, terbuka tanpa bilah browser.

## 6. Orang tua mengajukan jadwal ulang

Orang tua mengajukan pindah jadwal sesi (alasan dan waktu yang diinginkan). Admin atau guru menyetujui atau menolak. Persetujuan memakai fitur reschedule yang sudah ada (termasuk cek bentrok jadwal), dan orang tua mendapat notifikasi.

## 7. Soal Scratch/Blockly dinilai otomatis

Saat ini tugas Scratch dinilai manual oleh guru. Untuk siswa usia 7–10, bisa ditambah tipe task baru berbasis blok (Blockly, atau pengecekan proyek Scratch) yang dinilai otomatis seperti soal coding Python: guru membuat soal dan wajib menguji solusinya, siswa mengerjakan dari peta Course, dan hasilnya masuk ke progres dan XP.

## 8. Fitur lanjutan lain

Opsional, dikerjakan bila dibutuhkan:

| Fitur | Keterangan |
|---|---|
| Judge0 untuk C++/Java | Butuh VPS terpisah untuk menjalankan kode di server |
| Tes penempatan adaptif | Menentukan level awal siswa baru secara otomatis |
| Guru melihat kode siswa secara langsung | Selama kelas Zoom, guru memantau editor siswa |
| Riwayat pengumpulan tugas | Semua versi yang pernah dikirim siswa, bukan hanya yang terakhir |
| Bagian course dan bookmark | Mengelompokkan materi dan menandai materi favorit |
| Pengingat pertemuan orang tua–guru | Otomatis setiap N sesi |
| Login Google dan MFA admin | Keamanan dan kemudahan login |
| Integrasi Zoom API | Membuat link Zoom dan mengambil rekaman otomatis |
| Multi-cabang | Beberapa cabang kursus dalam satu aplikasi, dengan istilah yang bisa diganti |
| Mode gelap dan `DESIGN.md` | Tema gelap dan dokumentasi sistem desain |
| Isi kurikulum lengkap | Prototipe klien menyebut 32 lesson per level; data demo baru berisi beberapa. Diisi guru lewat aplikasi |

## Catatan yang belum terjawab

- **Menu More "belum berfungsi"** (masukan klien): tidak bisa direproduksi. Semua item berfungsi di pengujian. Menunggu detail dari klien: item yang diklik, browser, dan apakah sudah memakai build terbaru.
- **Alamat server demo** di panduan uji coba (`docs/panduan-uji-coba/README.md`) masih contoh.
