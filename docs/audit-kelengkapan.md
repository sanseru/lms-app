# Audit kelengkapan terhadap riset

Audit awal: 11 Oktober 2026. Status diperbarui 12 Oktober 2026 setelah modul Audit, Reports, Leads, Lab, kuis benar/salah + isian + timer, dan aturan/versi soal coding selesai. UI kini berbahasa Inggris; URL di bawah memakai segmen bahasa Inggris.

## Riset LMS kursus privat

Prioritas P0 sampai P3 dari dokumen riset.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
| P0 | Model wali vs siswa, pendaftaran kelas, paket kuota sesi | Sudah ada | People (relasi orang tua ↔ anak), Classes (daftarkan siswa), Finance (paket 8/16/24 sesi) |
| P0 | Jadwal berulang, cek bentrok guru dan siswa, link Zoom per sesi | Sudah ada | Classes (cek bentrok guru & siswa), Sessions (`sessions:generate`) |
| P0 | Absensi + catatan sesi satu layar, otomatis potong kuota | Sudah ada | Sessions `CompleteSession` → event `SessionCompleted` → Finance |
| P0 | Tagihan, unggah bukti transfer, verifikasi admin, kuitansi | Sudah ada | Finance; kuitansi cetak dengan terbilang di `/finance/invoices/{id}/receipt` |
| P0 | Portal orang tua: jadwal, kehadiran, sisa kuota, tagihan | Sudah ada | Dashboard (tampilan orang tua), Learning (rapor), Finance |
| P1 | Notifikasi: pengingat H-1, tagihan, ringkasan sesi | Sebagian | Notifications (lonceng + `/notifications`) — Notifikasi in-app sudah jalan. Kanal email dan WhatsApp belum; WhatsApp butuh akun penyedia WA API |
| P1 | Honor tutor otomatis dari sesi selesai + slip | Sudah ada | Finance (payroll_items, slip bulanan) |
| P1 | Pindah jadwal dan sesi pengganti tertaut ke sesi asal | Sudah ada | Sessions `RescheduleSession` (tombol **Reschedule** / sesi pengganti). Permintaan pindah jadwal dari orang tua belum ada |
| P1 | Tanya jawab per pelajaran | Sudah ada | Forum (`/questions`) |
| P1 | Dashboard keuangan: omzet, piutang, siswa kuota habis | Sudah ada | Finance overview + daftar paket hampir habis |
| P1 | Pengingat ke tutor yang belum isi catatan sesi | Sudah ada | `sessions:remind-reports` tiap jam |
| P2 | Pipeline calon siswa di kanban + jadwal kelas trial | Sudah ada | Leads: formulir publik `/free-trial`, pipeline `/leads`, kelas trial guru `/leads/trials`, `ConvertLead` membuat akun |
| P2 | Portofolio proyek, sertifikat per level, laporan berkala ortu (PDF) | Sudah ada | Kanban (link hasil karya), Lab + Community, Certificates, Reports (laporan bulanan `/monthly-reports`, cetak PDF dari browser) |
| P2 | Feed iCal per tutor, PWA | Sebagian | Feed iCal per user di `/calendar/{token}.ics`. PWA belum |
| P2 | Badge dan streak siswa | Sebagian | Gamification (XP, level, 13 lencana) — Streak harian belum |
| P3 | Integrasi Zoom API | Belum | Link Zoom diisi manual per kelas/sesi |
| P3 | Payment gateway (QRIS/VA) | Sengaja tidak | Sesuai keputusan: transfer manual |
| P3 | Multi cabang dan istilah yang bisa diganti | Belum | – |
| Arsitektur | Modular monolith, event antar modul | Sudah ada | `Modules/*`, `config/modules.php`, event `SessionCompleted` |
| Arsitektur | Ledger keuangan append-only | Sebagian | Pembayaran dicatat per baris, tagihan dibatalkan bukan dihapus, perubahan tagihan/pembayaran tercatat di audit log append-only — Belum ada entri koreksi formal |
| Pembeda | Pengingat pertemuan orang tua (PTM) tiap N sesi | Belum | Bisa memakai Notify saat kuota terpakai mencapai 6, 12, … |

## Rancangan modul coding

