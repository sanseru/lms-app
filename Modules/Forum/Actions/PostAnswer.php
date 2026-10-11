<?php

namespace Modules\Forum\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;

class PostAnswer
{
    public function handle(Question $question, User $author, array $data): Answer
    {
        return DB::transaction(function () use ($question, $author, $data) {
            $staff = $question->isStaff($author);

            $answer = $question->answers()->create([
                'author_id' => $author->id,
                'body' => $data['body'],
                'code' => $data['code'] ?? null,
                'by_staff' => $staff,
            ]);

            $question->forceFill([
                'answers_count' => $question->answers()->count(),
                'staff_answered_at' => $staff ? ($question->staff_answered_at ?? now()) : $question->staff_answered_at,
                'last_activity_at' => now(),
            ])->saveQuietly();

            return $answer;
        });
    }
}
