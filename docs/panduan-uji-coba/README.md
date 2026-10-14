# Panduan Uji Coba Aplikasi LMS Alprogmatics

Panduan ini mengajak Anda mencoba aplikasi dari awal sampai akhir, persis seperti dipakai sehari-hari: calon siswa mendaftar trial, admin memproses sampai anak menjadi siswa, tagihan dibayar, guru mengajar dan menilai, siswa belajar dan berkarya, lalu orang tua memantau. Setiap langkah dilengkapi gambar layar dan hasil yang seharusnya muncul.

Semua gambar diambil otomatis dari aplikasi dengan **data demo** yang sama dengan yang Anda pakai, dengan menjalankan langkah-langkah di panduan ini satu per satu. Jadi bila Anda mengikuti urutannya, layar Anda akan terlihat sama.

> **Waktu yang dibutuhkan:** sekitar 60–90 menit untuk semua skenario. Setiap skenario juga bisa dicoba terpisah.

## Persiapan

### Alamat dan akun demo

Buka alamat aplikasi uji coba yang diberikan tim Alprogmatics (misalnya `https://demo.alprogmatics.id`). Semua akun demo memakai kata sandi **`password`**.

| Peran | Username | Keterangan |
|---|---|---|
| Admin | `admin` | Mengelola semua data, keuangan, lead, dan laporan |
| Guru | `budi` | Mengajar kelas **Python Start 1** dan **Python Pro 1** |
| Guru | `sari` | Mengajar kelas **Scratch Game Maker** |
| Guru | `rizky` | Mengajar kelas **Web Development Junior** |
| Siswa | `aldi` | Ikut kelas Python Start 1 dan Scratch Game Maker (dipakai di skenario siswa) |
| Siswa | `nadia`, `raka`, `kirana`, `farel`, `alya`, … | Siswa lain di data demo |
| Orang tua | `ortu1` | Orang tua **Aldi** dan **Nadia** |
| Orang tua | `ortu5` | Orang tua **Bima** dan **Citra** (dipakai di skenario pembayaran) |

### Tips sebelum mulai

- **Pakai beberapa jendela.** Untuk mencoba dua peran sekaligus (misalnya orang tua mengirim bukti transfer, lalu admin memverifikasi), buka satu jendela biasa dan satu jendela penyamaran (*incognito*), atau dua browser berbeda.
- **Bahasa tampilan** aplikasi adalah bahasa Inggris. Di panduan ini nama tombol dan menu ditulis **tebal** persis seperti di layar.
- **Keluar akun:** klik nama di kanan atas, lalu **Log out**.
- **Data demo bisa diulang.** Langkah di panduan ini mengubah data (menambah siswa, membayar tagihan, dan lain-lain). Bila ingin mengulang dari awal, minta tim menjalankan ulang data demo.
- Beberapa bagian butuh **internet**: video YouTube di materi dan gambar pustaka sprite Scratch.

### Urutan skenario

| No. | Skenario | Masuk sebagai |
|---|---|---|
| 1 | Calon siswa mendaftar free trial | Tamu (tanpa login) |
| 2 | Admin memproses lead sampai menjadi siswa | `admin` |
| 3 | Admin menyiapkan data: siswa, orang tua, guru, kelas, program | `admin` |
| 4 | Tagihan dan pembayaran | `admin`, `ortu5` |
| 5 | Guru menyiapkan materi dan mengajar | `budi`, `sari` |
| 6 | Siswa belajar di peta Course | `aldi` |
| 7 | Siswa berkarya: Laboratory dan Community | `aldi` |
| 8 | Bertanya, proyek, prestasi, dan sertifikat | `aldi`, `budi` |
| 9 | Orang tua memantau anak | `ortu1` |
| 10 | Admin memantau: notifikasi, audit, verifikasi sertifikat | `admin`, tamu |
| 11 | Tampilan di HP | `aldi` |

## Skenario 1 – Calon siswa mendaftar free trial

Orang tua calon siswa mengisi formulir trial di website, tanpa perlu akun.

