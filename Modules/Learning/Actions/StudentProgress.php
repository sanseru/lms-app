<?php

namespace Modules\Learning\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Models\QuizQuestion;
use Modules\Learning\Support\LessonGate;
use Modules\Learning\Support\LessonTypes;
use Modules\Learning\Support\LevelAccess;

/**
 * A student's learning summary per course: progress, best quiz score,
 * assignment scores, the classes they take it in, and the next lesson.
 * Used by the student, parent, and teacher portals.
 */
class StudentProgress
{
    public function __construct(private LessonTypes $types, private LevelAccess $access, private LessonGate $gate) {}

    /**
     * Warm-up Mission vs Final Mission: the first attempt score of each,
     * normalized gain g = (post − pre) / (100 − pre), and a per-concept breakdown.
     */
    public function missions(Course $course, User $student): ?array
    {
        $tests = $course->lessons->whereNotNull('assessment')->where('type', 'quiz')->keyBy('assessment');

        if ($tests->isEmpty()) {
            return null;
        }

        $first = fn (?Lesson $lesson) => $lesson
            ? QuizAttempt::where('lesson_id', $lesson->id)->where('student_id', $student->id)->oldest('id')->first()
            : null;
        $pre = $first($tests->get('pre'));
        $post = $first($tests->get('post'));

        $concepts = QuizQuestion::whereIn('lesson_id', $tests->pluck('id'))->whereNotNull('concept')->get(['id', 'concept']);
        $byConcept = fn (?QuizAttempt $attempt) => $attempt
            ? $concepts->filter(fn ($q) => isset($attempt->answers[$q->id]))->groupBy('concept')
                ->map(fn ($qs) => (int) round($qs->filter(fn ($q) => $attempt->answers[$q->id]['correct'] ?? false)->count() / $qs->count() * 100))
            : collect();
        $preC = $byConcept($pre);
        $postC = $byConcept($post);

        return [
            'pre' => $pre?->score,
            'post' => $post?->score,
            'gain' => self::gain($pre?->score, $post?->score),
            'pre_lesson_id' => $tests->get('pre')?->id,
            'post_lesson_id' => $tests->get('post')?->id,
            'concepts' => $preC->keys()->merge($postC->keys())->unique()->sort()->values()
                ->map(fn ($c) => ['concept' => $c, 'pre' => $preC->get($c), 'post' => $postC->get($c)])->all(),
        ];
    }

    public static function gain(?int $pre, ?int $post): ?float
    {
        if ($pre === null || $post === null || $pre >= 100) {
            return null;
        }

        return round(($post - $pre) / (100 - $pre), 2);
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forStudent(User $student): Collection
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->whereIn('id', $this->access->courseIds($student))
            ->with([
                'program:id,name,color',
                'units',
                'lessons' => fn ($q) => $q->where('is_published', true),
                'classes' => fn ($q) => $q->whereHas('students', fn ($q) => $q->whereKey($student->id))->with('teacher')->orderBy('name'),
            ])
            ->orderBy('program_id')->orderBy('level_order')->orderBy('title')
            ->get();

        $lessonIds = $courses->flatMap->lessons->pluck('id');

        $done = LessonProgress::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)->pluck('completed_at', 'lesson_id');
        $bestQuiz = QuizAttempt::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)
            ->selectRaw('lesson_id, max(score) as best, count(*) as tries')->groupBy('lesson_id')->get()->keyBy('lesson_id');
        $submissions = AssignmentSubmission::query()->where('student_id', $student->id)->whereIn('lesson_id', $lessonIds)->get()->keyBy('lesson_id');

        // Scores from extra lesson types (e.g. coding exercises).
        $extra = [];
        foreach ($this->types->all() as $key => $handler) {
            $ids = $courses->flatMap->lessons->where('type', $key)->pluck('id')->all();
            $extra += $ids ? $handler->scores($ids, $student->id) : [];
        }

        return $courses->map(function (Course $course) use ($done, $bestQuiz, $submissions, $extra, $student) {
            $locked = array_flip($this->gate->lockedLessonIds($student, $course));
            $lessons = $course->lessons->map(fn ($lesson) => [
                'locked' => isset($locked[$lesson->id]),
                ...$lesson->toItem(),
                'done' => $done->has($lesson->id),
                'quiz_best' => $bestQuiz->get($lesson->id)?->best ?? $extra[$lesson->id]['best'] ?? null,
                'quiz_tries' => $bestQuiz->get($lesson->id)?->tries ?? $extra[$lesson->id]['tries'] ?? null,
                'submission_status' => $submissions->get($lesson->id)?->status,
                'submission_label' => AssignmentSubmission::STATUSES[$submissions->get($lesson->id)?->status] ?? null,
                'submission_score' => $submissions->get($lesson->id)?->score,
                'max_score' => $lesson->type === 'assignment' ? $lesson->maxScore() : null,
            ]);

            $total = $lessons->count();
            $completed = $lessons->where('done', true)->count();
            $quizScores = $lessons->where('type', 'quiz')->whereNull('assessment')->pluck('quiz_best')->filter(fn ($v) => $v !== null);
            $graded = $lessons->where('type', 'assignment')->filter(fn ($l) => $l['submission_score'] !== null);

            return [
                ...$course->toCard(),
                'classes' => $course->classes->map->toCard()->values(),
                'program' => $course->program?->only('id', 'name', 'color'),
                // Numbered lessons on the map; only those with published items, in the order of their first item.
                'units' => $course->units
                    ->filter(fn ($u) => $lessons->contains('unit_id', $u->id))
                    ->sortBy(fn ($u) => $lessons->firstWhere('unit_id', $u->id)['position'])
                    ->map(fn ($u) => ['id' => $u->id, 'title' => $u->title])->values(),
                'lessons' => $lessons->values(),
                'total' => $total,
                'completed' => $completed,
                'percent' => $total ? (int) round($completed / $total * 100) : 0,
                'quiz_avg' => $quizScores->isNotEmpty() ? (int) round($quizScores->avg()) : null,
                'assignment_avg' => $graded->isNotEmpty() ? (int) round($graded->avg(fn ($l) => $l['submission_score'] / max(1, $l['max_score']) * 100)) : null,
                'next' => $lessons->first(fn ($l) => ! $l['done'] && ! $l['locked']),
                'pending_assignments' => $lessons->where('type', 'assignment')->filter(fn ($l) => in_array($l['submission_status'], [null, 'revision'], true))->count(),
                'missions' => $this->missions($course, $student),
            ];
        });
    }
}
