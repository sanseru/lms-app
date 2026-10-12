<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Actions\QuizTimer;
use Modules\Learning\Http\Requests\LessonRequest;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\LessonReport;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Models\QuizQuestion;
use Modules\Learning\Support\LessonGate;
use Modules\Learning\Support\LessonTypes;
use Modules\Learning\Support\LevelAccess;

class LessonController extends Controller
{
    public function create(Request $request, Course $course, LessonTypes $extra): Response|RedirectResponse
    {
        if ($handler = $extra->get($request->query('type'))) {
            return redirect($handler->createUrl($course));
        }

        $type = in_array($request->query('type'), array_keys(Lesson::TYPES), true) ? $request->query('type') : 'material';

        return Inertia::render('Learning::Lessons/Form', [
            'course' => $course->only('id', 'title'),
            'lesson' => null,
            'defaultType' => $type,
            'types' => Lesson::TYPES,
            'extraTypes' => $this->extraTypes($extra, $course),
            'assessments' => Lesson::ASSESSMENTS,
            'questionTypes' => QuizQuestion::TYPES,
        ]);
    }

    public function store(LessonRequest $request, Course $course): RedirectResponse
    {
        $lesson = DB::transaction(function () use ($request, $course) {
            $lesson = $course->lessons()->create($this->lessonData($request) + ['position' => $course->nextPosition()]);
            $this->syncQuestions($lesson, $request->validated('questions') ?? []);

            return $lesson;
        });

        return to_route('learning.lessons.show', $lesson)->with('success', Lesson::types()[$lesson->type].' added.');
    }

    public function show(Request $request, Lesson $lesson, LevelAccess $levels, LessonGate $gate): Response|RedirectResponse
    {
        Gate::authorize('view', $lesson);
        $user = $request->user();

        // Lessons open one after another; a locked one sends the student back to the map.
        if ($user->hasRole('student') && $user->cannot('learning.manage') && $levels->allows($user, $lesson->course_id) && $gate->isLocked($user, $lesson)) {
            return to_route('learning.courses.index', ['course' => $lesson->course_id])
                ->with('error', 'Finish the lesson before this one first, or ask your teacher to open it for your class.');
        }

        $manage = $user->can('learning.manage');
        $learn = $user->can('learn', $lesson);
        $course = $lesson->course;

        $siblings = $course->lessons()->when(! $manage, fn ($q) => $q->where('is_published', true))->get(['id', 'title', 'type', 'position', 'unit_id']);
        $index = $siblings->search(fn ($l) => $l->id === $lesson->id);

        // Inside a numbered lesson the outline is its tasks; Previous/Next still walk the whole course.
        $unitOrder = $siblings->pluck('unit_id')->filter()->unique()->values();
        $outline = $lesson->unit_id ? $siblings->where('unit_id', $lesson->unit_id)->values() : $siblings;

        $data = [
            'course' => $course->toCard(),
            'lesson' => $lesson->toItem() + [
                'body_html' => $lesson->bodyHtml(),
                'video_embed' => $lesson->videoEmbedUrl(),
                'video_url' => $lesson->video_url,
                'pass_score' => $lesson->type === 'quiz' ? $lesson->passScore() : null,
                'max_attempts' => $lesson->max_attempts,
                'time_limit_minutes' => $lesson->type === 'quiz' ? $lesson->time_limit_minutes : null,
                'max_score' => $lesson->type === 'assignment' ? $lesson->maxScore() : null,
            ],
            'unit' => $lesson->unit_id ? [
                'id' => $lesson->unit_id,
                'title' => $lesson->loadMissing('unit')->unit?->title,
                'number' => $unitOrder->search($lesson->unit_id) + 1,
            ] : null,
            'outline' => $outline->map(fn ($l) => ['id' => $l->id, 'title' => $l->title, 'type' => $l->type, 'icon' => Lesson::iconFor($l->type)]),
            'done' => $learn ? LessonProgress::where('lesson_id', $lesson->id)->where('student_id', $user->id)->pluck('lesson_id') : [],
            'prev' => $index > 0 ? $siblings[$index - 1]->only('id', 'title') : null,
            'next' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1]->only('id', 'title') : null,
            'can' => ['manage' => $manage, 'learn' => $learn],
            'reports' => [
                'open' => $manage ? LessonReport::where('lesson_id', $lesson->id)->where('status', 'open')->count() : 0,
                'mine' => $learn && LessonReport::where('lesson_id', $lesson->id)->where('user_id', $user->id)->where('status', 'open')->exists(),
            ],
        ];

