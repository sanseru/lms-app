<?php

namespace Modules\Learning\Actions;

use App\Models\User;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\QuizAttempt;

/** Menilai jawaban kuis secara otomatis dan mencatat percobaannya. */
class GradeQuiz
{
    /**
     * @param  array<int|string, mixed>  $answers  question_id => indeks opsi, atau teks untuk soal isian
     * @param  bool  $late  dikumpulkan lewat batas waktu: nilai tetap dicatat, tapi tidak lulus
     */
    public function handle(Lesson $lesson, User $student, array $answers, ?int $duration = null, bool $late = false): QuizAttempt
    {
        $questions = $lesson->questions()->get();
        $total = (int) $questions->sum('points');
        $earned = 0;
        $result = [];

        foreach ($questions as $question) {
            $given = $answers[$question->id] ?? null;
            $correct = $question->isCorrect($given);
            $earned += $correct ? $question->points : 0;
            $result[$question->id] = ['given' => $question->normalizeGiven($given), 'correct' => $correct];
        }

        $score = $total > 0 ? (int) round($earned / $total * 100) : 0;

        $attempt = $lesson->attempts()->create([
            'student_id' => $student->id,
            'answers' => $result,
            'score' => $score,
            'earned_points' => $earned,
            'total_points' => $total,
            'passed' => ! $late && $score >= $lesson->passScore(),
            'duration_seconds' => $duration,
            'late' => $late,
        ]);

        if ($attempt->passed) {
            LessonProgress::markDone($lesson->id, $student->id);
        }

        return $attempt;
    }
}
