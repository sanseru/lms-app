# Panduan kerja di repo ini

- Bahasa UI dan pesan validasi: Indonesia. Locale `id`, zona waktu `Asia/Jakarta`.
- Arsitektur modular monolith: fitur baru masuk ke `Modules/<Nama>` (lihat README bagian "Struktur modul"), bukan ke `app/`.
- `app/` hanya untuk infrastruktur bersama: `User`, `Support/Modules`, `Support/Navigation`, middleware, auth Breeze.
- Hak akses: deklarasikan permission di `permissions()` provider modul, tegakkan dengan middleware `can:` atau Policy. Admin lolos semua cek lewat `Gate::before`, jadi aturan status bisnis (mis. tagihan lunas tidak bisa dibayar) harus dicek eksplisit, bukan hanya di policy.
- Controller tipis, validasi di Form Request, logika simpan yang dipakai ulang di `Actions/`.
- Frontend: Vue 3 `<script setup>`, Tailwind 4. Pakai komponen `resources/js/Components/ui` dan utilitas `btn-*`, `card`, `field`, `label`, `eyebrow` dari `resources/css/app.css`.
- Setiap fitur wajib punya test Pest di `tests/Feature/Modules`. Jalankan `php artisan test` dan `vendor/bin/pint` sebelum commit.
