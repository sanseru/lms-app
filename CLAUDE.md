# Panduan kerja di repo ini

- Bahasa UI, pesan validasi, notifikasi, dan data demo: **Inggris**. Locale `en`, zona waktu `Asia/Jakarta`, mata uang Rupiah. Dokumentasi di `docs/` dan komentar boleh bahasa Indonesia, tetapi teks baru yang dilihat pengguna wajib bahasa Inggris. Segmen URL juga bahasa Inggris (nama route tidak berubah).
- Arsitektur modular monolith: fitur baru masuk ke `Modules/<Nama>` (lihat README bagian "Struktur modul"), bukan ke `app/`.
- `app/` hanya untuk infrastruktur bersama: `User`, `Support/Modules`, `Support/Navigation`, middleware, auth Breeze.
- Hak akses: deklarasikan permission di `permissions()` provider modul, tegakkan dengan middleware `can:` atau Policy. Admin lolos semua cek lewat `Gate::before`, jadi aturan status bisnis (mis. tagihan lunas tidak bisa dibayar) harus dicek eksplisit, bukan hanya di policy.
- Controller tipis, validasi di Form Request, logika simpan yang dipakai ulang di `Actions/`.
- Frontend: Vue 3 `<script setup>`, Tailwind 4. Pakai komponen `resources/js/Components/ui` dan utilitas `btn-*`, `card`, `field`, `label`, `eyebrow` dari `resources/css/app.css`.
- Setiap fitur wajib punya test Pest di `tests/Feature/Modules`. Jalankan `php artisan test` dan `vendor/bin/pint` sebelum commit.
- Desain: konteks produk ada di `PRODUCT.md`, audit terakhir di `docs/design/`. Skill `/impeccable` terpasang di `.claude/skills/impeccable` (jalankan `audit`, `polish`, dll.). Jangan pakai gradien ungu, teks putih di atas coral, atau emoji sebagai ikon UI; pakai `panel-brand` untuk panel sorotan.
