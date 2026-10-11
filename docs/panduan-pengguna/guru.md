# Panduan Guru

Guru mengajar kelas lewat Zoom, mengisi absensi dan catatan setiap sesi, membuat kursus beserta materi, kuis, tugas, dan soal coding, menjawab pertanyaan siswa, membimbing proyek, serta menerbitkan sertifikat.

Baca dulu [hal yang sama untuk semua peran](README.md#hal-yang-sama-untuk-semua-peran) (login, notifikasi, kalender HP).

## Beranda

![Beranda guru](img/guru-beranda.png)

Beranda guru berisi:

- **Kelas aktif, Total siswa, Sesi selesai bulan ini, Honor belum direkap**.
- **Jadwal hari ini** dengan tombol **Masuk Zoom**.
- Kartu tugas yang menunggu Anda: sesi yang belum diisi laporannya, tugas siswa yang perlu dinilai, kartu proyek yang perlu dicek, dan pertanyaan yang belum dijawab.
- **Kelas yang kamu ajar**.

## Kelas dan jadwal

- Menu **Kelas** menampilkan kelas yang Anda ajar. Buka kelas untuk melihat daftar siswa, link Zoom, dan sesi dua minggu terakhir serta yang akan datang.
- Menu **Jadwal** menampilkan sesi per minggu. Pasang jadwal ke kalender HP lewat **Buat tautan kalender** (lihat [panduan umum](README.md#jadwal-di-kalender-hp)).
- Kelas, jadwal, dan daftar siswa diatur oleh admin.

## Mengisi absensi dan catatan sesi

Ini tugas terpenting setelah mengajar. Saat Anda menyelesaikan sesi, kuota paket siswa terpotong, orang tua menerima catatan, PR terkirim ke siswa, dan honor Anda tercatat.

![Daftar sesi](img/guru-sesi.png)

1. Buka **Sesi & Absensi**, lalu pilih sesi (atau klik sesi dari Beranda).
2. Di **Absensi**, pilih status setiap siswa: **Hadir**, **Izin**, **Sakit**, atau **Alpa**. Tombol **Tandai semua hadir** mempercepat. Keterangan per siswa opsional.
3. Di **Catatan sesi**, isi:
   - **Materi yang dibahas** (wajib), mis. "Perulangan for dan while".
   - **PR / tugas**: dikirim sebagai notifikasi ke siswa.
   - **Catatan untuk orang tua**: terlihat di portal orang tua.
   - **Catatan internal**: hanya untuk admin dan guru.
   - **Link rekaman Zoom** (opsional).
   - Centang **Tandai untuk admin** bila ada kendala (siswa kesulitan, masalah teknis) yang perlu ditindaklanjuti.
4. Klik **Simpan & selesaikan sesi**.

![Isi laporan sesi](img/guru-sesi-isi.png)

Catatan:

- **Hadir** dan **Alpa** memotong kuota paket siswa. **Izin** dan **Sakit** tidak.
- Laporan bisa diubah lagi setelah selesai (tombol berubah menjadi **Simpan perubahan**).
- Bila laporan belum diisi sekitar dua jam setelah sesi berakhir, Anda mendapat notifikasi pengingat.

### Pindah jadwal

Bila sesi harus digeser (mis. siswa ujian sekolah), buka sesi itu dan klik **Pindah jadwal**. Isi tanggal, jam mulai, jam selesai, dan alasan, lalu **Simpan jadwal baru**. Sesi lama berstatus *Dipindah* dan tertaut ke sesi baru. Sistem menolak jadwal yang bentrok dengan kelas Anda yang lain. Pembatalan sesi dilakukan oleh admin.

## Kursus dan materi

Kursus adalah kurikulum yang bisa dipakai beberapa kelas sekaligus. Siswa melihat kursus yang dipasang ke kelasnya dan berstatus **Terbit**.

![Halaman kursus](img/guru-kursus.png)

### Membuat kursus

1. **Kursus & Materi → Kursus baru**.
2. Isi judul, mata pelajaran, level, deskripsi, warna sampul, dan status (**Draf** atau **Terbit**).
3. **Simpan**, lalu di halaman kursus bagian **Dipakai di kelas**, pilih kelas dan klik **Pasang ke kelas**. Klik **Lepas** untuk melepasnya.

### Menambah isi kursus

Di halaman kursus, gunakan tombol **Materi**, **Kuis**, **Tugas**, atau **Coding**. Urutkan dengan tombol **Naikkan/Turunkan**. Setiap pelajaran punya pilihan **Tampilkan ke siswa**; matikan untuk menyimpannya sebagai draf.

**Materi**: judul, isi (Markdown: judul, daftar, `kode`, tautan), link video YouTube/Vimeo, dan perkiraan durasi. Siswa menandai materi selesai setelah membaca.

**Kuis** (dinilai otomatis):

![Form kuis](img/guru-kuis-form.png)

1. Tulis pertanyaan, lalu pilihan jawaban. Klik lingkaran untuk menandai jawaban benar (bisa satu atau beberapa jawaban benar).
2. Opsional: pembahasan (tampil setelah siswa lulus), konsep soal (mis. "perulangan"), dan poin.
3. **Pengaturan**: nilai lulus (0–100), batas percobaan, dan **Jadikan misi**:
   - **Kuis biasa**.
   - **Misi Pemanasan**: dikerjakan di awal kursus (pre-test). Tidak memengaruhi nilai.
   - **Misi Akhir**: dikerjakan di akhir kursus (post-test).
   Hasil kedua misi dibandingkan per konsep untuk laporan **Kenaikan kemampuan** di rapor. Pakai soal berbeda dengan konsep yang sama.

Kunci jawaban baru terlihat siswa setelah mereka lulus. Di halaman kuis, guru melihat **Hasil siswa** serta soal dan kuncinya.

**Tugas**: judul, instruksi, dan nilai maksimal. Siswa mengumpulkan jawaban teks, tautan proyek, dan/atau berkas (JPG, PNG, PDF, ZIP, SB3, PY, HTML, TXT, JS, CSS; maks. 10 MB).

### Soal coding

![Form soal coding](img/guru-soal-coding.png)

Kode siswa dijalankan di browser mereka, lalu server membandingkan hasilnya dengan jawaban yang hanya disimpan di server.

1. Di halaman kursus, klik **Coding**.
2. Isi judul dan cerita soal, lalu pilih **Bahasa** (Python, JavaScript, HTML/CSS) dan **Cara menilai**:
   - **Input → output**: program membaca input lalu mencetak output. Cocok untuk pemula.
   - **Fungsi**: siswa menulis fungsi dengan nama tertentu; tiap tes berisi argumen (JSON) dan nilai return yang diharapkan.
   - **Cek HTML/CSS**: halaman siswa dicek elemen, teks, atribut, dan gaya CSS-nya (dengan selector CSS).
   - **Susun baris (Parsons)**: siswa menyusun baris solusi yang diacak. Tidak perlu tes.
3. Isi **Kode awal** untuk siswa dan **Solusi** Anda.
4. Tambah **tes**: input/argumen, output yang diharapkan, bobot, pesan bila gagal, dan tandai **tersembunyi** untuk kasus tepi. Jawaban tes tersembunyi tidak pernah dikirim ke browser siswa.
5. Tambah **Petunjuk bertingkat** (maks. 5), yang terbuka satu per satu saat siswa memintanya.
6. **Pengaturan**: konsep, level (1–5), durasi, batas waktu (ms), **Solusi terbuka setelah gagal N kali**, dan **Bedakan huruf besar/kecil**.
7. Klik **Simpan & uji solusi**. Soal baru terlihat siswa bila solusi Anda **lulus semua tes** (Uji solusi). Bila belum lulus, soal tetap draf.

Di halaman soal, guru melihat kode terbaru tiap siswa, cocok untuk dibahas bersama saat sesi Zoom.

## Penilaian tugas

![Penilaian tugas](img/guru-penilaian.png)

1. Buka **Penilaian Tugas**. Tab: **Perlu dinilai**, **Menunggu revisi**, **Sudah dinilai**.
2. Baca jawaban, buka tautan atau unduh berkas siswa.
3. Isi nilai dan umpan balik, lalu klik **Simpan nilai**. Atau klik **Minta revisi** dengan catatan apa yang perlu diperbaiki.
4. Siswa (dan orang tua) menerima notifikasi. Nilai yang sudah diberikan bisa diubah lewat **Ubah nilai**.

## Rapor progres siswa

Rapor siswa menampilkan progres setiap kursus: persentase selesai, rata-rata kuis, rata-rata tugas, dan **Kenaikan kemampuan** dari Misi Pemanasan ke Misi Akhir. Kenaikan dihitung sebagai (akhir − awal) ÷ (100 − awal) dan diberi label Tinggi (≥ 70%), Sedang (≥ 30%), atau Rendah.

Saat ini rapor belum punya tautan di menu guru. Buka lewat alamat `/progress/<ID siswa>` (ID terlihat di alamat halaman prestasi siswa, `/prestasi/siswa/<ID>`). Guru hanya bisa membuka rapor siswa di kelas yang ia ajar. Hasil kuis per pelajaran juga terlihat di bagian **Hasil siswa** pada halaman kuis.

## Tanya Jawab

![Tanya jawab guru](img/guru-tanya.png)

Siswa bertanya per kelas, opsional terkait pelajaran tertentu.

1. Buka **Tanya Jawab**. Mulai dari tab **Belum dijawab**. Beranda juga menampilkan pertanyaan yang belum dijawab.
2. Buka pertanyaan, tulis jawaban (bisa menambahkan kode), klik **Kirim jawaban**. Jawaban guru ditandai khusus dan tampil paling atas. Penanya mendapat notifikasi.
3. Klik **Pilih jawaban terbaik** pada jawaban yang paling tepat (penanya juga bisa).
4. Klik **Sematkan** untuk pertanyaan yang sering ditanyakan agar tampil di atas untuk seluruh kelas.

Pertanyaan bertanda **Hanya guru** hanya terlihat oleh guru, admin, penanya, dan orang tuanya. Pertanyaan **anonim untuk teman** tetap menampilkan nama penanya kepada guru dan orang tua.

## Proyek (kanban)

Setiap siswa di kelas otomatis mendapat papan kanban sendiri dengan kolom **Mau dikerjakan → Sedang dikerjakan → Minta dicek guru → Selesai**.

### Membuat proyek

![Form proyek](img/guru-proyek-form.png)

1. **Proyek → Proyek baru**.
2. Pilih kelas, ikon, judul, tenggat (opsional), dan penjelasan untuk siswa.
3. Atur **Guru mengecek setiap langkah**. Bila aktif, siswa memindahkan kartu ke "Minta dicek guru" dan Anda menyetujui atau mengembalikannya. Matikan agar siswa bisa langsung menandai selesai.
4. Isi **Langkah bawaan**, atau klik **Pakai contoh** (Game Scratch, Program Python, Website). Siswa juga bisa menambah kartu sendiri.
5. Simpan. Langkah yang diubah kemudian ikut berubah di papan siswa yang belum menyelesaikannya.

### Memantau dan mengecek

![Ringkasan proyek](img/guru-proyek-ringkasan.png)

- Halaman proyek menampilkan **Papan siswa** (progres setiap siswa) dan antrean **Perlu dicek**.
- Pada kartu yang menunggu, klik **Setujui**, atau **Kembalikan** dengan catatan "apa yang perlu diperbaiki" lalu **Kirim catatan**.
- Buka papan siswa untuk melihat kartu dan **Hasil proyek** (link karya dan cerita singkat).
- Ubah status menjadi **Diarsipkan** untuk mengunci papan siswa setelah proyek selesai.

![Papan siswa dilihat guru](img/guru-proyek-papan.png)

## Prestasi siswa

![Prestasi siswa per kelas](img/guru-prestasi-kelas.png)

Menu **Prestasi Siswa** menampilkan level, XP total, XP bulan ini, dan lencana siswa per kelas. XP dihitung otomatis dari kehadiran, materi, kuis, tugas, soal coding, proyek, dan tanya jawab, jadi guru tidak perlu memberi poin manual.

## Sertifikat

![Sertifikat untuk guru](img/guru-sertifikat.png)

1. Buka **Sertifikat**. Siswa yang sudah menyelesaikan semua pelajaran kursus kelasnya muncul otomatis. Klik **Terbitkan** untuk menerbitkan sekali klik.
2. Untuk penghargaan lain (mis. juara lomba), klik **Terbitkan manual**: pilih kelas dan siswa, isi judul, keterangan (mis. "sebagai Juara 1 Lomba Game Scratch"), dan tanggal terbit.
3. Buka sertifikat untuk **Cetak / simpan PDF**. Sertifikat bisa **dicabut** bila salah terbit; halaman verifikasinya akan menampilkan status dicabut.

Setiap sertifikat punya nomor, QR, dan kode verifikasi yang bisa diperiksa siapa pun di `/sertifikat/{kode}`:

![Halaman verifikasi sertifikat](img/sertifikat-verifikasi.png)

## Honor saya

![Honor saya](img/guru-honor.png)

Menu **Honor Saya** menampilkan tarif per sesi, sesi yang **belum direkap**, slip yang **belum dibayar**, dan total yang **sudah dibayar**. Honor tercatat otomatis setiap Anda menyelesaikan laporan sesi. Admin merekap honor menjadi slip setiap akhir bulan; buka slip untuk melihat rincian dan mencetaknya.
