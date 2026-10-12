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

    /**
     * Course catalog: a student may look at any published course (title, description,
     * lesson list) to spark interest. Lessons stay locked unless they take the course.
     */
    public function preview(User $user, Course $course): bool
    {
        return $user->hasRole('student') && $course->status === 'published';
    }

    public function manage(User $user): bool
    {
        return $user->can('learning.manage');
    }
}
