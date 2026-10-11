<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Learning\Actions\GradeQuiz;
use Modules\Learning\Models\Lesson;

class QuizController extends Controller
{
    public function store(Request $request, Lesson $lesson, GradeQuiz $grade): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        abort_unless($lesson->type === 'quiz', 422);

        $user = $request->user();
        $tries = $lesson->attempts()->where('student_id', $user->id)->count();

        if ($lesson->max_attempts && $tries >= $lesson->max_attempts) {
            return back()->with('error', 'You have no attempts left for this quiz.');
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ], [], ['answers' => 'answers']);

        $attempt = $grade->handle($lesson, $user, $data['answers']);

        return back()->with($attempt->passed ? 'success' : 'error', $attempt->passed
            ? "Great job! You scored {$attempt->score}. Quiz passed."
            : "You scored {$attempt->score}. You need {$lesson->passScore()} to pass, so give it another try.");
    }
}
