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

            return Inertia::render('Learning::Courses/Mine', [
                'courses' => $progress->forStudent($user),
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

        return to_route('learning.courses.show', $course)->with('success', 'Kursus dibuat. Tambahkan materi pertama.');
    }

    public function show(Request $request, Course $course, StudentProgress $progress): Response
    {
        Gate::authorize('view', $course);
        $user = $request->user();
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

        return to_route('learning.courses.show', $course)->with('success', 'Kursus diperbarui.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->classes()->update(['course_id' => null]);
        $course->delete();

        return to_route('learning.courses.index')->with('success', 'Kursus dihapus.');
    }

    /** Pasang kursus ini sebagai kurikulum sebuah kelas. */
    public function attachClass(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['class_room_id' => ['required', 'exists:class_rooms,id']], [], ['class_room_id' => 'kelas']);
        ClassRoom::whereKey($data['class_room_id'])->update(['course_id' => $course->id]);

        return back()->with('success', 'Kelas sekarang memakai kursus ini.');
    }

    public function detachClass(Course $course, ClassRoom $class): RedirectResponse
    {
        abort_unless($class->course_id === $course->id, 404);
        $class->forceFill(['course_id' => null])->saveQuietly();

        return back()->with('success', 'Kelas dilepas dari kursus.');
    }

    private function formOptions(): array
    {
        return ['levels' => ClassRoom::LEVELS, 'colors' => ClassRoom::COLORS, 'statuses' => Course::STATUSES];
    }
}
