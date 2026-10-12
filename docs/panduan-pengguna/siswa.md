# Panduan Siswa

Halo! Di aplikasi ini kamu bisa melihat jadwal kelas, masuk Zoom, belajar lewat peta kursus, mengerjakan kuis dan soal coding, mengumpulkan tugas, membuat proyek, bereksperimen di Laboratorium, memamerkan karya di Komunitas, bertanya ke guru, dan mengumpulkan XP serta lencana.

Akunmu dibuat oleh admin. Masuk dengan **username** dan kata sandi yang diberikan. Kalau lupa kata sandi, minta tolong orang tua menghubungi admin. Panduan umum (login, notifikasi, kalender) ada di [sini](README.md#hal-yang-sama-untuk-semua-peran).

Tampilan aplikasi memakai bahasa Inggris. Di panduan ini, nama menu dan tombol ditulis **tebal** persis seperti yang kamu lihat di layar.

## Menu siswa

| Menu | Isinya | Alamat |
|---|---|---|
| **Home** | Ringkasan belajarmu hari ini | `/dashboard` |
| **Classes** | Kelas yang kamu ikuti | `/classes` |
| **Schedule** | Jadwal kelas minggu ini | `/schedule` |
| **Course** | Peta belajar kursusmu dan katalog kursus lain | `/courses`, `/courses/explore` |
| **Community** | Proyek yang dibagikan siswa lain | `/community` |
| **Laboratory** | Tempat bebas mencoba kode | `/lab` |
| **My projects** | Papan proyek dari guru | `/projects` |
| **Q&A** | Tanya jawab dengan guru dan teman | `/questions` |
| **My achievements** | XP, level, dan lencana | `/achievements` |
| **My certificates** | Sertifikat yang sudah kamu dapat | `/my-certificates` |
| **My monthly reports** | Laporan belajar per bulan | `/monthly-reports` |

## Beranda

![Beranda siswa: sapaan, level dan XP, lencana, kursus yang sedang dipelajari, proyek, jadwal sesi berikutnya, dan kuota belajar](img/siswa-beranda.png)

Di **Home** kamu melihat:

- Sapaan, jumlah kelas, sesi yang sudah kamu hadiri, dan persentase kehadiran.
- **Level** dan XP-mu, serta **Badges** (lencana) yang sudah kamu dapat.
- **Keep learning**: setiap kursus beserta persentase selesai, rata-rata kuis, dan pelajaran yang perlu kamu kerjakan berikutnya. Klik **All courses** untuk membuka halaman **Course**.
- **My projects**: proyek yang sedang kamu kerjakan. Klik **All projects** untuk melihat semuanya.
- **Next sessions** dengan tombol **Join Zoom**. Klik saat kelas akan dimulai. **My schedule** membuka jadwal lengkap.
- **Last session**: status hadirmu, materi, dan PR (**Homework**) dari guru.
- **Learning quota**: sisa sesi di paketmu.
- **My classes**: kartu setiap kelas yang kamu ikuti.

## Course: peta belajar

![Halaman Course: panel kelas di kiri dan peta belajar berkelok dari START sampai Finish di kanan](img/siswa-materi.png)

Buka **Course**. Halaman ini berisi peta belajar kursusmu.

- Kalau kamu ikut lebih dari satu kursus, pilih kursusnya di tombol-tombol paling atas (lengkap dengan persentase selesai).
- Panel **Your class** menampilkan nama kelas, kursus, dan berapa pelajaran sudah selesai. Tombol oranye (**Start: ...** atau **Continue: ...**) langsung membawamu ke pelajaran berikutnya.
- Kartu di bawahnya berisi **Teacher**, jadwal kelas, **Next class**, tombol **Join on Zoom**, **Quiz average**, dan **Assignments to hand in** (tugas yang belum dikumpulkan).
- Di kanan ada peta yang berkelok dari **START** di atas sampai kastel **Finish** di bawah. Setiap lingkaran adalah satu pelajaran, diberi nomor dan ikon jenisnya (materi, kuis, tugas, coding, misi).
  - Lingkaran dengan centang hijau sudah selesai, dan jalurnya ikut berwarna.
  - Lingkaran yang menyala dengan label **Up next** (atau **Start here** untuk pelajaran pertama) adalah yang harus kamu kerjakan sekarang.
  - Lingkaran putih belum dikerjakan.
  - Di sebelah lingkaran tertulis judul pelajaran, jenisnya, dan nilai terbaikmu (untuk kuis dan coding).
- Klik lingkaran mana pun untuk membuka pelajarannya. Kalau semua pelajaran selesai, kastel berubah kuning dan tertulis **Course complete!**

Klik **See the lesson list and scores** untuk melihat daftar pelajaran dalam bentuk tabel. Kartu **Explore other courses** di bawahnya membuka katalog kursus.

### Daftar pelajaran dan nilai

![Halaman kursus: progres, daftar isi kursus, dan nilai terbaik setiap pelajaran](img/siswa-kursus.png)

Halaman ini menampilkan **Your progress** (persentase dan rata-rata kuis), lalu **Course content**: semua pelajaran berurutan dengan jenis, durasi, dan **Best score**. Kartu **Continue learning** di kanan membawamu ke pelajaran berikutnya. Klik **My course** di kiri atas untuk kembali ke peta.

### Jelajahi kursus lain

![Katalog kursus: panel kursusmu di kiri dan semua kursus yang tersedia di kanan](img/siswa-katalog.png)

Ingin tahu kursus apa lagi yang bisa kamu pelajari? Klik **Explore other courses** di halaman Course (alamatnya `/courses/explore`).

- Panel **Your course** di kiri menampilkan kursusmu sekarang dan progresnya. **Continue course** membawamu kembali ke peta belajar.
- Di kanan ada semua kursus yang sudah terbit. Pilih **All** atau satu mata pelajaran untuk menyaring. Kursus yang sudah kamu ikuti diberi label **Your course** lengkap dengan persentase selesai.
- Klik kursus lain untuk melihat isinya: deskripsi, jumlah pelajaran, dan daftar **What you will learn**. Pelajarannya masih terkunci (ikon gembok) sampai kamu masuk kelas yang memakai kursus itu.

![Pratinjau kursus lain dengan daftar pelajaran yang masih terkunci dan tombol I'm interested](img/siswa-pratinjau-kursus.png)

Tertarik? Klik **I'm interested**. Guru, admin, dan orang tuamu akan diberi tahu, lalu mereka bisa membantumu masuk kelasnya. Setelah itu kartunya berubah menjadi **We got your message**, dan di katalog kursus itu diberi label **Interested**.

## Belajar materi, kuis, dan tugas

Saat kamu membuka pelajaran, aplikasi masuk ke **mode fokus**: menu samping dan header disembunyikan supaya kamu bisa berkonsentrasi.

- Bar gelap di atas berisi tombol panah kiri (**Back to the course map**) untuk kembali ke peta belajar, nama kursus, dan posisi pelajaran (mis. **Lesson 4 of 10**).
- Di tengah bar ada satu lingkaran untuk setiap pelajaran. Lingkaran hijau bercentang sudah selesai, lingkaran putih adalah pelajaran yang sedang dibuka. Klik lingkaran mana pun untuk pindah pelajaran.
- Tombol panah empat arah di kanan bar membuat halaman memenuhi layar (**Fullscreen**). Tekan lagi atau tombol Esc untuk keluar.
- Gunakan **Previous** dan **Next** di bagian bawah untuk pindah ke pelajaran sebelum atau sesudahnya.

### Materi

Baca materinya dan tonton videonya (bila ada). Setelah selesai, klik **Mark as done**. Tulisan berubah menjadi **Done** dan progresmu tersimpan.

### Kuis

![Hasil kuis: nilai, status lulus, tombol mengulang, dan pembahasan setiap soal](img/siswa-kuis.png)

Ada empat jenis soal. Petunjuk kecil di bawah setiap soal memberi tahu cara menjawabnya:

| Jenis soal | Petunjuk di layar | Cara menjawab |
|---|---|---|
| Pilihan ganda | **Choose one answer** | Pilih satu jawaban. |
| Banyak jawaban | **Choose all correct answers** | Centang semua jawaban yang benar. |
| Benar/salah | **Choose true or false** | Klik kotak besar **True** (centang hijau) atau **False** (silang merah). |
| Isian singkat | **Type your answer** | Ketik jawabanmu di kotak **Your answer**. Huruf besar/kecil, spasi berlebih, dan titik di akhir tidak berpengaruh. |

1. Jawab pertanyaannya. Di bagian bawah terlihat **Answered** (berapa soal sudah dijawab) dan sisa kesempatan.
2. Klik **Submit answers**. Nilaimu langsung keluar.
3. Kalau belum mencapai nilai lulus, klik **Try again** selama kesempatannya masih ada (**Attempts left**). Sudah lulus pun boleh mencoba lagi dengan **Take it again**.
4. Untuk isian singkat, hasil menampilkan jawabanmu dan jawaban yang diterima (**Accepted**).
5. Kunci jawaban dan pembahasan (**Explanation**) muncul setelah kamu lulus.

**Kuis berwaktu.** Beberapa kuis punya batas waktu. Kamu akan melihat tulisan *This quiz has a ...-minute timer* dan tombol **Start quiz**. Waktu mulai berjalan saat kamu menekan tombol itu, dan **tetap berjalan walaupun kamu meninggalkan halaman**. Jam hitung mundur terlihat di bagian bawah dan berubah kuning lalu merah saat waktunya hampir habis. Kalau waktu habis, jawabanmu terkirim otomatis. Jawaban yang terkirim setelah batas waktu tidak dihitung lulus.

**Warm-up Mission** adalah kuis di awal kursus, bukan ujian. Jawab sebisamu; hasilnya membantu guru tahu harus mulai dari mana. **Final Mission** di akhir kursus menunjukkan seberapa jauh kemajuanmu dibanding misi pemanasan.

### Tugas

1. Isi **Answer / notes**: tulis jawaban atau ceritakan apa yang kamu buat (boleh tempel kode).
2. Tempel **Project link** (mis. dari Scratch, Replit, atau Google Drive) dan/atau isi **Upload a file** (gambar, PDF, ZIP, .sb3, .py, .html; maks. 10 MB).
3. Klik **Submit**. Statusnya menjadi **Awaiting grade**. Selama belum dinilai, kamu masih bisa mengubahnya dengan **Update submission**.
4. Kalau statusnya **Needs revision**, baca catatan guru, perbaiki, lalu kirim lagi lewat form **Resubmit**.
5. Setelah dinilai, statusnya **Graded** dan nilai serta komentar guru muncul.

### Melaporkan masalah

Kalau soal, tes, atau materi sepertinya salah, klik **Report a problem** di bawah pelajaran (ada juga di soal coding).

1. Pilih alasannya di **What is wrong?**: jawaban atau tes sepertinya salah, instruksinya tidak jelas, ada yang rusak atau salah ketik, atau alasan lain.
2. Ceritakan lebih lanjut di **Tell us more (optional)**, mis. "Tes 2 mengharapkan 10, padahal 3 + 4 = 7".
3. Klik **Send report**. Guru mendapat notifikasi, dan di pelajaran itu tertulis *You reported a problem. Your teacher will check it.*

Setelah guru memeriksa, kamu mendapat notifikasi: **Fixed** kalau pelajarannya sudah diperbaiki, atau **Checked** kalau ternyata pelajarannya sudah benar.

## Soal coding

![Soal coding: cerita soal, tes contoh, petunjuk, editor kode, dan hasil penilaian](img/siswa-coding.png)

Kode kamu dijalankan langsung di browser, jadi tidak perlu memasang apa pun.

1. Baca cerita soal dan **Sample tests**. Ikon gembok memberi tahu berapa tes tersembunyi yang ikut dinilai saat kamu mengumpulkan.
2. Tulis kode di editor. Tekan **Run sample tests** (atau Ctrl+Enter) untuk mencoba. Buka **Try your own input** bila ingin menguji input lain (satu nilai per baris).
3. Kalau sudah yakin, klik **Submit**. Server menilai kodemu dengan tes contoh dan tes tersembunyi. Hasilnya muncul di bagian atas, mis. **All tests passed!** atau **So close! Try again**.
4. Bingung? Klik **Show hint 1** di kotak **Hints**. Petunjuk terbuka satu per satu. Setelah beberapa kali mencoba, kotak **See solution** bisa muncul.
5. **Your history** menyimpan kiriman sebelumnya. Tombol panah melingkar di samping **Submit** mengembalikan kode awal.
6. Tombol panah empat arah di sebelahnya (**Fullscreen**) membuat soal dan editor memenuhi layar, dengan editor yang lebih tinggi. Klik **Exit fullscreen** atau tekan Esc untuk kembali.

Saat pertama kali membuka soal Python, tunggu sebentar: browser sedang menyiapkan Python (hanya lama di awal).

**Aturan kode.** Beberapa soal punya kotak **Code rules**, misalnya kode harus memakai `for`, tidak boleh memakai `sum`, atau paling banyak 6 baris. Aturan ini dicek saat kamu menekan **Submit** (*Checked when you submit*). Komentar dan isi teks di dalam tanda kutip tidak dihitung, jadi menulis kata kuncinya di komentar tidak akan lolos.

Jenis soal lain:

- **HTML/CSS**: hasil halamanmu tampil di **Preview**. Klik **Check my page** untuk mencoba. Kotak **What gets checked** berisi hal-hal yang dinilai.

  ![Soal HTML/CSS: daftar yang dicek, editor, dan pratinjau halaman](img/siswa-html.png)

- **Susun baris**: seret baris kode (atau pakai tombol panah atas/bawah) sampai urutannya benar, lalu klik **Submit**. Baris merah berarti belum di posisi yang benar.

![Soal susun baris: baris kode yang bisa digeser naik dan turun](img/siswa-parsons.png)

## Laboratory

![Laboratorium: daftar proyek kode milikmu dengan filter jenis](img/siswa-lab.png)

**Laboratory** (`/lab`) adalah tempat bebas untuk mencoba ide. Tidak ada nilai di sini.

1. Klik **New project**. Di jendela **What do you want to make?**, pilih jenisnya: **Python**, **JavaScript**, atau **Website** (HTML, CSS, dan JS).
2. Isi **Project name**, lalu klik **Create and open**.
3. Tulis kode di editor. Klik **Run** (atau Ctrl+Enter) untuk menjalankannya. Hasilnya muncul di **Output**; untuk website, halamanmu tampil di **Preview**. Kalau programmu meminta input, klik **Input** dan tulis satu nilai per baris.
4. Isi **What does it do?** supaya orang lain tahu proyekmu tentang apa.
5. Klik **Save** (atau Ctrl+S). Tulisan **Unsaved changes** berarti ada perubahan yang belum tersimpan.
6. Klik **Fullscreen** di atas editor supaya editor dan panel hasil memenuhi layar. Klik **Exit fullscreen** atau tekan Esc untuk kembali.

![Editor Laboratorium: nama proyek, editor kode, dan panel hasil](img/siswa-lab-editor.png)

Di daftar proyek, gunakan tombol **All**, **Python**, **JavaScript**, dan **Website** untuk menyaring. Klik **All projects** di editor untuk kembali ke daftar. Ikon tempat sampah menghapus proyek.

**Membagikan ke Komunitas.** Klik **Save & share** di editor. Proyekmu muncul di Komunitas dan kartunya diberi label **In Community**. Mau menariknya lagi? Klik **Make private**. **View in Community** membuka halaman proyekmu di Komunitas.

## Community

![Komunitas: proyek yang sedang tren minggu ini dan semua proyek yang dibagikan](img/siswa-komunitas.png)

**Community** (`/community`) berisi proyek yang dibagikan siswa dari semua kelas.

- **Trending this week**: proyek yang paling banyak disukai dalam 7 hari terakhir.
- Urutkan dengan **New**, **Popular**, atau **Most discussed**. Saring dengan **All**, **Python**, **JavaScript**, **Website**, atau cari lewat **Search projects**.
- **Share a project** membawamu ke Laboratory untuk memilih proyek yang mau dibagikan.

Klik kartu untuk membuka proyek. Di sana kamu bisa:

- Menjalankan proyeknya dengan **Run**, dan membuka **Code** untuk melihat kodenya.
- Menekan ikon hati untuk menyukai proyek (tekan lagi untuk membatalkan).
- Menulis komentar di **Comments**, lalu klik **Post comment**. Tulis yang baik, atau tanyakan cara kerjanya. Kamu bisa menghapus komentarmu sendiri, dan pemilik proyek bisa menghapus komentar di proyeknya.
- Kalau itu proyekmu, klik **Edit in Laboratory** untuk mengubahnya.

Guru bisa menurunkan proyek dari Komunitas. Kalau itu terjadi, kartunya berlabel **Removed by teacher**; kamu tetap bisa mengerjakannya di Laboratory, tetapi tidak bisa membagikannya lagi.

## My projects

![Papan proyek: empat kolom To do, Doing, Ready for review, dan Done, serta form hasil proyek](img/siswa-proyek-papan.png)

Guru membuat proyek, dan kamu mendapat papan sendiri dengan kolom: **To do → Doing → Ready for review → Done**. Kolom **Ready for review** hanya ada kalau langkah proyek perlu dicek guru.

1. Buka **My projects**, pilih proyek. Baca **Teacher's project instructions** bila ada.
2. Seret kartu ke **Doing** saat kamu mulai mengerjakannya. Di HP, pakai tombol panah kiri/kanan pada kartu.
3. Kalau langkahnya sudah selesai, geser ke **Ready for review**. Guru akan menyetujuinya (kartu pindah ke **Done**) atau mengembalikannya dengan catatan (**Teacher's note**).
4. Kamu boleh menambah langkah sendiri dengan **Add card**. Kartu bertopi wisuda adalah langkah dari guru.
5. Di bagian **Project result**, isi **Project link** (Scratch, Replit, GitHub Pages) dan **A short story**, lalu klik **Save**.

Kalau semua langkah sudah di **Done**, muncul ucapan **Project complete!** Kalau proyek sudah diarsipkan guru, papannya tidak bisa diubah lagi. Klik **All projects** untuk kembali ke daftar.

![Papan proyek di HP: kartu dengan tombol panah untuk pindah kolom](img/siswa-proyek-hp.png)

## Q&A

![Daftar pertanyaan di Q&A dengan filter dan jumlah jawaban](img/tanya-daftar.png)

Tidak ada pertanyaan yang bodoh! Teman sekelasmu mungkin juga bingung hal yang sama.

Di daftar pertanyaan, pakai tab **All**, **Unanswered**, **Has answers**, **Resolved**, dan **My questions**, pilih kelas di **All classes**, atau cari lewat **Search questions...**.

1. Buka **Q&A → Ask a question**, atau klik **Ask** di kartu **Stuck? Ask your teacher** di halaman pelajaran.
2. Pilih **Class** dan (opsional) pelajarannya di **About a lesson**. Pilih **General question** kalau tidak tentang pelajaran tertentu.
3. Tulis **Question title** yang singkat, ceritakan apa yang sudah kamu coba di **Tell us more**, klik **Add code** untuk menempel kode, dan lampirkan tangkapan layar di **Image (optional)** bila perlu (JPG/PNG, maks. 4 MB).
4. Atur **Who can see it?**: **Whole class** (teman sekelas bisa membaca dan ikut menjawab) atau **Teacher only**. Centang **Hide my name from classmates** kalau malu; teman akan melihat "A classmate", tetapi guru dan orang tuamu tetap tahu.
5. Klik **Send question**. Kamu mendapat notifikasi saat ada jawaban.

Kartu **Stuck? Ask your teacher** di setiap halaman pelajaran juga menampilkan pertanyaan teman tentang pelajaran itu. Klik **View** untuk melihat semuanya:

![Kartu tanya di halaman pelajaran beserta pertanyaan teman](img/siswa-kartu-tanya.png)

![Form bertanya: kelas, judul, cerita, kode, gambar, dan siapa yang bisa melihat](img/siswa-tanya-form.png)

Setelah paham, klik **Pick as best answer** pada jawaban yang paling membantu; pertanyaanmu ditandai **Resolved**. Masih bingung? Klik **Reopen**. Kamu juga bisa menekan **Me too** pada pertanyaan teman dan **Helpful** pada jawaban yang bagus, serta ikut menjawab pertanyaan teman di kotak **Help answer** lalu klik **Send answer**.

![Detail pertanyaan: jawaban guru yang dipilih sebagai jawaban terbaik](img/tanya-detail.png)

## My achievements

![Halaman My achievements: level, XP, lencana, bintang kelas, dan cara mendapat XP](img/siswa-prestasi.png)

Kamu mendapat **XP** otomatis dari kegiatan belajar:

| Kegiatan (seperti di layar) | XP |
|---|---|
| **Finished a lesson** | 10 |
| **Passed a quiz** | 20 |
| **Scored 100 on a quiz** | +10 |
| **Assignment graded** | 25 |
| **Passed a coding exercise** | 25 |
| **Attended class** | 15 |
| **Asked in Q&A** | 5 |
| **Answer picked as best** | 20 |
| **Finished a project step** | 10 |
| **Finished a project** | 50 |
| **Earned a certificate** | 100 |

XP menaikkan **level**: **Code Rookie**, **Explorer**, **Tinkerer**, **Inventor**, **Junior Engineer**, **Creator**, **Code Master**, sampai **Legend**. Ada juga **13 lencana**, misalnya **First Steps** (1 pelajaran), **Little Coder** (1 soal coding), **Always There** (hadir 8 sesi), **Brave Asker** (1 pertanyaan), **Builder** (1 proyek), dan **Graduate** (1 sertifikat). Lencana yang belum didapat tampil pudar beserta progresnya.

Halaman **My achievements** juga menampilkan **Class stars this month** (5 siswa dengan XP terbanyak bulan ini di setiap kelasmu), **Latest XP**, dan **How to earn XP**.

## My certificates

![Halaman My certificates berisi kartu sertifikat](img/siswa-sertifikat.png)

Setelah kamu menyelesaikan semua pelajaran di kursus, guru akan menerbitkan sertifikat. Buka **My certificates**, pilih sertifikat, lalu klik **Print / save as PDF**. Sertifikat punya kode QR yang bisa dipindai untuk membuktikan keasliannya; tombol **Copy verification link** menyalin tautan pemeriksaannya.

## My monthly reports

**My monthly reports** (`/monthly-reports`) berisi laporan belajarmu setiap bulan, sama seperti yang dibaca orang tuamu.

1. Pilih bulan di kotak di kanan atas, lalu klik **Open report**.
2. Laporan berisi **Attendance**, **Lessons completed**, **Quiz average**, **XP this month**, catatan guru (**Going well** dan **Focus for next month**), daftar sesi bulan itu beserta PR, nilai kuis dan tugas, latihan coding dan proyek, lencana bulan itu, dan progres kursus, serta sisa sesi paket. Jumlah tagihan hanya tampil untuk orang tua dan admin.
3. Pakai tombol panah di samping nama bulan untuk pindah bulan, dan **Print / save PDF** untuk mencetak.

## Jadwal dan notifikasi

- **Schedule** menampilkan kelasmu minggu ini. Kamu bisa memasangnya ke kalender HP lewat **Create calendar link** (lihat [panduan umum](README.md#jadwal-di-kalender-hp)).
- **Classes** menampilkan kelas yang kamu ikuti beserta jadwal dan gurunya.
- **Lonceng** memberi tahu PR baru dari sesi, tugas yang sudah dinilai atau perlu revisi, jawaban atas pertanyaanmu, dan hasil pemeriksaan laporan masalah yang kamu kirim.
