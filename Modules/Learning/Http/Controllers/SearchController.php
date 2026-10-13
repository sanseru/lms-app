<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Support\LevelAccess;

/**
 * Header search for students (client prototype: "Search for courses or lessons…").
 * Courses come from the published catalog; lessons only from levels the student can open,
 * so search never reveals the content of a locked level.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, LevelAccess $access): Response
    {
        $student = $request->user();
        $q = trim((string) $request->query('q'));
        $like = '%'.addcslashes($q, '%_\\').'%';
        $mine = $access->courseIds($student);

        $courses = $lessons = collect();

        if (mb_strlen($q) >= 2) {
            $courses = Course::query()->where('status', 'published')
                ->where(fn ($w) => $w->whereLike('title', $like)->orWhereLike('subject', $like)->orWhereLike('description', $like))
                ->with('program:id,name')
                ->orderBy('title')->limit(12)->get()
                ->map(fn (Course $c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'program' => $c->program?->name,
                    'description' => $c->description,
                    'color' => $c->color,
                    'mine' => in_array($c->id, $mine, true),
                ]);

            $lessons = Lesson::query()->whereIn('course_id', $mine)->where('is_published', true)
                ->where(fn ($w) => $w->whereLike('title', $like)->orWhereHas('unit', fn ($u) => $u->whereLike('title', $like)))
                ->with(['course:id,title', 'unit:id,title'])
                ->orderBy('course_id')->orderBy('position')->limit(20)->get()
                ->map(fn (Lesson $l) => [
                    'id' => $l->id,
                    'title' => $l->title,
                    'type_label' => Lesson::types()[$l->type] ?? $l->type,
                    'icon' => Lesson::iconFor($l->type),
                    'course' => $l->course?->title,
                    'unit' => $l->unit?->title,
                ]);
        }

        return Inertia::render('Learning::Search/Index', [
            'q' => $q,
            'courses' => $courses,
            'lessons' => $lessons,
        ]);
    }
}
