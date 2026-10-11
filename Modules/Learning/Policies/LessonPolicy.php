<?php

namespace Modules\Learning\Policies;

use App\Models\User;
use Modules\Learning\Models\Lesson;

class LessonPolicy
{
    public function view(User $user, Lesson $lesson): bool
    {
        if ($user->can('learning.manage')) {
            return true;
        }

        return $lesson->is_published && $user->can('view', $lesson->course);
    }

    /** Taking quizzes, submitting assignments, marking done: only students in a class using this course. */
    public function learn(User $user, Lesson $lesson): bool
    {
        return $user->hasRole('student')
            && $lesson->is_published
            && $lesson->course->status === 'published'
            && $user->enrolledClasses()->where('course_id', $lesson->course_id)->exists();
    }
}