1. Buka alamat aplikasi, lalu tambahkan `/free-trial` di belakangnya (atau klik **Sign up for a free trial class** di halaman depan).
2. Isi **Parent's name** (`Rina Wijaya`), **WhatsApp number**, **Child's name** (`Kayla`), **Age** (`9`), pilih **Program of interest** (`Scratch Junior & Scratch`), dan **Preferred time**.
3. Klik **Send sign-up**.

![Formulir free trial](img/02-free-trial-form.jpg)

**Hasil yang diharapkan:** muncul ucapan **Thank you, Rina Wijaya!** dan admin langsung mendapat notifikasi lead baru.

![Formulir terkirim](img/03-free-trial-terkirim.jpg)

## Skenario 2 – Admin memproses lead sampai menjadi siswa

### Masuk sebagai admin

1. Buka halaman login, isi username `admin` dan kata sandi `password`, lalu klik **Log in**.

![Halaman login](img/01-login.jpg)

2. Beranda admin menampilkan ringkasan: siswa aktif, guru, kelas berjalan, tagihan belum dibayar, kelas hari ini, bukti transfer baru, dan siswa terbaru.

![Beranda admin](img/04-admin-beranda.jpg)

### Papan lead

3. Buka menu **Leads**. Setiap calon siswa tampil sebagai kartu di kolom tahapannya: **New**, **Contacted**, **Trial scheduled**, **Trial done**. Kartu **Kayla** yang baru mendaftar ada di kolom **New**.

![Papan lead](img/05-admin-leads.jpg)

4. Klik kartu **Kayla**. Halaman lead menampilkan tahapan, data orang tua dan anak, tombol **Chat WhatsApp**, serta riwayat.

![Detail lead baru](img/06-admin-lead-baru.jpg)

### Menghubungi dan menjadwalkan trial

5. Klik **I have contacted them** setelah menyapa orang tua lewat WhatsApp.
6. Klik **Schedule trial**, isi **Time** (tanggal dan jam trial), pilih **Teacher** (`Sari Wulandari`), dan isi **Zoom link**. Klik **Save schedule**.

![Menjadwalkan kelas trial](img/07-admin-jadwal-trial.jpg)

**Hasil yang diharapkan:** muncul pesan **Trial scheduled and the teacher has been notified.** Guru Sari mendapat notifikasi.

### Mencatat hasil trial

7. Setelah kelas trial, klik **Record trial result**. Tulis hasil trial di **How did the trial go?**, pilih semangat anak (**1–5**), dan **Recommended class** (`Scratch Game Maker`). Klik **Save result**.

![Hasil kelas trial](img/08-admin-hasil-trial.jpg)

### Mendaftarkan sebagai siswa

8. Klik **Enrol as student**. Username anak dan orang tua sudah terisi otomatis. Isi **Initial password** (minimal 8 karakter, misalnya `kayla12345`) dan pilih **Enrol in class** (`Scratch Game Maker`). Klik **Create accounts**.

![Membuat akun siswa dan orang tua](img/09-admin-buat-akun.jpg)

**Hasil yang diharapkan:** tahapan berubah menjadi **Enrolled**, muncul kotak hijau **Kayla is now a student**, dan tombol **Sell session package** untuk menjual paket sesi. Kirim username dan kata sandi ke orang tua.

![Lead menjadi siswa](img/10-admin-lead-jadi-siswa.jpg)

## Skenario 3 – Admin menyiapkan data: siswa, orang tua, guru, kelas, program

Selain lewat lead, admin bisa menambah data langsung.

### Siswa, orang tua, dan guru

1. Buka **Students**. Daftar siswa bisa dicari dan disaring; **Kayla** dari skenario 2 sudah ada di sini.

![Daftar siswa](img/11-admin-siswa.jpg)

2. Klik **Add student** untuk melihat formulirnya: akun login (nama, username, kata sandi) dan data siswa (tanggal lahir, sekolah, kelas, orang tua).

![Formulir siswa](img/12-admin-form-siswa.jpg)

