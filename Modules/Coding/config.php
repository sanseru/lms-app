<?php

return [
    /*
    | Tempat kode siswa dijalankan untuk penilaian:
    |  - browser: kode jalan di browser siswa (Pyodide untuk Python, Web Worker untuk JS),
    |    server membandingkan output dengan jawaban yang hanya disimpan di server.
    |  - judge0: server mengirim kode ke Judge0 CE di VPS terpisah. Lebih sulit dicurangi.
    | Kode siswa tidak pernah dijalankan di server Laravel.
    */
    'driver' => env('CODING_DRIVER', 'browser'),

    'judge0' => [
        'url' => env('JUDGE0_URL'),
        'token' => env('JUDGE0_TOKEN'),
        // ID bahasa Judge0 CE (GET /languages).
        'languages' => ['python' => 71, 'javascript' => 63],
    ],

    'pyodide_url' => env('PYODIDE_URL', '/vendor/pyodide/'),
];
