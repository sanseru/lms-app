<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Http\Requests\LessonRequest;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\LessonReport;
use Modules\Learning\Models\QuizAttempt;
use Modules\Learning\Models\QuizQuestion;
use Modules\Learning\Support\LessonTypes;

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

        return to_route('learning.lessons.show', $lesson)->with('success', Lesson::types()[$lesson->type].' ditambahkan.');
    }

    public function show(Request $request, Lesson $lesson): Response
    {
        Gate::authorize('view', $lesson);
        $user = $request->user();
        $manage = $user->can('learning.manage');
        $learn = $user->can('learn', $lesson);
        $course = $lesson->course;

        $siblings = $course->lessons()->when(! $manage, fn ($q) => $q->where('is_published', true))->get(['id', 'title', 'type', 'position']);
        $index = $siblings->search(fn ($l) => $l->id === $lesson->id);

        $data = [
            'course' => $course->toCard(),
            'lesson' => $lesson->toItem() + [
                'body_html' => $lesson->bodyHtml(),
                'video_embed' => $lesson->videoEmbedUrl(),
                'video_url' => $lesson->video_url,
                'pass_score' => $lesson->type === 'quiz' ? $lesson->passScore() : null,
                'max_attempts' => $lesson->max_attempts,
                'max_score' => $lesson->type === 'assignment' ? $lesson->maxScore() : null,
            ],
            'outline' => $siblings->map(fn ($l) => ['id' => $l->id, 'title' => $l->title, 'type' => $l->type, 'icon' => Lesson::iconFor($l->type)]),
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
            'lesson' => $lesson->only('id', 'title', 'type', 'body', 'video_url', 'duration_minutes', 'is_published', 'pass_score', 'max_attempts', 'max_score', 'assessment') + [
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

        return to_route('learning.lessons.show', $lesson)->with('success', 'Perubahan disimpan.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->course;
        $lesson->delete();

        return to_route('learning.courses.show', $course)->with('success', 'Materi dihapus.');
    }

    /** Geser urutan materi naik/turun satu langkah. */
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

    /** Siswa menandai materi bacaan selesai. */
    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        Gate::authorize('learn', $lesson);
        abort_unless($lesson->type === 'material', 422);
        LessonProgress::markDone($lesson->id, $request->user()->id);

        return back()->with('success', 'Materi ditandai selesai.');
    }

    /** Jenis tambahan dari modul lain untuk pilihan di form. */
    private function extraTypes(LessonTypes $extra, Course $course): array
    {
        return collect($extra->all())->map(fn ($h) => ['label' => $h->label(), 'icon' => $h->icon(), 'url' => $h->createUrl($course)])->all();
    }

    private function lessonData(LessonRequest $request): array
    {
        $data = collect($request->validated())->except('questions')->all();
        $data['is_published'] = $request->boolean('is_published', true);

        // Kolom khusus jenis lain dikosongkan agar data tetap bersih.
        if ($data['type'] !== 'quiz') {
            $data['pass_score'] = $data['max_attempts'] = $data['assessment'] = null;
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
                'options' => collect($q['options'])->map(fn ($o) => ['text' => $o['text'], 'correct' => (bool) ($o['correct'] ?? false)])->values()->all(),
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
            // Kunci jawaban dibuka setelah lulus atau kesempatan habis.
            $reveal = $last && ($attempts->contains('passed', true) || $remaining === 0);

            $data['attempts'] = $attempts->map(fn (QuizAttempt $a) => ['id' => $a->id, 'score' => $a->score, 'passed' => $a->passed, 'at' => $a->created_at->translatedFormat('d M Y, H:i')]);
            $data['remaining'] = $remaining;
            $data['result'] = $last ? [
                'score' => $last->score,
                'passed' => $last->passed,
                'earned' => $last->earned_points,
                'total' => $last->total_points,
                'reveal' => $reveal,
                'items' => $questions->mapWithKeys(fn ($q) => [$q->id => [
                    'given' => $last->answers[$q->id]['given'] ?? [],
                    'correct' => $last->answers[$q->id]['correct'] ?? false,
                    'answer' => $reveal ? $q->correctIndexes() : null,
                    'explanation' => $reveal ? $q->explanation : null,
                ]]),
            ] : null;
        }

        if ($manage) {
            $data['answerKey'] = $questions->mapWithKeys(fn ($q) => [$q->id => ['answer' => $q->correctIndexes(), 'explanation' => $q->explanation]]);
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