3. Formulir **Add parent** menghubungkan orang tua dengan anaknya lewat daftar centang **Children**.

![Formulir orang tua](img/13-admin-form-orang-tua.jpg)

4. Formulir **Add teacher** berisi keahlian dan **honor per sesi** yang dipakai untuk menghitung gaji guru.

![Formulir guru](img/14-admin-form-guru.jpg)

### Kelas

5. Buka **Classes**. Setiap kelas menampilkan guru, jadwal, jumlah siswa, dan status.

![Daftar kelas](img/15-admin-kelas.jpg)

6. Klik kelas **Scratch Game Maker**. Halaman kelas berisi jadwal sesi, **Join Zoom**, data Zoom, daftar siswa (**Add students**), dan tombol **Lesson access**.

![Detail kelas](img/16-admin-detail-kelas.jpg)

7. Untuk membuat kelas baru, klik **Create class** dan isi nama, mata pelajaran, guru, hari dan jam, serta link Zoom. Sesi kelas dibuat otomatis dari jadwal mingguan.

![Formulir kelas baru](img/17-admin-form-kelas.jpg)

### Program dan level

8. Buka **Programs**. Kursus disusun seperti di prototipe: setiap **program** (Python, Scratch, Data, …) berisi **level** berurutan (Python Start 1, Python Start 2, …). Siswa naik level satu per satu.

![Program dan level](img/18-admin-program.jpg)

## Skenario 4 – Tagihan dan pembayaran

### Paket sesi dan tagihan (admin)

1. Buka **Session packages** (grup menu **Finance**). Di sini admin membuat paket sesi (jumlah sesi, masa berlaku, harga) dan menjualnya ke siswa.

![Paket sesi](img/19-admin-paket.jpg)

2. Buka **Invoices**, klik **New invoice**. Pilih **One student** (`Bima`), isi **Invoice title** (`Scratch workshop (1 day)`), **Amount** (`150000`), dan **Due date** (bawaannya tanggal 10 berikutnya). Klik **Create invoice**.

![Formulir tagihan](img/20-admin-form-tagihan.jpg)

**Hasil yang diharapkan:** muncul pesan **1 invoice(s) created.** Tagihan bisa juga dibuat untuk satu kelas sekaligus (**A whole class**).

![Daftar tagihan](img/21-admin-daftar-tagihan.jpg)

### Orang tua membayar (ortu5)

3. Di jendela lain, masuk sebagai `ortu5`. Beranda orang tua menampilkan tagihan yang menunggu pembayaran, kuota sesi, jadwal, dan progres belajar anak.

![Beranda orang tua](img/22-ortu-beranda.jpg)

4. Buka **Child's invoices**, lalu klik tagihan **Scratch workshop (1 day)**.

![Daftar tagihan orang tua](img/23-ortu-tagihan.jpg)

5. Klik **Send transfer proof**. Isi **Sending bank**, **Account holder name**, dan unggah foto bukti transfer di **Transfer proof**. Klik **Send**.

![Mengirim bukti transfer](img/24-ortu-kirim-bukti.jpg)

**Hasil yang diharapkan:** muncul pesan **Transfer proof sent. An admin will verify your payment.** dan pembayaran berstatus **Awaiting verification**.

![Bukti transfer terkirim](img/25-ortu-bukti-terkirim.jpg)

### Admin memverifikasi

6. Kembali sebagai `admin`, buka **Finance overview**. Ringkasan keuangan menampilkan uang masuk bulan ini, tagihan belum lunas, yang terlambat, dan daftar **Needs verification**.

![Ringkasan keuangan](img/26-admin-keuangan.jpg)

7. Buka tagihan Bima. Di **Payment history** klik **Proof** untuk melihat bukti, lalu **Verify** (atau **Reject** dengan alasan).

![Memeriksa bukti transfer](img/27-admin-cek-bukti.jpg)

**Hasil yang diharapkan:** muncul pesan **Payment verified.** dan tagihan berstatus **Paid**.

![Tagihan lunas](img/28-admin-lunas.jpg)

