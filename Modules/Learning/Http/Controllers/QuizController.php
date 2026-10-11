<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Learning\Actions\GradeQuiz;
use Modules\Learning\Actions\QuizTimer;
use Modules\Learning\Models\Lesson;

class QuizController extends Controller
{
    public function start(Request $request, Lesson $lesson, QuizTimer $timer): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        abort_unless($lesson->type === 'quiz' && $lesson->time_limit_minutes, 422);

        if ($this->outOfTries($lesson, $request->user()->id)) {
            return back()->with('error', 'You have no attempts left for this quiz.');
        }

        $timer->start($lesson, $request->user()->id);

        return back();
    }

    public function store(Request $request, Lesson $lesson, GradeQuiz $grade, QuizTimer $timer): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        abort_unless($lesson->type === 'quiz', 422);

        $user = $request->user();

        if ($this->outOfTries($lesson, $user->id)) {
            return back()->with('error', 'You have no attempts left for this quiz.');
        }

        $data = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ], [], ['answers' => 'answers']);

        $duration = null;
        $late = false;

        if ($lesson->time_limit_minutes) {
            $started = $timer->startedAt($lesson, $user->id);

            if (! $started) {
                return back()->with('error', 'Start the quiz first so the timer can run.');
            }

            $duration = (int) $started->diffInSeconds(now());
            $late = $duration > $lesson->time_limit_minutes * 60 + QuizTimer::GRACE_SECONDS;
        }

        $attempt = $grade->handle($lesson, $user, $data['answers'], $duration, $late);
        $timer->forget($lesson, $user->id);

        if ($late) {
            return back()->with('error', "Time was up before your answers arrived. Score {$attempt->score} is saved, but this try does not count as a pass.");
        }

        return back()->with($attempt->passed ? 'success' : 'error', $attempt->passed
            ? "Great job! You scored {$attempt->score}. Quiz passed."
            : "You scored {$attempt->score}. You need {$lesson->passScore()} to pass, so give it another try.");
    }

    private function outOfTries(Lesson $lesson, int $studentId): bool
    {
        return $lesson->max_attempts && $lesson->attempts()->where('student_id', $studentId)->count() >= $lesson->max_attempts;
    }
}
