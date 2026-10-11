# Panduan Admin

Admin mengelola seluruh operasional: akun pengguna, kelas dan jadwal, sesi, kurikulum, keuangan, dan honor tutor. Admin bisa membuka semua halaman, termasuk yang dipakai guru.

Baca dulu [hal yang sama untuk semua peran](README.md#hal-yang-sama-untuk-semua-peran) (login, notifikasi, kalender).

## Urutan kerja yang disarankan saat mulai

1. Tambah **guru** (beserta honor per sesi).
2. Tambah **siswa**, lalu **orang tua** dan hubungkan ke anaknya.
3. Buat **kelas** dengan jadwal mingguan dan link Zoom, lalu daftarkan siswa.
4. Buat **paket kuota** dan jual ke siswa (tagihan dibuat otomatis).
5. Guru (atau admin) membuat **kursus** dan memasangnya ke kelas.
6. Setiap akhir bulan, rekap **honor tutor** menjadi slip.

## Beranda

![Beranda admin](img/admin-beranda.png)

Beranda admin merangkum:

- **Siswa aktif, Guru aktif, Kelas berjalan, Tagihan belum lunas**.
- **Kelas hari ini**, termasuk sesi yang *belum diisi absensinya*.
- **Bukti transfer baru** yang perlu diverifikasi.
- Paket siswa dengan **sisa kuota ≤ 2 sesi**.
- **Siswa terbaru**, serta tombol cepat **Buat kelas** dan **Siswa baru**.

## Pengguna

Menu **Pengguna** berisi Siswa, Guru, dan Orang Tua. Setiap akun punya username untuk login, email opsional (kecuali kebutuhan reset kata sandi), dan status **Aktif/Nonaktif**. Akun nonaktif tidak bisa login.

### Siswa

![Daftar siswa](img/admin-siswa.png)

1. Buka **Siswa → Tambah siswa**.
2. **Akun login**: nama, username (huruf kecil, angka, `-` atau `_`), email (opsional), kata sandi, status. Anak bisa login dengan username tanpa email.
3. **Data siswa**: tanggal lahir, jenis kelamin, asal sekolah, kelas di sekolah (mis. "5 SD"), tanggal bergabung, alamat, catatan.
4. **Orang tua / wali**: pilih orang tua yang sudah ada, atau klik **Tambah orang tua**.
5. Klik **Simpan**. Kode siswa dibuat otomatis (`SIS-00001`, dst.).

![Form siswa](img/admin-siswa-form.png)

Gunakan kotak cari dan filter status (Semua/Aktif/Nonaktif) di daftar. Tombol hapus menonaktifkan akun dan menyembunyikannya dari daftar; riwayat belajar dan keuangannya tetap tersimpan.

**Mengganti kata sandi siswa yang lupa**: buka **Ubah** pada siswa tersebut, isi kata sandi baru, simpan.

### Guru

![Form guru](img/admin-guru-form.png)

1. **Guru → Tambah guru**.
2. Isi akun login, lalu **Profil pengajar** (keahlian, tanggal bergabung, bio singkat yang tampil ke siswa dan orang tua).
3. Bagian **Honor**: isi **Honor per sesi (Rp)**, bank, dan nomor rekening. Honor dihitung otomatis dari setiap sesi yang selesai diajar.
4. **Simpan**. Kode guru dibuat otomatis (`GRU-0001`).

### Orang tua

![Form orang tua](img/admin-ortu-form.png)

1. **Orang Tua → Tambah orang tua**.
2. Isi akun login. **Nomor HP wajib** untuk konfirmasi pembayaran.
3. Di bagian **Anak**, pilih siswa yang menjadi tanggungannya dan **Hubungan** (Ayah/Ibu/Wali). Satu orang tua bisa punya beberapa anak.
4. **Simpan**. Orang tua kini bisa melihat jadwal, progres, proyek, prestasi, sertifikat, dan tagihan anaknya.

## Kelas dan jadwal

### Membuat kelas

![Form kelas](img/admin-kelas-form.png)

1. **Kelas → Buat kelas**.
2. **Informasi kelas**: nama (mis. "Python Start 1 – Sabtu Pagi"), mata pelajaran, tingkat, guru, deskripsi, warna kartu.
3. **Jadwal mingguan**: hari, jam mulai, jam selesai, tanggal mulai dan selesai, kapasitas siswa, status (Akan datang/Berjalan/Selesai).
4. **Zoom**: link, Meeting ID, passcode.
5. **Simpan**. Sistem menolak jadwal yang bentrok dengan kelas lain milik guru yang sama.

### Mendaftarkan siswa ke kelas

![Detail kelas](img/admin-kelas-detail.png)

1. Buka kelas dari daftar **Kelas**.
2. Di bagian **Siswa**, klik **Tambah siswa**, pilih siswa aktif yang belum terdaftar, simpan.
3. Untuk mengeluarkan siswa, klik ikon **Keluarkan dari kelas** di baris siswa tersebut.

Daftar siswa kelas hanya terlihat oleh guru kelas dan admin.

### Sesi dibuat otomatis

Sesi (pertemuan per tanggal) dibuat otomatis dari jadwal mingguan, empat minggu ke depan, setiap malam. Bila perlu segera (mis. kelas baru), buka detail kelas dan klik **Buat sesi 8 minggu**.

### Jadwal mingguan

![Jadwal](img/admin-jadwal.png)

Menu **Jadwal** menampilkan semua sesi minggu ini per hari. Gunakan panah untuk pindah minggu atau **Minggu ini** untuk kembali. Ikon menandai sesi yang sudah selesai dan yang **absensinya belum diisi**.

## Sesi dan absensi

![Daftar sesi](img/admin-sesi.png)

Menu **Sesi & Absensi** berisi tab sesi mendatang, selesai, dan batal/dipindah. Biasanya guru yang mengisi laporan sesi (lihat [panduan guru](guru.md#mengisi-absensi-dan-catatan-sesi)), tetapi admin juga bisa.

Tindakan khusus admin pada halaman sesi:

- **Batalkan** sesi terjadwal (isi alasan, mis. "Libur nasional"). Kuota siswa tidak terpotong dan honor tidak dihitung.
- **Buat sesi pengganti** untuk sesi yang dibatalkan, atau **Pindah jadwal** sesi terjadwal. Sesi baru tertaut ke sesi asal, dan sistem mengecek bentrok jadwal guru. Setiap sesi hanya bisa dipindah/diganti sekali.
- Sesi yang guru beri tanda **Tandai untuk admin** berarti ada kendala yang perlu ditindaklanjuti; sesi ini muncul di Beranda sebagai *perlu ditindaklanjuti*.

## Kursus, materi, dan penilaian

Admin memiliki akses yang sama dengan guru untuk **Kursus & Materi**, **Penilaian Tugas**, **Proyek**, **Tanya Jawab**, **Prestasi Siswa**, dan **Sertifikat**. Langkah-langkahnya ada di [panduan guru](guru.md).

## Keuangan

Pembayaran dilakukan orang tua lewat **transfer bank manual**. Tidak ada payment gateway. Alurnya: admin membuat tagihan → orang tua transfer dan mengunggah bukti → admin memverifikasi → status tagihan berubah otomatis.

### Ringkasan keuangan

![Ringkasan keuangan](img/admin-keuangan.png)

Menampilkan **Diterima bulan ini, Belum terbayar, Tagihan terlambat, Menunggu verifikasi**, grafik ditagihkan vs diterima 6 bulan terakhir, daftar **Perlu diverifikasi**, dan **Paket hampir habis** (sisa ≤ 2 sesi atau kedaluwarsa ≤ 14 hari). Gunakan daftar terakhir untuk menawarkan perpanjangan.

### Membuat tagihan

![Form tagihan](img/admin-tagihan-form.png)

1. **Tagihan → Buat tagihan**.
2. **Ditagihkan ke**: satu siswa, atau semua siswa aktif dalam satu kelas sekaligus.
3. **Rincian**: judul (mis. "SPP Oktober 2026"), nominal, periode, jatuh tempo, catatan untuk orang tua.
4. **Buat tagihan**. Orang tua menerima notifikasi.

![Daftar tagihan](img/admin-tagihan.png)

Status tagihan: **Belum bayar**, **Sebagian**, **Lunas**, **Dibatalkan**. Tagihan dengan label *bukti baru* berisi pembayaran yang menunggu verifikasi. Tagihan yang belum ada pembayarannya bisa **Batalkan** (tidak dihapus, demi jejak keuangan).

### Memverifikasi pembayaran

![Verifikasi pembayaran](img/admin-verifikasi.png)

1. Buka tagihan dari **Perlu diverifikasi** atau daftar tagihan.
2. Di **Riwayat pembayaran**, klik **Bukti** untuk melihat bukti transfer, lalu cocokkan dengan mutasi rekening.
3. Klik **Verifikasi** bila sesuai. Bila tidak, klik **Tolak** dan isi alasan (terlihat oleh orang tua, mis. "Nominal tidak sesuai mutasi").
4. Status tagihan menjadi **Sebagian** atau **Lunas** otomatis. Orang tua mendapat notifikasi hasilnya.

Untuk pembayaran tunai atau transfer yang dikonfirmasi lewat jalur lain, gunakan **Catat pembayaran** di halaman tagihan (bukti boleh dikosongkan).

### Kuitansi

Tagihan dengan pembayaran terverifikasi punya tombol **Kuitansi**: halaman siap cetak lengkap dengan nominal terbilang. Cetak atau simpan sebagai PDF dari browser.

### Paket kuota

![Paket kuota](img/admin-paket.png)

Paket berisi sejumlah sesi yang dibayar di muka.

1. **Paket Kuota → Paket baru**: nama (mis. "Reguler 8 sesi"), jumlah sesi, masa berlaku (hari), harga (isi 0 untuk kelas trial gratis), keterangan.
2. **Jual paket**: pilih paket, siswa, opsional **Khusus kelas** (kosongkan agar berlaku untuk semua kelas siswa), dan jatuh tempo. Tagihan dibuat otomatis.
3. Paket berstatus **Menunggu pembayaran** sampai tagihannya **Lunas**, lalu menjadi **Aktif**.
4. Setiap sesi selesai, kuota terpotong otomatis: **Hadir** dan **Alpa** memotong, **Izin** dan **Sakit** tidak.
5. Paket berakhir sebagai **Habis** atau **Kedaluwarsa**. Paket bisa **Dibatalkan** dari kolom Aksi.

Filter **Kuota hampir habis** membantu menemukan siswa yang perlu perpanjangan.

### Honor tutor

![Honor tutor](img/admin-honor.png)

Honor tercatat otomatis setiap guru menyelesaikan sesi, sesuai **Honor per sesi** di profil guru.

1. Buka **Honor Tutor**. Bagian atas menampilkan sesi selesai yang **belum direkap** per guru untuk bulan terpilih.
2. Klik **Buat slip** pada guru, isi **Bonus**, **Potongan**, dan catatan bila ada, lalu simpan. Sesi-sesi tersebut masuk ke slip.
3. Buka slip, klik **Tandai dibayar** setelah transfer, isi tanggal bayar dan unggah bukti transfer (opsional).
4. Slip bisa **Cetak**. Slip yang belum dibayar bisa **Hapus**; sesinya kembali ke daftar belum direkap.

![Slip honor](img/admin-slip.png)

Guru melihat slipnya sendiri di menu **Honor Saya**.

## Hal yang perlu diketahui admin

- Admin lolos semua cek hak akses, tetapi aturan bisnis tetap berlaku (mis. tagihan lunas tidak bisa dibayar lagi, sesi yang sudah dipindah tidak bisa dipindah lagi).
- Notifikasi saat ini hanya muncul di dalam aplikasi (lonceng). Pengiriman lewat WhatsApp atau email belum tersedia.
- Rekening tujuan transfer yang tampil ke orang tua diatur di konfigurasi server (`FINANCE_BANK_*`), bukan dari menu. Lihat [dokumentasi production](../production.md#konfigurasi-env).
- Isi halaman depan (kontak, harga, testimoni) diatur di kode, bukan dari menu. Lihat [dokumentasi production](../production.md#menyesuaikan-halaman-depan).