8. Klik **Receipt** untuk kuitansi resmi (terbilang dalam bahasa Inggris). Tombol **Print / save as PDF** mencetaknya.

![Kuitansi](img/29-kuitansi.jpg)

### Honor guru

9. Buka **Tutor fees**. Honor dihitung otomatis dari sesi yang sudah selesai; klik **Create pay slip** untuk membuat slip gaji guru.

![Honor guru](img/30-admin-honor.jpg)

## Skenario 5 – Guru menyiapkan materi dan mengajar

### Beranda guru (budi)

1. Masuk sebagai `budi`. Beranda guru menampilkan pertanyaan siswa yang belum dijawab, langkah proyek yang menunggu dicek, kelas hari ini, dan honor.

![Beranda guru](img/31-guru-beranda.jpg)

### Course dan isinya

2. Buka **Course**. Guru melihat semua course (level) yang bisa diajarkan.

![Daftar course](img/32-guru-course.jpg)

3. Klik **Python Start 1**. Bagian **Course content** berisi task berurutan (Theory, Quiz, Assignment, Coding), dan bagian **Lessons on the map** mengelompokkan task menjadi lingkaran Lesson di peta siswa.

![Isi course](img/33-guru-isi-course.jpg)

4. Klik **Theory** untuk menambah materi. Isi **Title** (`Theory: Loops recap`) dan **Lesson content** (Markdown, boleh berisi kode). Klik **Save**.

![Formulir materi](img/34-guru-form-theory.jpg)

**Hasil yang diharapkan:** muncul pesan **Theory added.** dan materi tampil seperti yang akan dilihat siswa.

![Materi tersimpan](img/35-guru-theory-tersimpan.jpg)

### Membuka lesson untuk kelas

5. Buka **Classes**, pilih **Python Start 1**, lalu **Lesson access**. Lesson terbuka satu per satu (**Step by step**). Guru bisa membuka lesson lebih awal untuk satu kelas, atau **Open all**.

![Akses lesson per kelas](img/36-guru-akses-lesson.jpg)

### Sesi dan absensi

6. Buka **Online Lesson**. Tab **Needs report** berisi sesi yang belum diisi absensinya.

![Daftar sesi](img/37-guru-sesi.jpg)

7. Buka satu sesi. Isi kehadiran setiap siswa (**Present**, **Excused**, **Sick**, **Absent**), **Topic covered**, **Homework**, **Note for parents**, dan **Zoom recording link**, lalu **Save & complete session**. Kehadiran otomatis memotong kuota paket siswa.

![Mengisi sesi](img/38-guru-isi-sesi.jpg)

8. **Monthly reports** berisi laporan bulanan setiap siswa; guru menulis catatan **Going well** dan **Focus for next month** untuk orang tua.

![Laporan bulanan guru](img/39-guru-laporan-bulanan.jpg)

### Menilai tugas (sari)

9. Masuk sebagai `sari`, buka **Grading**. Isi **Score** dan umpan balik, lalu **Save grade** (atau **Ask for revision**).

![Penilaian tugas](img/40-guru-penilaian.jpg)

**Hasil yang diharapkan:** muncul pesan **Grade saved.** dan siswa mendapat notifikasi.

### Tugas yang dikerjakan di Scratch

10. Buka **Course → Scratch Creator 1**, klik **Assignment**. Isi judul (`Project: My dancing cat`) dan petunjuk, lalu pada **Students work in** pilih **Scratch editor**. Klik **Save**.

![Tugas Scratch](img/41-guru-tugas-scratch.jpg)

11. Agar siswa bisa langsung mengerjakannya, buka **Classes → Scratch Game Maker → Lesson access** dan klik **Open all**.

![Membuka semua lesson](img/42-guru-buka-semua-lesson.jpg)

## Skenario 6 – Siswa belajar di peta Course

### Beranda siswa (aldi)

