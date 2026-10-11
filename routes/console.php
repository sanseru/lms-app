<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep class sessions generated a few weeks ahead.
Schedule::command('sessions:generate')->dailyAt('01:00');

// Recalculate XP nightly (also done when a student opens their achievements page).
Schedule::command('xp:sync')->dailyAt('01:30');

// Remind teachers who have not filled in session reports.
Schedule::command('sessions:remind-reports')->hourly();

// Tanggal 1 pagi: orang tua diberi tahu laporan bulan lalu sudah siap.
Schedule::command('reports:monthly')->monthlyOn(1, '08:00');
