<?php

return [
    /*
    | Where student code runs for grading:
    |  - browser: code runs in the student's browser (Pyodide for Python, a Web Worker for JS);
    |    the server compares the output with answers that are only stored on the server.
    |  - judge0: the server sends code to Judge0 CE on a separate VPS. Harder to cheat.
    | Student code never runs on the Laravel server.
    */
    'driver' => env('CODING_DRIVER', 'browser'),

    'judge0' => [
        'url' => env('JUDGE0_URL'),
        'token' => env('JUDGE0_TOKEN'),
        // Judge0 CE language IDs (GET /languages).
        'languages' => ['python' => 71, 'javascript' => 63],
    ],

    'pyodide_url' => env('PYODIDE_URL', '/vendor/pyodide/'),
];