1. Masuk sebagai `aldi`. Menu siswa ada di bar atas: **Home**, **Course**, **Online Lesson**, **Community**, **Laboratory**, **Archive**, dan **More**. Beranda menampilkan sapaan, 6 statistik (proyek, like, view, komentar, lesson selesai, soal terjawab), level dan badge, kursus yang sedang dipelajari, proyek, dan **Popular in Community**.

![Beranda siswa](img/43-siswa-beranda.jpg)

### Peta Course

2. Klik **Course**. Peta tampil seperti dunia bergambar: **START**, lingkaran Lesson bernomor, dan kastel di akhir level. Lesson yang sudah selesai bercentang hijau; yang menyala adalah yang harus dikerjakan sekarang (**Up next**).

![Peta course](img/44-siswa-peta-course.jpg)

3. Gulir ke bawah: setelah kastel ada level berikutnya (Python Start 2, Python Pro 1, …) yang masih terkunci sampai level ini selesai.

![Peta course lengkap](img/45-siswa-peta-course-penuh.jpg)

4. Klik lingkaran Lesson untuk melihat task-nya, lalu klik task atau **Continue**.

![Daftar task dalam Lesson](img/46-siswa-popup-lesson.jpg)

### Mencari materi

5. Klik ikon kaca pembesar di kanan atas dan ketik kata kunci (misalnya `Theory`). Hasilnya dibagi menjadi **Courses** dan **Lessons**.

![Pencarian](img/47-siswa-cari.jpg)

### Mengerjakan task

6. **Theory:** materi tampil dalam **mode fokus**, dengan bar gelap di atas, tombol kembali ke peta, dan titik untuk setiap task. Klik **Mark as done** setelah membaca.

![Materi dalam mode fokus](img/48-siswa-theory.jpg)

7. **Quiz:** pilih jawaban lalu **Submit answers**. Nilai langsung keluar; jawaban benar terbuka setelah lulus.

![Kuis](img/49-siswa-kuis.jpg)

8. **Coding:** tulis kode di editor, klik **Run sample tests** untuk mencoba, lalu **Submit**. Kode dijalankan di browser dan dinilai otomatis.

![Soal coding](img/50-siswa-coding.jpg)

### Tugas Scratch

9. Buka tugas **Project: My dancing cat** (dari skenario 5). Editor Scratch lengkap ada langsung di halaman tugas.

![Tugas Scratch](img/51-siswa-tugas-scratch.jpg)

10. Klik **Fullscreen** agar editor memenuhi layar. Buat proyeknya, lalu klik **Submit** di bar atas editor.

![Editor Scratch layar penuh](img/51b-siswa-tugas-scratch-penuh.jpg)

**Hasil yang diharapkan:** muncul pesan **Assignment submitted. Your teacher will grade it.** Proyek Scratch terlampir otomatis dan bisa diputar lewat **Play Scratch project**.

![Tugas terkirim](img/52-siswa-tugas-terkirim.jpg)

## Skenario 7 – Siswa berkarya: Laboratory dan Community

### Laboratory

1. Buka **Laboratory**. Ini tempat bebas berkarya, tanpa nilai.

![Laboratory](img/53-siswa-lab.jpg)

2. Klik **New project**. Pilih jenisnya: **Python**, **Scratch**, **Presentation**, **Website**, atau **JavaScript**. Isi **Project name**, lalu **Create and open**.

![Memilih jenis proyek](img/54-siswa-lab-pilih-jenis.jpg)

3. **Python:** tulis kode dan klik **Run**; hasilnya muncul di **Output**. Klik **Save & share** untuk membagikannya ke Community.

![Proyek Python](img/55-siswa-lab-python.jpg)

4. **Scratch:** editor Scratch resmi terbuka; klik **Save** untuk menyimpan proyek ke akun.

![Proyek Scratch](img/56-siswa-lab-scratch.jpg)

5. **Presentation:** tulis slide di kiri (`---` memisahkan slide, `#` untuk judul). Slideshow di kanan diputar dengan **Back** / **Next**.

![Presentasi](img/57-siswa-lab-presentasi.jpg)

### Community

