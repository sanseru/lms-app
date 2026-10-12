<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Support\LevelAccess;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->can('learning.manage')) {
            return true;
        }

        return $lesson->is_published && $user->can('view', $lesson->course);
    }

    /** Taking quizzes, submitting assignments, marking done: students in a class using this course, or who unlocked this level. */
    public function learn(User $user, Lesson $lesson): bool
    {
        return $user->hasRole('student')
            && $lesson->is_published
            && $lesson->course->status === 'published'
            && app(LevelAccess::class)->allows($user, $lesson->course_id);
    }
}
