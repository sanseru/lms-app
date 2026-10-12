<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Actions\StudentProgress;
use Modules\Learning\Http\Requests\CourseRequest;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;

class CourseController extends Controller
{
    public function index(Request $request, StudentProgress $progress): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->can('learning.manage')) {
            if ($user->hasRole('guardian')) {
                return to_route('learning.progress.index');
            }

            $courses = $progress->forStudent($user);

            return Inertia::render('Learning::Courses/Mine', [
                'courses' => $courses,
                'selected' => $request->integer('course') ?: $courses->first()['id'] ?? null,
                'nextSessions' => $this->nextSessions($courses->flatMap(fn ($c) => collect($c['classes'])->pluck('id'))->unique()->all()),
            ]);
        }

        $courses = Course::query()
            ->withCount(['lessons', 'classes'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->where(fn ($q) => $q->where('title', 'like', "%$s%")->orWhere('subject', 'like', "%$s%")))
            ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('status')
            ->orderBy('title')
            ->get()
            ->map->toCard();

        return Inertia::render('Learning::Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only('search', 'status'),
            'statuses' => Course::STATUSES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Learning::Courses/Form', $this->formOptions() + ['course' => null]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = Course::create($request->validated() + ['created_by' => $request->user()->id]);

        return to_route('learning.courses.show', $course)->with('success', 'Course created. Add the first lesson.');
    }

    public function show(Request $request, Course $course, StudentProgress $progress): Response
    {
        $user = $request->user();

        // A student browsing the catalog gets a read-only overview of courses they do not take.
        if ($user->cannot('view', $course) && $user->can('preview', $course)) {
            return CourseCatalogController::preview($course, $request);
        }

        Gate::authorize('view', $course);
        $manage = $user->can('learning.manage');

        if ($user->hasRole('student') && ! $manage) {
            $mine = $progress->forStudent($user)->firstWhere('id', $course->id);
        }

        $lessons = $course->lessons()
            ->when(! $manage, fn ($q) => $q->where('is_published', true))
            ->withCount('questions')
            ->withCount(['submissions as pending_count' => fn ($q) => $q->where('status', 'submitted')])
            ->get()
            ->map(fn ($l) => $l->toItem() + ['pending_count' => $manage ? $l->pending_count : null]);

        return Inertia::render('Learning::Courses/Show', [
            'course' => $course->toCard(),
            'lessons' => $mine['lessons'] ?? $lessons,
            'progress' => isset($mine) ? collect($mine)->only('total', 'completed', 'percent', 'quiz_avg', 'assignment_avg', 'next') : null,
            'classes' => $manage ? $course->classes()->with('teacher')->withCount('students')->get()->map->toCard() : [],
            'availableClasses' => $manage
                ? ClassRoom::query()->where(fn ($q) => $q->whereNull('course_id')->orWhere('course_id', '!=', $course->id))->whereIn('status', ['active', 'upcoming'])->orderBy('name')->get(['id', 'name', 'code', 'course_id'])
                : [],
            'interests' => $manage
                ? $course->interests()->with('student:id,name')->latest()->get()->map(fn ($i) => ['id' => $i->id, 'name' => $i->student->name, 'student_id' => $i->student_id, 'at' => $i->created_at->translatedFormat('j M Y')])
                : [],
            'pendingTotal' => $manage ? AssignmentSubmission::query()->gradableBy($user)->where('status', 'submitted')->whereHas('lesson', fn ($q) => $q->where('course_id', $course->id))->count() : 0,
            'can' => ['manage' => $manage],
        ]);
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Learning::Courses/Form', $this->formOptions() + ['course' => $course->only('id', 'title', 'subject', 'level', 'description', 'color', 'status')]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return to_route('learning.courses.show', $course)->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->classes()->update(['course_id' => null]);
        $course->delete();

        return to_route('learning.courses.index')->with('success', 'Course deleted.');
    }

    /** Attach this course as a class's curriculum. */
    public function attachClass(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['class_room_id' => ['required', 'exists:class_rooms,id']], [], ['class_room_id' => 'class']);
        ClassRoom::whereKey($data['class_room_id'])->update(['course_id' => $course->id]);

        return back()->with('success', 'The class now uses this course.');
    }

    public function detachClass(Course $course, ClassRoom $class): RedirectResponse
    {
        abort_unless($class->course_id === $course->id, 404);
        $class->forceFill(['course_id' => null])->saveQuietly();

        return back()->with('success', 'Class removed from the course.');
    }

    /**
     * Next scheduled meeting per class, when the Sessions module is enabled.
     *
     * @param  array<int, int>  $classIds
     * @return array<int, array{starts_at: string, zoom_url: ?string}>
     */
    private function nextSessions(array $classIds): array
    {
        if (! $classIds || ! (new ClassRoom)->isRelation('sessions')) {
            return [];
        }

        return ClassRoom::query()->whereKey($classIds)
            ->with(['sessions' => fn ($q) => $q->where('status', 'scheduled')->where('starts_at', '>=', now()->subHour())->orderBy('starts_at')])
            ->get()
            ->filter(fn ($class) => $class->sessions->isNotEmpty())
            ->mapWithKeys(fn ($class) => [$class->id => [
                'starts_at' => $class->sessions->first()->starts_at->toIso8601String(),
                'zoom_url' => $class->sessions->first()->zoom_url ?: $class->zoom_url,
            ]])
            ->all();
    }

    private function formOptions(): array
    {
        return ['levels' => ClassRoom::LEVELS, 'colors' => ClassRoom::COLORS, 'statuses' => Course::STATUSES];
    }
}