        if ($learn) {
            $data['done'] = LessonProgress::where('student_id', $user->id)->whereIn('lesson_id', $siblings->pluck('id'))->pluck('lesson_id');
        }

        if ($handler = $lesson->handler()) {
            return Inertia::render($handler->showPage(), $data + $handler->showData($lesson, $user, $manage, $learn));
        }

        if ($lesson->type === 'quiz') {
            $data += $this->quizData($lesson, $user, $manage, $learn);
        }

        if ($lesson->type === 'assignment') {
            $data['submission'] = $learn ? $lesson->submissions()->with('grader')->where('student_id', $user->id)->first()?->toItem() : null;
            $data['submissions'] = $manage
                ? $lesson->submissions()->gradableBy($user)->with('student', 'grader')->latest('submitted_at')->get()->map->toItem()
                : [];
        }

        return Inertia::render('Learning::Lessons/Show', $data);
    }

    public function edit(Lesson $lesson): Response|RedirectResponse
    {
        if ($handler = $lesson->handler()) {
            return redirect($handler->editUrl($lesson));
        }

        return Inertia::render('Learning::Lessons/Form', [
            'course' => $lesson->course->only('id', 'title'),
            'lesson' => $lesson->only('id', 'title', 'type', 'body', 'video_url', 'duration_minutes', 'is_published', 'pass_score', 'max_attempts', 'time_limit_minutes', 'max_score', 'assessment') + [
                'questions' => $lesson->questions->map->only('id', 'prompt', 'type', 'options', 'explanation', 'points', 'concept'),
            ],
            'defaultType' => $lesson->type,
            'types' => Lesson::TYPES,
            'extraTypes' => [],
            'assessments' => Lesson::ASSESSMENTS,
            'questionTypes' => QuizQuestion::TYPES,
        ]);
    }

    public function update(LessonRequest $request, Lesson $lesson): RedirectResponse
    {
        DB::transaction(function () use ($request, $lesson) {
            $lesson->update($this->lessonData($request));
            $this->syncQuestions($lesson, $request->validated('questions') ?? []);
        });

        return to_route('learning.lessons.show', $lesson)->with('success', 'Changes saved.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->course;
        $lesson->delete();

        return to_route('learning.courses.show', $course)->with('success', 'Lesson deleted.');
    }

    /** Move a lesson up or down one step. */
    public function move(Request $request, Lesson $lesson): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', 'in:up,down']])['direction'];
        $ordered = $lesson->course->lessons()->get()->values();
        $index = $ordered->search(fn ($l) => $l->id === $lesson->id);
        $swap = $direction === 'up' ? $index - 1 : $index + 1;

        if (isset($ordered[$swap])) {
            DB::transaction(function () use ($ordered, $index, $swap) {
                $items = $ordered->all();
                [$items[$index], $items[$swap]] = [$items[$swap], $items[$index]];
                foreach ($items as $position => $item) {
                    $item->update(['position' => $position + 1]);
                }
            });
        }

        return back();
    }

    /** Student marks a reading lesson as done. */
    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        abort_unless($lesson->type === 'material', 422);
        LessonProgress::markDone($lesson->id, $request->user()->id);

        return back()->with('success', 'Lesson marked as done.');
    }

    /** Extra types from other modules for the form's picker. */
    private function extraTypes(LessonTypes $extra, Course $course): array
    {
        return collect($extra->all())->map(fn ($h) => ['label' => $h->label(), 'icon' => $h->icon(), 'url' => $h->createUrl($course)])->all();
    }

    private function lessonData(LessonRequest $request): array
    {
        $data = collect($request->validated())->except('questions')->all();
        $data['is_published'] = $request->boolean('is_published', true);

        // Clear columns that belong to other types to keep the data clean.
        if ($data['type'] !== 'quiz') {
            $data['pass_score'] = $data['max_attempts'] = $data['time_limit_minutes'] = $data['assessment'] = null;
        }
        if ($data['type'] !== 'assignment') {
            $data['max_score'] = null;
        }

        return $data;
    }

    private function syncQuestions(Lesson $lesson, array $questions): void
    {
        if ($lesson->type !== 'quiz') {
            $lesson->questions()->delete();

            return;
        }

        $keep = [];
        foreach (array_values($questions) as $position => $q) {
            $attributes = [
                'position' => $position + 1,
                'prompt' => $q['prompt'],
                'type' => $q['type'],
                'points' => $q['points'],
                'explanation' => $q['explanation'] ?? null,
                'concept' => isset($q['concept']) ? mb_strtolower(trim($q['concept'])) ?: null : null,
                // Short-answer questions: every option is an accepted answer, so they are all correct.
                'options' => collect($q['options'])->map(fn ($o) => ['text' => trim($o['text']), 'correct' => $q['type'] === 'short' || (bool) ($o['correct'] ?? false)])->values()->all(),
            ];

            $existing = ! empty($q['id']) ? $lesson->questions()->find($q['id']) : null;
            $keep[] = $existing ? tap($existing)->update($attributes)->id : $lesson->questions()->create($attributes)->id;
        }

        $lesson->questions()->whereNotIn('id', $keep)->delete();
    }

    private function quizData(Lesson $lesson, $user, bool $manage, bool $learn): array
    {
        $questions = $lesson->questions()->get();
        $data = ['questions' => $questions->map->toPublic()];

        if ($learn) {
            $attempts = $lesson->attempts()->where('student_id', $user->id)->latest('id')->get();
            $last = $attempts->first();
            $remaining = $lesson->max_attempts ? max(0, $lesson->max_attempts - $attempts->count()) : null;
            // The answer key is revealed after passing or running out of attempts.
            $reveal = $last && ($attempts->contains('passed', true) || $remaining === 0);

            $data['attempts'] = $attempts->map(fn (QuizAttempt $a) => ['id' => $a->id, 'score' => $a->score, 'passed' => $a->passed, 'late' => $a->late, 'duration' => $a->duration_seconds, 'at' => $a->created_at->format('d M Y, H:i')]);
            $data['timer'] = $lesson->time_limit_minutes ? [
                'limit' => $lesson->time_limit_minutes,
                'remaining' => app(QuizTimer::class)->remaining($lesson, $user->id),
            ] : null;
            $data['remaining'] = $remaining;
            $data['result'] = $last ? [
                'score' => $last->score,
                'passed' => $last->passed,
                'late' => $last->late,
                'duration' => $last->duration_seconds,
                'earned' => $last->earned_points,
                'total' => $last->total_points,
                'reveal' => $reveal,
                'items' => $questions->mapWithKeys(fn ($q) => [$q->id => [
                    'given' => $last->answers[$q->id]['given'] ?? ($q->isShort() ? '' : []),
                    'correct' => $last->answers[$q->id]['correct'] ?? false,
                    'answer' => $reveal ? $q->answerKey() : null,
                    'explanation' => $reveal ? $q->explanation : null,
                ]]),
            ] : null;
        }

        if ($manage) {
            $data['answerKey'] = $questions->mapWithKeys(fn ($q) => [$q->id => ['answer' => $q->answerKey(), 'explanation' => $q->explanation]]);
            $data['attemptSummary'] = $lesson->attempts()
                ->with('student')
                ->get()
                ->groupBy('student_id')
                ->map(fn ($rows) => [
                    'student' => ['id' => $rows->first()->student->id, 'name' => $rows->first()->student->name, 'initials' => $rows->first()->student->initials()],
                    'best' => $rows->max('score'),
                    'tries' => $rows->count(),
                    'passed' => $rows->contains('passed', true),
                ])
                ->sortBy('student.name')
                ->values();
        }

        return $data;
    }
}
