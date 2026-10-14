<?php

// Python di browser (Pyodide) gagal dimuat bila server mengirim .mjs/.wasm dengan tipe yang salah.
// Apache/LiteSpeed (hosting cPanel) membaca pemetaan ini dari public/.htaccess.
it('maps the Pyodide file types for Apache and LiteSpeed hosts', function () {
    $htaccess = file_get_contents(public_path('.htaccess'));

    expect($htaccess)
        ->toContain('AddType text/javascript .mjs')
        ->toContain('AddType application/wasm .wasm');
});

it('serves Pyodide from this server by default', function () {
    expect(config('coding.pyodide_url'))->toBe('/vendor/pyodide/');
});