6. Buka **Community**. Bagian **Trending** menampilkan karya paling disukai **Today**, **Week**, atau **Month**. Di bawahnya semua karya bisa diurutkan dan disaring per jenis.

![Community](img/58-siswa-community.jpg)

7. Buka karya **Bouncing ball**. Klik bendera hijau untuk memainkan proyek Scratch, klik ikon hati untuk **like**, lalu tulis komentar dan klik **Post comment**.

![Like dan komentar](img/59-siswa-community-proyek.jpg)

**Hasil yang diharapkan:** muncul pesan **Comment posted.** Guru dan orang tua juga bisa memberi like dan komentar.

## Skenario 8 – Bertanya, proyek, prestasi, dan sertifikat

### Bertanya ke guru

1. Sebagai `aldi`, buka **More → Q&A**, klik **Ask a question**. Pilih kelas (**Python Start 1**), tulis judul dan penjelasan, lalu **Send question**.

![Bertanya](img/60-siswa-tanya.jpg)

2. Sebagai `budi`, buka **Q&A**, pilih pertanyaan tersebut, tulis jawaban, lalu **Send answer**.

![Guru menjawab](img/68-guru-jawab.jpg)

3. Kembali sebagai `aldi`, buka pertanyaannya dan klik **Pick as best answer**.

![Jawaban terbaik](img/70-siswa-jawaban-terbaik.jpg)

### Proyek bertahap (kanban)

4. Sebagai `aldi`, buka **More → My projects** dan pilih proyek. Geser kartu dari **To do** ke **Doing** (atau pakai tombol panah), lalu ke **Ready for review** bila selesai.

![Papan proyek](img/61-siswa-papan-proyek.jpg)

5. Sebagai `budi`, buka **Projects** dan pilih proyek kelasnya. Langkah yang menunggu dicek ada di **To review**: klik **Approve** atau **Send back** dengan catatan.

![Guru mengecek proyek](img/69-guru-cek-proyek.jpg)

### Online Lesson, Archive, prestasi, sertifikat

6. **Online Lesson** menampilkan kelas yang sedang berlangsung (**Live now**), kelas berikutnya, rekaman Zoom terakhir, dan jadwal minggu ini.

![Online Lesson](img/62-siswa-online-lesson.jpg)

7. **Archive** menyimpan Lesson yang sudah selesai untuk diulang kapan saja.

![Archive](img/63-siswa-archive.jpg)

8. **More → My achievements** menampilkan XP, level, 19 badge, bintang kelas bulan ini, dan cara mendapat XP.

![Prestasi](img/64-siswa-prestasi.jpg)

9. **More → My certificates** berisi sertifikat; buka satu untuk mencetaknya (**Print / save as PDF**) atau menyalin link verifikasi.

![Sertifikat](img/65-siswa-sertifikat.jpg)

### Katalog course

10. Dari peta Course klik **Explore other courses**. Katalog menampilkan semua program; klik program untuk melihat levelnya.

![Katalog course](img/66-siswa-katalog.jpg)

11. Buka course yang belum diikuti dan klik **I'm interested**. Admin dan orang tua mendapat pemberitahuan.

![Tertarik course lain](img/67-siswa-tertarik.jpg)

## Skenario 9 – Orang tua memantau anak

1. Masuk sebagai `ortu1`. Beranda menampilkan setiap anak (Aldi dan Nadia): kuota paket, sesi berikutnya dengan **Join Zoom**, catatan sesi terakhir, dan progres belajar.

![Beranda orang tua](img/71-ortu1-beranda.jpg)

2. Buka **Learning progress** untuk rapor lengkap: progres tiap course, rata-rata kuis dan tugas, kenaikan kemampuan per konsep (**Skill growth**), dan nilai setiap task.

![Rapor progres](img/72-ortu1-rapor.jpg)

3. Buka **Monthly reports** untuk laporan bulanan: kehadiran, lesson selesai, nilai, catatan guru, daftar sesi dan PR. Klik **Print / save PDF** untuk menyimpannya.

![Laporan bulanan](img/73-ortu1-laporan-bulanan.jpg)

