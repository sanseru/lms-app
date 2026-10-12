# Contoh dari klien: LMS-Alprogmatics

Folder [`LMS-Alprogmatics/`](LMS-Alprogmatics/) adalah prototipe web statis (HTML, CSS, JavaScript) dari klien. Isinya disalin apa adanya sebagai **referensi desain dan alur**, bukan bagian dari aplikasi Laravel.

- `student/`: halaman siswa (beranda, kursus dan peta belajar Python, laboratorium, kelas online, komunitas, arsip, login).
- `teacher/`: halaman guru dengan struktur serupa.

Cara melihat: buka `LMS-Alprogmatics/student/index.html` atau `LMS-Alprogmatics/teacher/index.html` langsung di browser, atau jalankan server statis, mis. `python3 -m http.server -d docs/client-example/LMS-Alprogmatics`.

Catatan:

- Jangan mengubah isi prototipe ini. Ambil inspirasinya, lalu bangun fiturnya di `Modules/<Nama>` sesuai konvensi repo.
- Folder ini tidak ikut di-build: Tailwind mengecualikan `docs/` (`@source not` di `resources/css/app.css`), Vite hanya memakai `resources/js/app.js`, dan Pest hanya menjalankan `tests/`.
- Login di prototipe (`aldi` / `guru`, sandi `12345`) hanya cek di sisi browser untuk demo, bukan kredensial nyata.
- Ringkasan token desain (warna ungu `#5B4185`, coral `#F58464`, font Plus Jakarta Sans, DM Sans, DM Mono) dan konteks produk ada di [`PRODUCT.md`](../../PRODUCT.md).
