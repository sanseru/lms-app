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

    /** Siswa aktif di kelas, guru kelas, atau admin. */
    public function askIn(User $user, ClassRoom $class): bool
    {
        if ($user->hasRole('admin') || $class->teacher_id === $user->id) {
            return true;
        }

        return $user->hasRole('student')
            && $user->enrolledClasses()->whereKey($class->id)->wherePivot('status', 'active')->exists();
    }

    /** Orang tua hanya membaca; siswa dan guru kelas boleh menjawab. */
    public function answer(User $user, Question $question): bool
    {
        return $this->view($user, $question)
            && ($question->isStaff($user) || $this->askIn($user, $question->classRoom));
    }

    public function update(User $user, Question $question): bool
    {
        return $question->author_id === $user->id;
    }

    /** Penanya boleh menghapus selama belum ada jawaban; guru dan admin boleh kapan saja. */
    public function delete(User $user, Question $question): bool
    {
        return $question->isStaff($user) || ($question->author_id === $user->id && $question->answers_count === 0);
    }

    /** Memilih jawaban terbaik atau menandai selesai. */
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