## Skenario 10 – Admin memantau

1. Lonceng di kanan atas dan halaman **Notifications** berisi semua pemberitahuan: lead baru, bukti transfer, hasil trial, siswa yang tertarik course lain, dan lain-lain.

![Notifikasi](img/74-admin-notifikasi.jpg)

2. **Audit log** mencatat siapa mengubah apa dan kapan (login, tagihan, nilai, dan lain-lain); catatannya tidak bisa diubah atau dihapus.

![Audit log](img/75-admin-audit.jpg)

3. Siapa pun bisa memeriksa keaslian sertifikat lewat link verifikasinya, tanpa login.

![Verifikasi sertifikat](img/76-verifikasi-sertifikat.jpg)

## Skenario 11 – Tampilan di HP

Aplikasi bisa dipakai di HP lewat browser. Menu dibuka lewat tombol ☰ di kanan atas.

![Beranda siswa di HP](img/77-hp-beranda.jpg)

![Peta course di HP](img/78-hp-peta.jpg)

## Daftar periksa uji coba

Centang setiap butir yang sudah dicoba dan berjalan sesuai harapan.

| No. | Yang dicoba | Hasil sesuai? | Catatan |
|---|---|---|---|
| 1 | Mendaftar free trial sebagai tamu | ☐ | |
| 2 | Admin: hubungi, jadwalkan trial, catat hasil, daftarkan jadi siswa | ☐ | |
| 3 | Admin: tambah siswa, orang tua, guru, kelas | ☐ | |
| 4 | Admin: buat tagihan | ☐ | |
| 5 | Orang tua: kirim bukti transfer | ☐ | |
| 6 | Admin: verifikasi pembayaran dan cetak kuitansi | ☐ | |
| 7 | Guru: tambah materi, kuis, tugas, soal coding | ☐ | |
| 8 | Guru: buka lesson untuk kelas | ☐ | |
| 9 | Guru: isi absensi dan catatan sesi | ☐ | |
| 10 | Guru: nilai tugas | ☐ | |
| 11 | Siswa: peta Course, kerjakan materi, kuis, coding | ☐ | |
| 12 | Siswa: kerjakan dan kumpulkan tugas Scratch | ☐ | |
| 13 | Siswa: Laboratory (Python, Scratch, Presentation) dan bagikan | ☐ | |
| 14 | Like dan komentar di Community | ☐ | |
| 15 | Bertanya di Q&A, guru menjawab, pilih jawaban terbaik | ☐ | |
| 16 | Proyek kanban: geser kartu, guru menyetujui | ☐ | |
| 17 | Prestasi, badge, sertifikat, Archive, pencarian | ☐ | |
| 18 | Orang tua: beranda, rapor, laporan bulanan | ☐ | |
| 19 | Admin: notifikasi, audit log, verifikasi sertifikat | ☐ | |
| 20 | Tampilan di HP | ☐ | |

## Bila ada kendala

| Kendala | Penyebab dan solusi |
|---|---|
| Lupa kata sandi akun demo | Semua akun demo memakai `password` |
| Data berbeda dengan gambar | Data demo sudah berubah karena uji coba sebelumnya; minta tim mengulang data demo |
| Ingin peta Course memenuhi layar | Klik tombol layar penuh di kanan atas. Esc untuk keluar |
| Soal Python menampilkan "Python could not load in this browser" | Berkas Python belum ada di server atau tipe berkasnya salah. Teruskan teks "Details: …" ke tim teknis (lihat `docs/production.md`, bagian masalah umum) |
| Video materi tidak muncul | Video YouTube butuh internet |
| Editor Scratch lambat terbuka | Pertama kali dibuka, browser mengunduh editor (sekitar 17 MB); berikutnya lebih cepat |
| Soal Python lama di "Preparing Python…" | Pertama kali, browser memuat Python (sekitar 10 MB); tunggu sebentar |

Masukan dan pertanyaan bisa dicatat di kolom **Catatan** pada daftar periksa, lalu dikirim ke tim Alprogmatics.
