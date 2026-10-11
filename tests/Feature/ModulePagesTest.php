<?php

/**
 * Every Inertia::render('Module::Page') in Modules must have its .vue file.
 */
test('every module inertia page exists', function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('Modules')));
    $missing = [];

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        preg_match_all("/Inertia::render\\('([A-Za-z]+)::([A-Za-z\\/]+)'/", file_get_contents($file->getPathname()), $matches, PREG_SET_ORDER);

        foreach ($matches as [, $module, $page]) {
            if (! is_file(base_path("Modules/{$module}/resources/js/Pages/{$page}.vue"))) {
                $missing[] = "{$module}::{$page}";
            }
        }
    }

    expect($missing)->toBe([]);
});
