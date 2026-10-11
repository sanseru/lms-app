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

    /** Fill in session attendance and notes. */
    public function report(User $user, ClassSession $session): bool
    {
        return $session->teacher_id === $user->id && $session->isReportable();
    }

    public function cancel(User $user, ClassSession $session): bool
    {
        return $user->can('classes.manage');
    }

    /** Reschedule or create a replacement session: admin or that session's teacher. */
    public function reschedule(User $user, ClassSession $session): bool
    {
        return $user->can('classes.manage') || $session->teacher_id === $user->id;
    }
}
