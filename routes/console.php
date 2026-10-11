<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sesi kelas selalu tersedia beberapa minggu ke depan.
Schedule::command('sessions:generate')->dailyAt('01:00');

// XP dihitung ulang setiap malam (juga otomatis saat siswa membuka halaman prestasi).
Schedule::command('xp:sync')->dailyAt('01:30');

// Guru yang belum mengisi laporan sesi diingatkan lewat notifikasi.
Schedule::command('sessions:remind-reports')->hourly();

// Tanggal 1 pagi: orang tua diberi tahu laporan bulan lalu sudah siap.
Schedule::command('reports:monthly')->monthlyOn(1, '08:00');
