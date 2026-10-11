<?php

namespace Modules\Sessions\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Sessions\Models\ClassSession;

/**
 * Fired every time a session's attendance is saved (including corrections).
 * Listeners must be idempotent: the Finance module deducts quota and records tutor fees from here.
 */
class SessionCompleted
{
    use Dispatchable;

    public function __construct(public ClassSession $session) {}
}
