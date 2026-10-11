<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\Models\Course;

class CoursePolicy
{
    public function view(User $user, Course $course): bool
    {
        return Course::query()->visibleTo($user)->whereKey($course->id)->exists();
    }

    public function manage(User $user): bool
    {
        return $user->can('learning.manage');
    }
}
