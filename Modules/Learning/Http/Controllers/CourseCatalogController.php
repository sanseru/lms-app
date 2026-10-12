<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Actions\RegisterCourseInterest;
use Modules\Learning\Actions\StudentProgress;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseInterest;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\Program;
use Modules\Learning\Support\LevelAccess;

/**
 * Course catalog for students: every published course, so a student can look
 * beyond their own class and tell the course they are interested in another one.
 */
class CourseCatalogController extends Controller
{
    public function index(Request $request, StudentProgress $progress, LevelAccess $access): Response
    {
        $student = $request->user();
        $mine = $progress->forStudent($student)->keyBy('id');
        $locks = self::locks($access, $student);
        $interested = CourseInterest::where('student_id', $student->id)->pluck('course_id')->flip();

        $courses = Course::query()
            ->where('status', 'published')
            ->with(['program', 'lessons' => fn ($q) => $q->where('is_published', true)->select('id', 'course_id', 'type', 'duration_minutes')])
            ->orderBy('level_order')->orderBy('title')
            ->get()
            ->map(fn (Course $course) => [
                ...$course->toCard(),
                'lessons_count' => $course->lessons->count(),
                'minutes' => (int) $course->lessons->sum('duration_minutes'),
                'mine' => $mine->has($course->id),
                'percent' => $mine->get($course->id)['percent'] ?? null,
                'interested' => $interested->has($course->id),
                'program' => $course->program?->only('id', 'name', 'position'),
                'unlock_after' => $locks[$course->id] ?? null,
            ])
            ->values();

        $current = $mine->get($request->integer('course')) ?? $mine->first();

        return Inertia::render('Learning::Courses/Explore', [
            'courses' => $courses,
            'programs' => Program::orderBy('position')->orderBy('name')->get()->map->toCard(),
            'current' => $current ? collect($current)->only('id', 'title', 'subject', 'color', 'description', 'percent', 'completed', 'total', 'next') : null,
        ]);
    }

    public function interest(Course $course, Request $request, RegisterCourseInterest $register): RedirectResponse
    {
        Gate::authorize('preview', $course);
        if ($course->isTakenBy($request->user())) {
            return back()->with('error', 'You already take this course.');
        }

        $register->handle($course, $request->user());

        return back()->with('success', 'Thanks! We will let your teacher and parents know.');
    }

    /** Read-only overview of a course the student does not take yet. */
    public static function preview(Course $course, Request $request): Response
    {
        $lessons = $course->lessons()->where('is_published', true)->get();

        return Inertia::render('Learning::Courses/Preview', [
            'course' => $course->toCard() + [
                'lessons_count' => $lessons->count(),
                'minutes' => (int) $lessons->sum('duration_minutes'),
            ],
            // Titles only: lesson content stays locked until the student joins a class.
            'lessons' => $lessons->map(fn (Lesson $l) => $l->only('id', 'title', 'type', 'duration_minutes') + ['type_label' => Lesson::types()[$l->type] ?? $l->type, 'assessment_label' => Lesson::ASSESSMENTS[$l->assessment] ?? null])->values(),
            'counts' => $lessons->countBy('type'),
            'interested' => CourseInterest::where('course_id', $course->id)->where('student_id', $request->user()->id)->exists(),
            'program' => $course->program?->only('id', 'name'),
            'unlockAfter' => self::locks(app(LevelAccess::class), $request->user())[$course->id] ?? null,
        ]);
    }

    /**
     * Locked levels in the student's own programs, with the level to finish first.
     *
     * @return array<int, string> course id => title of the level before it
     */
    private static function locks(LevelAccess $access, User $student): array
    {
        $locks = [];
        foreach ($access->programs($student) as $program) {
            foreach ($program['levels'] as $i => $level) {
                if (! $level['unlocked'] && $i > 0) {
                    $locks[$level['id']] = $program['levels'][$i - 1]['title'];
                }
            }
        }

        return $locks;
    }
}
