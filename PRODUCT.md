# Product

<!-- impeccable:product-schema 1 -->

> Ditulis lewat alur `/impeccable init` pada 2026-10-11. Fakta yang ditandai _(disimpulkan)_ diambil dari brief pemilik ("LMS untuk kursus pemrograman, target anak sekolah") dan dari kode repo, bukan dari wawancara terpisah. Koreksi langsung di file ini bila ada yang keliru.

## Platform

web

## Users

- **Siswa** SD sampai SMA (sekitar 6–17 tahun), pengguna utama. Membuka aplikasi di laptop sekolah/rumah atau ponsel orang tua, sering sebelum atau sesudah kelas Zoom. Tugasnya: tahu kelas berikutnya, melanjutkan materi, mengerjakan soal coding, bertanya ke guru, menggerakkan kartu proyek, dan melihat XP/lencana. Kemampuan membaca dan kesabaran sangat beragam antara anak 7 tahun dan remaja 16 tahun. _(disimpulkan)_
- **Orang tua (wali)**. Membuka di ponsel, singkat dan jarang. Ingin tahu: anak hadir atau tidak, belajar apa, perkembangan, sisa kuota, dan tagihan yang perlu dibayar lewat transfer bank.
- **Guru/tutor**. Mengajar lewat Zoom, mengisi absensi dan catatan sesi dalam satu layar, menilai tugas, menjawab Q&A, memeriksa kartu proyek, membuat soal coding, menulis catatan laporan bulanan, dan mengajar kelas trial.
- **Admin**. Operasional harian: siswa, guru, kelas, jadwal, tagihan, verifikasi bukti transfer, paket kuota, honor tutor, calon siswa dan kelas trial, audit log.

## Product Purpose

Alprogmatics adalah LMS untuk kursus coding online anak (kelas privat dan kelompok kecil via Zoom). Satu aplikasi menyatukan belajar mandiri (materi, kuis, soal coding yang dijalankan di browser), interaksi dengan guru (Q&A, proyek kanban), motivasi (XP, level, lencana, sertifikat), dan operasional kursus (jadwal, absensi, kuota, tagihan, honor). Sukses berarti anak kembali sendiri tanpa disuruh, orang tua percaya dan membayar tepat waktu, dan guru tidak butuh alat lain.

## Positioning

Kursus coding anak yang hidup di Zoom tetapi punya "rumah" sendiri: setiap sesi langsung tercatat menjadi laporan ke orang tua, setiap pelajaran punya tombol tanya guru, setiap proyek punya papan kerja per anak, dan soal coding dinilai otomatis tanpa membocorkan jawaban. Laporan ke orang tua dan kuota sesi berbasis absensi adalah inti yang tidak dimiliki LMS generik.

## Operating Context

- Kelas berlangsung di Zoom; aplikasi dipakai di sekitar kelas, bukan menggantikan Zoom.
- Pembayaran manual: transfer bank, unggah bukti, admin memverifikasi. Tidak ada payment gateway.
- Login dengan username atau email (anak tidak wajib punya email). Pendaftaran publik mati; akun dibuat admin.
- Seluruh teks yang dilihat pengguna (UI, validasi, notifikasi, data demo, segmen URL) berbahasa **Inggris**, locale `en`, zona waktu Asia/Jakarta, mata uang Rupiah. Dokumentasi di `docs/` tetap berbahasa Indonesia.
- Perangkat: laptop untuk siswa saat ngoding; ponsel untuk orang tua dan siswa saat cek jadwal/kartu proyek. _(disimpulkan)_

## Capabilities and Constraints

- Modular monolith Laravel 13 + Inertia 2 + Vue 3 + Tailwind 4 (lihat README "Struktur modul" dan CLAUDE.md).
- Empat dashboard per role di satu URL `/dashboard`.
- Coding: Python (Pyodide), JavaScript, HTML/CSS, Parsons; penilaian di server. Laboratory (Python, Scratch, Presentation, Website, JavaScript) dan Community untuk proyek bebas siswa; editor Scratch di-host sendiri dan juga bisa dipakai untuk tugas di lesson.
- Laporan bulanan orang tua, sertifikat, dan kuitansi dicetak ke PDF dari browser (tanpa library PDF di server).
- Notifikasi baru in-app; kanal email/WhatsApp belum ada.
- Harga, kontak WhatsApp, dan testimoni di landing masih contoh (`Modules/Landing/resources/js/site.js`).

