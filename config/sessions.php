<?php

return [
    // Status kehadiran yang memotong kuota paket siswa.
    // Izin dan sakit tidak memotong kuota; alpa (tanpa kabar) tetap dihitung.
    'quota_statuses' => ['present', 'absent'],

    // Berapa minggu ke depan sesi dibuat otomatis dari jadwal mingguan kelas.
    'generate_weeks_ahead' => 4,
];
