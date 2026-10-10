<?php

namespace Modules\Sessions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Sessions\Models\ClassSession;

/**
 * Dipicu setiap kali absensi sesi selesai disimpan (termasuk koreksi).
 * Listener harus idempoten: modul Finance memotong kuota dan mencatat honor dari sini.
 */
class SessionCompleted
{
    use Dispatchable;

    public function __construct(public ClassSession $session) {}
}