Keputusan: kode dijalankan di browser, tidak di server.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
| Tahap 1 | Python, JavaScript, HTML/CSS di browser | Sudah ada | Coding (Pyodide, Web Worker, iframe sandbox) |
| Tahap 1 | Tes tersembunyi tidak dikirim ke browser, server menilai | Sudah ada | Coding `Grader` membandingkan output dengan jawaban di server — Sesuai keputusan, server tidak menjalankan kode siswa |
| Tahap 1 | Tipe input→output, fungsi, cek HTML/CSS, Parsons | Sudah ada | `CodeExercise::MODES` |
| Tahap 1 | Tebak output | Sebagian | Bisa dibuat sebagai kuis pilihan ganda — Belum ada tipe khusus dengan blok kode |
| Tahap 1 | Aturan kode (wajib for, dilarang sum, maks. baris) | Sudah ada | Coding, dicek di server saat menilai |
| Tahap 1 | Pembuat soal tutor dengan Uji solusi wajib | Sudah ada | Coding `ExerciseController::verify` |
| Tahap 1 | Petunjuk bertingkat, solusi terbuka setelah N gagal | Sudah ada | Coding |
| Tahap 1 | Versi soal (nilai lama tetap pada versi lama) | Sudah ada | Coding `CodeExerciseVersion` |
| Tahap 1 | Tombol Laporkan soal untuk siswa | Sudah ada | **Report a problem** di setiap pelajaran, ditangani guru di `/lesson-reports` |
| Tahap 1 | Pre/post-test, bank soal per konsep, normalized gain | Sudah ada | Learning (Warm-up Mission / Final Mission, kolom `concept`) |
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
| FR-06 | Kuis pilihan tunggal/ganda, nilai lulus, batas percobaan | Sudah ada | Learning `QuizQuestion::TYPES` (pilihan tunggal/ganda, benar/salah, isian singkat) + timer (`QuizTimer`) — Soal esai belum |
| FR-07 | Tugas teks/link/berkas, nilai, revisi | Sudah ada | Learning `AssignmentSubmission` — Riwayat versi pengumpulan belum |
| FR-08 | Coding Lab dengan runner antrean terpisah | Sengaja tidak | Diganti eksekusi di browser; Judge0 opsional |
| FR-09 | XP, level, lencana, leaderboard bisa dimatikan | Sudah ada | Gamification — Streak belum |
| FR-10 | Progres pelajaran, nilai, dashboard siswa dan guru | Sudah ada | Learning, Dashboard |
| FR-12 | Notifikasi in-app | Sudah ada | Notifications |
| FR-13 | Sertifikat dengan verifikasi publik dan pencabutan | Sudah ada | Certificates |
| FR-14 | Audit log (role, nilai, pembayaran) | Sebagian | Audit (`/audit`, tabel `activity_log` append-only): login, akun, nilai, absensi, tagihan, pembayaran, paket, honor, jadwal, sertifikat, calon siswa — Perubahan role belum tercatat |
| Persona | Teaching assistant | Belum | – |

## Permintaan langsung

Semua yang diminta di chat proyek.

| Prioritas | Butir | Status | Lokasi / catatan |
|---|---|---|---|
|  | Manajemen guru dan siswa (+ orang tua) | Sudah ada | People |
|  | Jadwal dan link Zoom | Sudah ada | Classes, Sessions |
|  | Kanban proyek | Sudah ada | Kanban (`/projects`) |
|  | Tanya jawab | Sudah ada | Forum (`/questions`) |
|  | Keuangan dengan transfer manual | Sudah ada | Finance |
|  | Honor tutor | Sudah ada | Finance (payroll) |
|  | Landing page | Sudah ada | Landing (`/`) |
|  | Pre-test dan post-test | Sudah ada | Learning (Warm-up Mission / Final Mission) |
|  | Multi bahasa coding | Sebagian | Python, JavaScript, HTML/CSS — C++/Java menunggu server Judge0 |
|  | Tutor membuat soal sendiri | Sudah ada | Coding (form soal + Uji solusi), Learning (kuis) |

## Usulan langkah berikutnya

Urutan kerja yang disepakati ada di [`AI_CONTEXT.md`](../AI_CONTEXT.md) bagian 9. Butir yang masih terbuka dari audit ini:

- **Tinggi** · Kanal notifikasi email dan WhatsApp: tabel notifikasi sudah ada; tinggal kanal pengirim lewat antrean. WhatsApp butuh akun penyedia (mis. Fonnte/Wablas/WA Cloud API) dan nomor.
- **Sedang** · Streak harian dan PWA: motivasi anak dan akses cepat dari HP.
- **Sedang** · Permintaan pindah jadwal dari orang tua, memakai `RescheduleSession` yang sudah ada.
- **Sedang** · Audit perubahan role, soal esai, riwayat versi pengumpulan tugas.
- **Rendah** · Blockly, Judge0, Zoom API, login Google + MFA admin, multi cabang: butuh server tambahan atau keputusan bisnis.
