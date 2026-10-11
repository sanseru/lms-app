<?php

namespace Modules\Learning\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\QuizAttempt;

/**
 * Ringkasan belajar seorang siswa per kursus: progres, nilai kuis terbaik,
 * nilai tugas, dan materi berikutnya. Dipakai portal siswa, orang tua, dan guru.
 */
class StudentProgress
{
    /** @return Collection<int, array<string, mixed>> */
    public function forStudent(User $student): Collection
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->whereHas('classes.students', fn ($q) => $q->whereKey($student->id))
            ->with(['lessons' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('title')
            ->get();

        $lessonIds = $courses->flatMap->lessons->pluck('id');

        $done = LessonProgress::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)->pluck('completed_at', 'lesson_id');
        $bestQuiz = QuizAttempt::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)
            ->selectRaw('lesson_id, max(score) as best, count(*) as tries')->groupBy('lesson_id')->get()->keyBy('lesson_id');
        $submissions = AssignmentSubmission::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');

        return $courses->map(function (Course $course) use ($done, $bestQuiz, $submissions) {
            $lessons = $course->lessons->map(fn ($lesson) => [
                ...$lesson->toItem(),
                'done' => $done->has($lesson->id),
                'quiz_best' => $bestQuiz->get($lesson->id)?->best,
                'quiz_tries' => $bestQuiz->get($lesson->id)?->tries,
                'submission_status' => $submissions->get($lesson->id)?->status,
                'submission_label' => AssignmentSubmission::STATUSES[$submissions->get($lesson->id)?->status] ?? null,
                'submission_score' => $submissions->get($lesson->id)?->score,
                'max_score' => $lesson->type === 'assignment' ? $lesson->maxScore() : null,
            ]);

            $total = $lessons->count();
            $completed = $lessons->where('done', true)->count();
            $quizScores = $lessons->where('type', 'quiz')->pluck('quiz_best')->filter(fn ($v) => $v !== null);
            $graded = $lessons->where('type', 'assignment')->filter(fn ($l) => $l['submission_score'] !== null);

            return [
                ...$course->toCard(),
                'lessons' => $lessons->values(),
                'total' => $total,
                'completed' => $completed,
                'percent' => $total ? (int) round($completed / $total * 100) : 0,
                'quiz_avg' => $quizScores->isNotEmpty() ? (int) round($quizScores->avg()) : null,
                'assignment_avg' => $graded->isNotEmpty() ? (int) round($graded->avg(fn ($l) => $l['submission_score'] / max(1, $l['max_score']) * 100)) : null,
                'next' => $lessons->firstWhere('done', false),
                'pending_assignments' => $lessons->where('type', 'assignment')->filter(fn ($l) => in_array($l['submission_status'], [null, 'revision'], true))->count(),
            ];
        });
    }
}
