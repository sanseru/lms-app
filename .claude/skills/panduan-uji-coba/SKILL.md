---
name: panduan-uji-coba
description: Buat ulang panduan uji coba klien (docs/panduan-uji-coba) dari awal sampai akhir: reset data demo, jalankan walkthrough Playwright yang benar-benar mengisi form di aplikasi, ambil ulang semua screenshot, perbarui teks panduan bila tampilan berubah, cetak PDF A4, lalu commit dan buat PR. Pakai hanya saat pengguna minta, mis. "update panduan uji coba" atau "buat ulang PDF panduan klien". Jangan dijalankan otomatis setelah perubahan UI.
---

# Panduan uji coba klien

Hasil: `docs/panduan-uji-coba/README.md` (sumber, bahasa Indonesia, label UI bahasa Inggris persis seperti di layar), gambar `docs/panduan-uji-coba/img/*.jpg`, dan PDF `docs/panduan-uji-coba/dist/Panduan-Uji-Coba-Alprogmatics.pdf` (tidak di-commit; kirim ke pengguna).

## Langkah

1. **Siapkan lingkungan** (sekali per mesin):
   - `composer install`, `npm install`, `.env` dengan `APP_KEY`.
   - Browser Playwright: di Claude Code cloud sudah ada (`/opt/pw-browsers`); di lokal jalankan `npx playwright install chromium`.
   - `npm run build` (memasang Pyodide dan editor Scratch, lalu build aset).
2. **Jalankan server** di background: `php artisan serve --port=8000` (atau set `APP_URL` untuk alamat lain).
3. **Reset data demo**: `scripts/uji-coba/reset.sh`. Walkthrough mengubah data, jadi selalu reset sebelum menjalankan ulang. JANGAN jalankan ini terhadap database produksi.
4. **Walkthrough**: hapus gambar lama (`rm docs/panduan-uji-coba/img/*.jpg`), lalu `node scripts/uji-coba/walkthrough.mjs`.
   - Bila sebuah langkah gagal, pesan menyebut nama langkahnya. Perbaiki selektor di `walkthrough.mjs` (lihat UI di `Modules/*/resources/js/Pages`), reset data, dan ulangi. Untuk mencoba sebagian: `ONLY=f,g node …` (bab A–J; bab G butuh F dijalankan lebih dulu pada data yang sama).
5. **Periksa gambar**: buka beberapa screenshot (terutama yang alurnya berubah). Pastikan tidak ada halaman error, pesan sukses tampil, dan isi sesuai teks panduan.
6. **Sesuaikan teks**: bila tombol, menu, atau alur berubah, perbarui `README.md` agar label sama persis dengan UI. Screenshot baru yang ditambahkan ke walkthrough harus dirujuk di README.
7. **Cek**: `node scripts/uji-coba/check.mjs` (gambar yang dirujuk harus ada; peringatan untuk screenshot yang belum dipakai).
8. **PDF**: `node scripts/uji-coba/build-pdf.mjs`. Lihat beberapa halaman (mis. `pdftoppm -r 40 -png … `) untuk memastikan sampul, daftar isi, dan gambar tampil.
9. **Selesaikan**: `php artisan test`, `vendor/bin/pint`, commit (`docs/panduan-uji-coba`, skrip yang diubah), push ke branch kerja, buat PR, dan kirim PDF ke pengguna (SendUserFile). Jangan commit folder `dist/`.

## Catatan

- Akun demo: `admin`, guru `budi`/`sari`/`rizky`, siswa `aldi`, orang tua `ortu1` (anak Aldi, Nadia) dan `ortu5` (anak Bima, Citra); kata sandi `password`.
- Data yang dibuat walkthrough: lead **Kayla** (orang tua Rina Wijaya), tagihan **Scratch workshop (1 day)** untuk Bima, tugas Scratch **Project: My dancing cat**, pertanyaan Q&A **Why does range(3) stop at 2?**. Teks README merujuk nama-nama ini.
- Screenshot desktop 1366 px, HP 390 px, JPEG kualitas 78. Gambar contoh bukti transfer dibuat dari HTML (tidak memakai data bank asli).
- Video YouTube dan gambar pustaka sprite Scratch butuh internet; di lingkungan tanpa internet kotak video tampil kosong (wajar).
