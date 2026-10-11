<?php

namespace Modules\Sessions\Policies;

use App\Models\User;
use Modules\Sessions\Models\ClassSession;

class ClassSessionPolicy
{
    public function view(User $user, ClassSession $session): bool
    {
        return $session->teacher_id === $user->id || $user->can('view', $session->classRoom);
    }

    /** Mengisi absensi dan catatan sesi. */
    public function report(User $user, ClassSession $session): bool
    {
        return $session->teacher_id === $user->id && $session->isReportable();
    }

    public function cancel(User $user, ClassSession $session): bool
    {
        return $user->can('classes.manage');
    }

    /** Memindah sesi atau membuat sesi pengganti: admin atau guru sesi itu. */
    public function reschedule(User $user, ClassSession $session): bool
    {
        return $user->can('classes.manage') || $session->teacher_id === $user->id;
    }
}