## Brand Commitments

- Nama: **Alprogmatics**. Logo: `public/images/logo-header.png`.
- Warna brand yang sudah dipakai: ungu `#5B4185` dan coral `#F58464` (dari prototipe referensi, bukan kewajiban mutlak).
- Warna logo: cyan `#01C9FE` → biru `#0080F0` → ungu `#6010D0`, aksen oranye `#FE8003` dan kuning `#FEA002`.
- Font: Plus Jakarta Sans (judul), DM Sans (teks), DM Mono (kode).
- Nada: ramah, bermain, menyemangati anak, tetapi tepercaya dan jelas bagi orang tua. Copy UI memakai bahasa Inggris sederhana: kalimat pendek dan kata sehari-hari untuk siswa, sopan dan lugas untuk orang tua. _(disimpulkan dari brief dan copy yang ada)_

## Client Preferences

Keinginan klien yang sudah disampaikan langsung (WhatsApp, prototipe, review). Ini mengikat: jangan dibalik tanpa persetujuan pemilik.

- **Panel sorotan berwarna logo dan ceria.** Banner sapaan, kartu level, dan panel highlight lain memakai gradasi warna logo: biru → ungu → sentuhan coral (`panel-brand` di `resources/css/app.css`). Jangan kembali ke ungu tua polos, dan jangan membuat gradien lain di luar `panel-brand`. Teks diletakkan di sisi kiri (biru/ungu); tombol di atas panel berwarna putih.
- **Ikuti prototipe klien** (`docs/client-example/LMS-Alprogmatics/`) untuk nama menu, urutan, dan alur siswa. Menu siswa di bar atas: **Home, Course, Online Lesson, Community, Laboratory, Archive**, sisanya di **More**. Tidak ada menu **Classes** untuk siswa (kelas tampil di halaman Course).
- **Logo besar** di header (setara prototipe, tinggi ±62 px di desktop).
- **Course sebagai dunia penuh layar**: peta langit dengan START, lingkaran Lesson, dan kastel; membuka Course dan mengerjakan task masuk layar penuh browser.
- **Struktur Program → Level → Lesson → Task**, naik level step by step tanpa pindah kelas; task "mixed" dipecah.
- **Laboratory**: Python, Scratch (editor Scratch asli, di-host sendiri), Presentation, Website, JavaScript. Karya bisa dibagikan ke Community; siswa, guru, dan orang tua bisa like dan komentar.
- **Badge dan statistik ala prototipe** di beranda siswa (6 statistik, Popular in Community, badge First project, First like, dsb.).
- Riwayat keputusan lengkap: `docs/analisis-prototipe-klien.md`.

## Evidence on Hand

- Belum ada testimoni, angka siswa, atau logo sekolah mitra yang asli. Jangan mengarang testimoni, statistik, atau klaim "dipercaya oleh". Testimoni di landing ditandai sebagai contoh.
- Prototipe desain teman di `/mnt/project-files/LMS-Alprogmatics/` (referensi saja).

## Product Principles

1. **Anak dulu, tapi orang tua harus yakin.** Layar siswa boleh ceria; layar orang tua dan keuangan harus tenang dan mudah dipindai.
2. **Satu langkah berikutnya yang jelas.** Setiap dashboard menjawab "apa yang harus aku lakukan sekarang" sebelum menampilkan statistik.
3. **Motivasi dari karya nyata.** XP dan lencana merayakan pelajaran, soal, dan proyek yang benar-benar selesai, bukan sekadar membuka halaman.
4. **Jujur.** Tidak ada notifikasi palsu, angka hiasan, atau klaim yang tidak didukung data.

## Accessibility & Inclusion

- WCAG 2.2 AA sebagai batas bawah: kontras teks ≥ 4.5:1, fokus keyboard terlihat, target sentuh ≥ 44px di ponsel.
- Pembaca muda: kalimat pendek, kata sehari-hari, ikon selalu disertai teks.
- Hormati `prefers-reduced-motion`; animasi perayaan tidak boleh menghalangi tugas.
