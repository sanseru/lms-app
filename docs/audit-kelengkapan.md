# Audit kelengkapan terhadap riset

Tanggal: 11 Oktober 2026. 59 butir: 28 sudah ada, 5 baru dikerjakan, 9 sebagian, 13 belum, 4 sengaja tidak.

## Riset LMS kursus privat

Prioritas P0 sampai P3 dari dokumen riset.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
| P0 | Model wali vs siswa, pendaftaran kelas, paket kuota sesi | Sudah ada | People (relasi orang tua ↔ anak), Classes (daftarkan siswa), Finance (paket 8/16/24 sesi) |
| P0 | Jadwal berulang, cek bentrok guru dan siswa, link Zoom per sesi | Sudah ada | Classes (cek bentrok guru & siswa), Sessions (`sessions:generate`) |
| P0 | Absensi + catatan sesi satu layar, otomatis potong kuota | Sudah ada | Sessions `CompleteSession` → event `SessionCompleted` → Finance |
| P0 | Tagihan, unggah bukti transfer, verifikasi admin, kuitansi | Baru dikerjakan | Finance; kuitansi cetak dengan terbilang di `/finance/invoices/{id}/kuitansi` — Kuitansi ditambahkan di audit ini |
| P0 | Portal orang tua: jadwal, kehadiran, sisa kuota, tagihan | Sudah ada | Dashboard (tampilan orang tua), Learning (rapor), Finance |
| P1 | Notifikasi: pengingat H-1, tagihan, ringkasan sesi | Sebagian | Modul baru Notifications (lonceng + `/notifikasi`) — Notifikasi in-app sudah jalan. Kirim lewat WhatsApp belum, perlu penyedia WA API |
| P1 | Honor tutor otomatis dari sesi selesai + slip | Sudah ada | Finance (payroll_items, slip bulanan) |
| P1 | Pindah jadwal dan sesi pengganti tertaut ke sesi asal | Baru dikerjakan | Sessions `RescheduleSession`, tombol Pindah jadwal / Buat sesi pengganti — Ditambahkan di audit ini |
| P1 | Tanya jawab per pelajaran | Sudah ada | Forum (`/tanya`) |
| P1 | Dashboard keuangan: omzet, piutang, siswa kuota habis | Baru dikerjakan | Finance Overview + daftar Paket hampir habis — Daftar paket hampir habis ditambahkan di audit ini |
| P1 | Pengingat ke tutor yang belum isi catatan sesi | Baru dikerjakan | `sessions:remind-reports` tiap jam — Ditambahkan di audit ini |
| P2 | Pipeline calon siswa di kanban + jadwal kelas trial | Belum | Usulan: modul Leads (tahap tanya → trial → daftar) yang membuat akun siswa saat daftar |
| P2 | Portofolio proyek, sertifikat per level, laporan berkala ortu (PDF) | Sebagian | Kanban (link hasil karya), Certificates — Laporan berkala PDF untuk orang tua belum ada |
| P2 | Feed iCal per tutor, PWA | Sebagian | Feed iCal per user di `/kalender/{token}.ics` — iCal ditambahkan di audit ini. PWA belum |
| P2 | Badge dan streak siswa | Sebagian | Gamification (XP, level, 13 lencana) — Streak harian belum |
| P3 | Integrasi Zoom API | Belum | Link Zoom diisi manual per kelas/sesi |
| P3 | Payment gateway (QRIS/VA) | Sengaja tidak | Sesuai keputusan: transfer manual |
| P3 | Multi cabang dan istilah yang bisa diganti | Belum | – |
| Arsitektur | Modular monolith, event antar modul | Sudah ada | `Modules/*`, `config/modules.php`, event `SessionCompleted` |
| Arsitektur | Ledger keuangan append-only | Sebagian | Pembayaran dicatat per baris, tagihan dibatalkan bukan dihapus — Belum ada entri koreksi formal dan jejak audit |
| Pembeda | Pengingat pertemuan orang tua (PTM) tiap N sesi | Belum | Bisa memakai Notify saat kuota terpakai mencapai 6, 12, … |

## Rancangan modul coding

Keputusan: kode dijalankan di browser, tidak di server.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
| Tahap 1 | Python, JavaScript, HTML/CSS di browser | Sudah ada | Coding (Pyodide, Web Worker, iframe sandbox) |
| Tahap 1 | Tes tersembunyi tidak dikirim ke browser, server menilai | Sudah ada | Coding `Grader` membandingkan output dengan jawaban di server — Sesuai keputusan, server tidak menjalankan kode siswa |
| Tahap 1 | Tipe input→output, fungsi, cek HTML/CSS, Parsons | Sudah ada | `CodeExercise::MODES` |
| Tahap 1 | Tebak output | Sebagian | Bisa dibuat sebagai kuis pilihan ganda — Belum ada tipe khusus dengan blok kode |
| Tahap 1 | Aturan kode (wajib for, dilarang sum, maks. baris) | Belum | Usulan: aturan regex sederhana yang dicek di server |
| Tahap 1 | Pembuat soal tutor dengan Uji solusi wajib | Sudah ada | Coding `ExerciseController::verify` |
| Tahap 1 | Petunjuk bertingkat, solusi terbuka setelah N gagal | Sudah ada | Coding |
| Tahap 1 | Versi soal (nilai lama tetap pada versi lama) | Belum | Usulan: simpan snapshot tes di setiap submission |
| Tahap 1 | Tombol Laporkan soal untuk siswa | Belum | Bisa diarahkan ke Tanya Jawab privat |
| Tahap 1 | Pre/post-test, bank soal per konsep, normalized gain | Sudah ada | Learning (Misi Pemanasan/Akhir, kolom `concept`) |
| Tahap 1 | Pre-test adaptif untuk penempatan level | Belum | – |
| Tahap 2 | Judge0 untuk C++/Java dan lainnya | Sebagian | Driver `judge0` siap (`CODING_DRIVER=judge0`) — Butuh VPS terpisah, belum dipasang |
| Tahap 3 | Blockly untuk usia 7–10 tahun | Belum | – |
| Tahap 3 | Mode kelas Zoom: guru melihat kode siswa langsung | Belum | – |

