<?php

namespace Modules\Forum\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Http\Requests\QuestionRequest;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Forum\Models\Vote;
use Modules\Learning\Models\Lesson;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    private const TABS = ['all', 'unanswered', 'answered', 'resolved', 'mine'];

    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'all';
        $classId = $request->integer('class') ?: null;
        $lesson = $request->integer('lesson') ? Lesson::find($request->integer('lesson')) : null;
        $search = trim((string) $request->query('q'));

        $base = Question::query()->visibleTo($user)
            ->when($classId, fn (Builder $q) => $q->where('class_room_id', $classId))
            ->when($lesson, fn (Builder $q) => $q->where('lesson_id', $lesson->id))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('title', 'like', "%{$search}%")->orWhere('body', 'like', "%{$search}%")));

        $filtered = fn () => $tab === 'mine'
            ? (clone $base)->where('author_id', $user->id)
            : (clone $base)->status($tab === 'all' ? null : $tab);

        $questions = $filtered()
            ->with(['classRoom:id,name,color,teacher_id', 'lesson:id,title', 'author:id,name'])
            ->orderByDesc('pinned')
            ->orderByRaw('coalesce(last_activity_at, created_at) desc')
            ->paginate(15)
            ->withQueryString();

        $voted = $this->votedIds($user, Question::class, $questions->pluck('id'));

        return Inertia::render('Forum::Questions/Index', [
            'questions' => $questions->through(fn (Question $q) => $q->toItem($user, $voted)),
            'filters' => ['tab' => $tab, 'class' => $classId, 'q' => $search],
            'lesson' => $lesson ? ['id' => $lesson->id, 'title' => $lesson->title] : null,
            'counts' => [
                'all' => (clone $base)->count(),
                'unanswered' => (clone $base)->status('unanswered')->count(),
                'answered' => (clone $base)->status('answered')->count(),
                'resolved' => (clone $base)->status('resolved')->count(),
                'mine' => (clone $base)->where('author_id', $user->id)->count(),
            ],
            'classes' => ClassRoom::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'color']),
            'canAsk' => $this->askableClasses($user)->isNotEmpty(),
            'isStaff' => $user->hasRole('admin') || $user->hasRole('teacher'),
        ]);
    }

    public function create(Request $request): Response
    {
        $classes = $this->askableClasses($request->user());
        abort_if($classes->isEmpty(), 403, 'You are not enrolled in any class yet.');

        $lesson = $request->integer('lesson') ? Lesson::find($request->integer('lesson')) : null;
        $class = $lesson ? $classes->firstWhere('course_id', $lesson->course_id) : $classes->firstWhere('id', $request->integer('class'));

        return Inertia::render('Forum::Questions/Form', [
            'question' => null,
            'classes' => $this->classOptions($classes, $request->user()),
            'defaults' => ['class_room_id' => $class?->id ?? ($classes->count() === 1 ? $classes->first()->id : null), 'lesson_id' => $class ? $lesson?->id : null],
            'visibilities' => Question::VISIBILITIES,
        ]);
    }

    public function store(QuestionRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image']);
        $question = Question::create($data + [
            'author_id' => $request->user()->id,
            'anonymous' => $request->boolean('anonymous'),
            'last_activity_at' => now(),
            'image_path' => $request->file('image')?->store("forum/{$data['class_room_id']}", 'local'),
        ]);

        return to_route('forum.questions.show', $question)->with('success', 'Question sent. Your teacher and classmates can help answer it.');
    }

    public function show(Request $request, Question $question): Response
    {
        Gate::authorize('view', $question);
        $user = $request->user();
        $question->load(['classRoom', 'lesson:id,title,course_id', 'author', 'answers.author', 'answers.question']);

        $answers = $question->answers
            ->sortBy(fn (Answer $a) => [$a->id === $question->accepted_answer_id ? 0 : 1, $a->by_staff ? 0 : 1, -$a->votes_count, $a->id])
            ->values();
        $votedAnswers = $this->votedIds($user, Answer::class, $answers->pluck('id'));

        return Inertia::render('Forum::Questions/Show', [
            'question' => $question->toItem($user, $this->votedIds($user, Question::class, collect([$question->id]))) + [
                'body_html' => $question->bodyHtml(),
                'code' => $question->code,
                'image_url' => $question->image_path ? route('forum.questions.image', $question) : null,
                'accepted_answer_id' => $question->accepted_answer_id,
                'teacher' => $question->classRoom->teacher?->name,
            ],
            'answers' => $answers->map(fn (Answer $a) => $a->toItem($user, $votedAnswers)),
            'can' => [
                'answer' => $user->can('answer', $question),
                'update' => $user->can('update', $question),
                'delete' => $user->can('delete', $question),
                'resolve' => $user->can('resolve', $question),
                'pin' => $user->can('pin', $question),
                'vote' => $user->can('vote', $question),
            ],
            'isStaff' => $question->isStaff($user),
        ]);
    }

    public function edit(Request $request, Question $question): Response
    {
        Gate::authorize('update', $question);
        $question->load('classRoom');

        return Inertia::render('Forum::Questions/Form', [
            'question' => [
                'id' => $question->id, 'class_room_id' => $question->class_room_id, 'lesson_id' => $question->lesson_id,
                'title' => $question->title, 'body' => $question->body, 'code' => $question->code,
                'visibility' => $question->visibility, 'anonymous' => $question->anonymous,
                'image_url' => $question->image_path ? route('forum.questions.image', $question) : null,
            ],
            'classes' => $this->classOptions(collect([$question->classRoom]), $request->user()),
            'defaults' => null,
            'visibilities' => Question::VISIBILITIES,
        ]);
    }

    public function update(QuestionRequest $request, Question $question): RedirectResponse
    {
        $data = $request->safe()->except(['image', 'remove_image', 'class_room_id']);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($question->image_path) {
                Storage::disk('local')->delete($question->image_path);
            }
            $data['image_path'] = $request->file('image')?->store("forum/{$question->class_room_id}", 'local');
        }

        $question->update($data + ['anonymous' => $request->boolean('anonymous')]);

        return to_route('forum.questions.show', $question)->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        Gate::authorize('delete', $question);

        if ($question->image_path) {
            Storage::disk('local')->delete($question->image_path);
        }
        Vote::where('votable_type', Answer::class)->whereIn('votable_id', $question->answers()->select('id'))->delete();
        $question->votes()->delete();
        $question->delete();

        return to_route('forum.questions.index')->with('success', 'Question deleted.');
    }

    public function image(Question $question): StreamedResponse
    {
        Gate::authorize('view', $question);
        abort_unless($question->image_path && Storage::disk('local')->exists($question->image_path), 404);

        return Storage::disk('local')->response($question->image_path);
    }

    public function pin(Question $question): RedirectResponse
    {
        Gate::authorize('pin', $question);
        $question->update(['pinned' => ! $question->pinned]);

        return back()->with('success', $question->pinned ? 'Question pinned to the top of the list.' : 'Question unpinned.');
    }

    /** Mark as resolved without picking an answer, or reopen it. */
    public function resolve(Question $question): RedirectResponse
    {
        Gate::authorize('resolve', $question);
        $reopen = $question->resolved_at !== null;
        $question->update(['resolved_at' => $reopen ? null : now(), 'accepted_answer_id' => $reopen ? null : $question->accepted_answer_id]);

        return back()->with('success', $reopen ? 'Question reopened.' : 'Question marked as answered.');
    }

    /** Small summary for the "Ask your teacher" card on the lesson page. */
    public function lessonSummary(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();
        $query = Question::query()->visibleTo($user)->where('lesson_id', $lesson->id);

        return response()->json([
            'count' => (clone $query)->count(),
            'can_ask' => $this->askableClasses($user)->contains('course_id', $lesson->course_id),
            'latest' => (clone $query)->with(['classRoom:id,name,color,teacher_id', 'lesson:id,title', 'author:id,name'])
                ->latest()->take(3)->get()->map(fn (Question $q) => $q->toItem($user)),
        ]);
    }

    /** @return Collection<int, ClassRoom> */
    private function askableClasses(User $user): Collection
    {
        if ($user->hasRole('admin')) {
            return ClassRoom::where('status', 'active')->orderBy('name')->get();
        }

        if ($user->hasRole('teacher')) {
            return $user->teachingClasses()->where('status', 'active')->orderBy('name')->get();
        }

        if ($user->hasRole('student')) {
            return $user->enrolledClasses()->wherePivot('status', 'active')->orderBy('name')->get();
        }

        return collect();
    }

    private function classOptions(Collection $classes, User $user): array
    {
        $lessons = Lesson::query()
            ->whereIn('course_id', $classes->pluck('course_id')->filter())
            ->when(! $user->hasAnyRole(['admin', 'teacher']), fn (Builder $q) => $q->where('is_published', true))
            ->orderBy('position')->get(['id', 'title', 'course_id'])
            ->groupBy('course_id');

        return $classes->map(fn (ClassRoom $c) => [
            'id' => $c->id, 'name' => $c->name, 'color' => $c->color,
            'lessons' => $c->course_id ? ($lessons[$c->course_id] ?? collect())->map(fn ($l) => ['id' => $l->id, 'title' => $l->title])->values() : [],
        ])->values()->all();
    }

    private function votedIds(User $user, string $type, Collection $ids): array
    {
        return Vote::where('user_id', $user->id)->where('votable_type', $type)->whereIn('votable_id', $ids)
            ->pluck('votable_id')->all();
    }
}
