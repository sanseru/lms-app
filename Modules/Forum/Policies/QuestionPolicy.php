<?php

namespace Modules\Forum\Policies;

use App\Models\User;
use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Models\Question;

class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        return Question::query()->visibleTo($user)->whereKey($question->id)->exists();
    }

    /** Active students in the class, the class teacher, or an admin. */
    public function askIn(User $user, ClassRoom $class): bool
    {
        if ($user->hasRole('admin') || $class->teacher_id === $user->id) {
            return true;
        }

        return $user->hasRole('student')
            && $user->enrolledClasses()->whereKey($class->id)->wherePivot('status', 'active')->exists();
    }

    /** Parents can only read; students and the class teacher can answer. */
    public function answer(User $user, Question $question): bool
    {
        return $this->view($user, $question)
            && ($question->isStaff($user) || $this->askIn($user, $question->classRoom));
    }

    public function update(User $user, Question $question): bool
    {
        return $question->author_id === $user->id;
    }

    /** The asker can delete while there are no answers; teachers and admins can delete any time. */
    public function delete(User $user, Question $question): bool
    {
        return $question->isStaff($user) || ($question->author_id === $user->id && $question->answers_count === 0);
    }

    /** Pick the best answer or mark as resolved. */
    public function resolve(User $user, Question $question): bool
    {
        return $question->author_id === $user->id || $question->isStaff($user);
    }

    public function pin(User $user, Question $question): bool
    {
        return $question->isStaff($user);
    }

    public function vote(User $user, Question $question): bool
    {
        return $question->author_id !== $user->id && $this->answer($user, $question);
    }
}
