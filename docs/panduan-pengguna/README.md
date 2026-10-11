# Panduan Pengguna Alprogmatics LMS

Panduan ini menjelaskan cara memakai aplikasi untuk setiap peran. Pilih panduan sesuai akun Anda:

| Peran | Untuk siapa | Panduan |
|---|---|---|
| Admin | Pengelola kursus: data pengguna, kelas, jadwal, keuangan | [admin.md](admin.md) |
| Guru | Tutor yang mengajar kelas, membuat materi, menilai | [guru.md](guru.md) |
| Siswa | Anak yang belajar | [siswa.md](siswa.md) |
| Orang tua | Orang tua atau wali yang memantau anak dan membayar tagihan | [orang-tua.md](orang-tua.md) |

Untuk pemasangan di server, lihat [dokumentasi production](../production.md).

## Hal yang sama untuk semua peran

### Masuk (login)

![Halaman login](img/login.png)

1. Buka alamat aplikasi, lalu klik **Masuk** di halaman depan (atau langsung buka `/login`).
2. Isi **username atau email** dan kata sandi. Anak tidak wajib punya email; cukup username yang dibuat admin.
3. Klik **Masuk**. Anda akan diarahkan ke **Beranda**, yang isinya berbeda untuk tiap peran.

Catatan:

- Tidak ada pendaftaran mandiri. Semua akun dibuat oleh admin.
- Akun berstatus **Nonaktif** tidak bisa login. Hubungi admin bila ini terjadi.
- **Lupa kata sandi** mengirim tautan reset ke email akun. Akun tanpa email (umumnya siswa) harus meminta admin mengganti kata sandinya.

### Navigasi

- **Menu samping** (sidebar) dikelompokkan menjadi Utama, Akademik, Belajar, Pengguna, dan Keuangan. Menu yang tampil disaring otomatis sesuai peran, jadi setiap peran melihat menu yang berbeda.
- Di HP, menu samping dibuka lewat tombol menu di kiri atas.
- **Lonceng** di kanan atas menampilkan notifikasi terbaru. Titik merah berarti ada notifikasi yang belum dibaca.
- **Menu profil** (nama Anda di kanan atas) berisi **Profil saya** dan **Keluar**.

### Profil dan kata sandi

Buka menu profil, pilih **Profil saya**. Di sana Anda bisa mengubah nama dan email, serta mengganti kata sandi (isi kata sandi lama, lalu kata sandi baru dua kali).

### Notifikasi

Halaman **Notifikasi** (`/notifikasi`, juga bisa dibuka dari lonceng) berisi kabar yang relevan untuk Anda, misalnya:

| Peran | Contoh notifikasi |
|---|---|
| Admin | Bukti transfer baru dari orang tua |
| Guru | Pertanyaan baru dari siswa, pengingat laporan sesi yang belum diisi |
| Siswa | PR dari sesi, tugas dinilai atau diminta revisi, jawaban atas pertanyaan |
| Orang tua | Tagihan baru, hasil verifikasi pembayaran, ringkasan sesi anak |

![Halaman notifikasi](img/notifikasi.png)

Klik notifikasi untuk membuka halaman terkait. Tombol **Tandai semua dibaca** menghapus tanda belum dibaca sekaligus.

### Jadwal di kalender HP

Halaman **Jadwal** menampilkan sesi kelas per minggu (semua waktu dalam WIB). Di bagian bawahnya ada **Tampilkan jadwal di kalender HP**:

1. Klik **Buat tautan kalender**.
2. Salin tautan yang muncul, lalu tambahkan sebagai kalender langganan (Google Calendar: *Tambahkan kalender → Dari URL*; iPhone: *Pengaturan → Kalender → Akun → Tambah akun → Lainnya → Tambah kalender langganan*).
3. Jadwal kelas akan muncul dan diperbarui otomatis di kalender HP.

Tautan ini bersifat pribadi. Bila tersebar, klik **Buat ulang**; tautan lama langsung tidak berlaku.

### Masuk kelas Zoom

Setiap sesi punya tombol **Masuk Zoom** di Beranda dan di halaman kelas. Link, Meeting ID, dan passcode diatur admin per kelas.

### Istilah

| Istilah | Arti |
|---|---|
| Kelas | Rombongan belajar dengan guru, jadwal mingguan, dan link Zoom |
| Sesi | Satu pertemuan kelas pada tanggal tertentu, dibuat otomatis dari jadwal mingguan |
| Kursus | Kurikulum berisi pelajaran (materi, kuis, tugas, soal coding) yang dipasang ke satu atau beberapa kelas |
| Pelajaran | Satu isi kursus: materi bacaan/video, kuis, tugas, atau soal coding |
| Misi Pemanasan / Misi Akhir | Kuis awal dan akhir kursus (pre-test dan post-test) untuk mengukur kenaikan kemampuan |
| Paket kuota | Paket berisi sejumlah sesi (mis. 8 sesi) yang dibayar di muka dan terpotong tiap sesi |
| Proyek | Tugas berkarya langkah demi langkah dengan papan kanban per siswa |
| XP, level, lencana | Poin dan penghargaan yang dihitung otomatis dari aktivitas belajar |