## PRD CodeLearning

PRD ini untuk model langganan dan katalog publik. Butir yang bertentangan dengan keputusan kursus privat ditandai sengaja tidak.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
| FR-01 | Login, profil, RBAC di backend, reset password, rate limit | Sudah ada | Identity, Breeze, Spatie Permission, middleware `can:` |
| FR-01 | Daftar sendiri (register publik) | Sengaja tidak | Akun dibuat admin, sesuai alur kursus privat |
| FR-01 | Google OAuth, MFA admin (P1) | Belum | – |
| FR-02/05/11 | Katalog publik, langganan, checkout, webhook | Sengaja tidak | Model bisnis: paket kuota + transfer manual |
| FR-03/04 | Kursus, pelajaran (video, Markdown, kuis, tugas, coding), urutan | Sudah ada | Learning, Coding — Belum ada bagian/section dan bookmark |
| FR-06 | Kuis pilihan tunggal/ganda, nilai lulus, batas percobaan | Sebagian | Learning `QuizQuestion::TYPES` — Benar/salah, isian, esai, dan timer belum |
| FR-07 | Tugas teks/link/berkas, nilai, revisi | Sudah ada | Learning `AssignmentSubmission` — Riwayat versi pengumpulan belum |
| FR-08 | Coding Lab dengan runner antrean terpisah | Sengaja tidak | Diganti eksekusi di browser; Judge0 opsional |
| FR-09 | XP, level, lencana, leaderboard bisa dimatikan | Sudah ada | Gamification — Streak belum |
| FR-10 | Progres pelajaran, nilai, dashboard siswa dan guru | Sudah ada | Learning, Dashboard |
| FR-12 | Notifikasi in-app | Baru dikerjakan | Notifications — Ditambahkan di audit ini |
| FR-13 | Sertifikat dengan verifikasi publik dan pencabutan | Sudah ada | Certificates |
| FR-14 | Audit log (role, nilai, pembayaran) | Belum | Usulan: tabel `activity_log` yang dicatat dari model event |
| Persona | Teaching assistant | Belum | – |

## Permintaan langsung

Semua yang diminta di chat proyek.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
|  | Manajemen guru dan siswa (+ orang tua) | Sudah ada | People |
|  | Jadwal dan link Zoom | Sudah ada | Classes, Sessions |
|  | Kanban proyek | Sudah ada | Kanban (`/proyek`) |
|  | Tanya jawab | Sudah ada | Forum (`/tanya`) |
|  | Keuangan dengan transfer manual | Sudah ada | Finance |
|  | Honor tutor | Sudah ada | Finance (payroll) |
|  | Landing page | Sudah ada | Landing (`/`) |
|  | Pre-test dan post-test | Sudah ada | Learning (Misi Pemanasan/Akhir) |
|  | Multi bahasa coding | Sebagian | Python, JavaScript, HTML/CSS — C++/Java menunggu server Judge0 |
|  | Tutor membuat soal sendiri | Sudah ada | Coding (form soal + Uji solusi), Learning (kuis) |

## Usulan langkah berikutnya

- **Tinggi** · Kirim notifikasi lewat WhatsApp: Tabel notifikasi sudah ada; tinggal kanal pengirim (mis. Fonnte/Wablas/WA Cloud API) yang membaca baris baru. Butuh akun penyedia dan nomor.
- **Tinggi** · Audit log: Catat siapa mengubah nilai, memverifikasi pembayaran, dan mengubah role. Penting untuk kepercayaan keuangan.
- **Tinggi** · Laporan berkala orang tua (PDF): Rekap bulanan: kehadiran, materi, nilai, proyek, sisa kuota. Cetak dari browser seperti sertifikat.
- **Sedang** · Pipeline calon siswa + kelas trial: Papan tahap calon siswa, jadwal trial, lalu buat akun saat daftar.
- **Sedang** · Soal coding: aturan kode, versi soal, Laporkan soal: Melengkapi rancangan Tahap 1.
- **Sedang** · Kuis: benar/salah, isian, timer: Melengkapi FR-06.
- **Sedang** · Streak dan PWA: Motivasi anak dan akses cepat dari HP.
- **Rendah** · Blockly, Judge0, Zoom API, multi cabang: Butuh server tambahan atau keputusan bisnis.
