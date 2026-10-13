# Dokumentasi Alprogmatics LMS

| Dokumen | Isi |
|---|---|
| [Fitur yang bisa ditambahkan](roadmap-fitur.md) | Daftar fitur yang belum dibuat, prioritas, ukuran, dan data yang dibutuhkan dari pemilik |
| [Panduan uji coba klien](panduan-uji-coba/README.md) | Skenario uji coba dari awal sampai akhir (trial → siswa → tagihan → mengajar → belajar → orang tua) dengan gambar dan daftar periksa; PDF dibuat dengan skill `/panduan-uji-coba` |
| [Panduan pengguna](panduan-pengguna/README.md) | Cara memakai aplikasi: login, notifikasi, kalender, dan istilah |
| [Panduan admin](panduan-pengguna/admin.md) | Pengguna, calon siswa dan kelas trial, kelas dan jadwal, sesi, tagihan, verifikasi pembayaran, paket kuota, honor tutor, laporan bulanan, audit log |
| [Panduan guru](panduan-pengguna/guru.md) | Absensi dan catatan sesi, kursus, kuis, tugas (termasuk tugas Scratch), soal coding, penilaian, laporan soal, Q&A, proyek, Laboratory dan Community, laporan bulanan, kelas trial, sertifikat, honor |
| [Panduan siswa](panduan-pengguna/siswa.md) | Peta kursus, pencarian, materi, kuis, tugas (termasuk tugas Scratch), soal coding, proyek, Laboratory (Scratch, presentasi) dan Community, Online Lesson, bertanya, XP dan lencana, sertifikat, laporan bulanan, Archive |
| [Panduan orang tua](panduan-pengguna/orang-tua.md) | Memantau anak, membayar tagihan dan mengirim bukti transfer, rapor progres, laporan bulanan, proyek, prestasi |
| [Dokumentasi production](production.md) | Persyaratan server, instalasi, `.env`, database, build aset, cron, storage, backup, keamanan, deploy, troubleshooting, hal yang belum siap |
| [Audit kelengkapan](audit-kelengkapan.md) | Fitur yang sudah ada dan yang belum dibandingkan dengan riset |
| [Desain](design/) | Audit desain UI |
| [Analisis prototipe klien](analisis-prototipe-klien.md) | Apa yang sudah, belum, dan istilah yang diseragamkan dengan prototipe klien |
| [Contoh dari klien](client-example/README.md) | Prototipe web statis LMS-Alprogmatics dari klien, referensi desain saja (bukan bagian aplikasi) |

Screenshot panduan diambil dari UI berbahasa Inggris dengan data demo (`php artisan migrate:fresh --seed` di environment non-production), lebar 1366 px untuk desktop dan 390 px untuk HP. Teks panduan berbahasa Indonesia; label tombol dan menu ditulis persis seperti di UI (bahasa Inggris).
