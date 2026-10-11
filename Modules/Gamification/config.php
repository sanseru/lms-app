<?php

return [
    /*
    | Tampilkan "Bintang kelas bulan ini" (5 siswa dengan XP terbanyak bulan
    | berjalan) di halaman prestasi siswa. Matikan jika tidak ingin ada peringkat.
    */
    'leaderboard' => env('GAMIFICATION_LEADERBOARD', true),
];
