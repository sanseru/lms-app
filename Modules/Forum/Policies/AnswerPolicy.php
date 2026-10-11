<?php

namespace Modules\Forum\Policies;

use App\Models\User;
use Modules\Forum\Models\Answer;

class AnswerPolicy
{
    public function update(User $user, Answer $answer): bool
    {
        return $answer->author_id === $user->id;
    }

    public function delete(User $user, Answer $answer): bool
    {
        return $answer->author_id === $user->id || $answer->question->isStaff($user);
    }

    public function vote(User $user, Answer $answer): bool
    {
        return $answer->author_id !== $user->id && $user->can('answer', $answer->question);
    }
}
