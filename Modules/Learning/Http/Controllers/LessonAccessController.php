<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\ClassLessonOpen;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Support\LessonGate;

/**
 * The teacher's "Open / Locked" switches per class (client prototype: Teacher → Course).
 * Students normally open lessons one after another; a teacher can open a lesson early
 * for the whole class, also in the next level of the program.
 */
class LessonAccessController extends Controller
{
    public function show(Request $request, ClassRoom $class): Response
    {
        $this->authorizeClass($request, $class);
        $opened = ClassLessonOpen::where('class_room_id', $class->id)->pluck('node_key')->flip();

        return Inertia::render('Learning::LessonAccess/Show', [
            'classRoom' => $class->only('id', 'name', 'code'),
            'levels' => $this->levels($class)->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'level_order' => $course->level_order,
                'current' => $course->id === $class->course_id,
                'nodes' => $this->nodes($course)->map(fn ($n) => $n + ['opened' => $opened->has($n['key'])])->values(),
            ])->values(),
        ]);
    }

    /** Open or lock one lesson for the class. */
    public function update(Request $request, ClassRoom $class): RedirectResponse
    {
        $this->authorizeClass($request, $class);
        $data = $request->validate([
            'course_id' => ['required', 'integer', Rule::in($this->levels($class)->pluck('id'))],
            'node_key' => ['required', 'string', 'max:24'],
            'open' => ['required', 'boolean'],
        ]);
        abort_unless($this->nodes(Course::findOrFail($data['course_id']))->contains('key', $data['node_key']), 422);

        $this->set($class, (int) $data['course_id'], [$data['node_key']], $data['open'], $request->user()->id);

        return back();
    }

    /** "Open all" / "Lock all" for one level. */
    public function all(Request $request, ClassRoom $class): RedirectResponse
    {
        $this->authorizeClass($request, $class);
        $data = $request->validate([
            'course_id' => ['required', 'integer', Rule::in($this->levels($class)->pluck('id'))],
            'open' => ['required', 'boolean'],
        ]);
        $keys = $this->nodes(Course::findOrFail($data['course_id']))->pluck('key')->all();

        $this->set($class, (int) $data['course_id'], $keys, $data['open'], $request->user()->id);

        return back()->with('success', $data['open'] ? 'Every lesson in this level is open for the class.' : 'Lessons are back to step by step for the class.');
    }

    private function set(ClassRoom $class, int $courseId, array $keys, bool $open, int $userId): void
    {
        if (! $open) {
            ClassLessonOpen::where('class_room_id', $class->id)->whereIn('node_key', $keys)->delete();

            return;
        }

        foreach ($keys as $key) {
            ClassLessonOpen::firstOrCreate(['class_room_id' => $class->id, 'node_key' => $key], ['course_id' => $courseId, 'opened_by' => $userId]);
        }
    }

    /** The class's course, plus the other levels of its program. */
    private function levels(ClassRoom $class)
    {
        $course = $class->course_id ? Course::find($class->course_id) : null;

        if (! $course) {
            return collect();
        }

        return $course->program_id
            ? Course::where('program_id', $course->program_id)->orderBy('level_order')->orderBy('id')->get()
            : collect([$course]);
    }

    /** Map circles of a level with a label: "Lesson 2: Variables" or the item's own title. */
    private function nodes(Course $course)
    {
        $lessons = Lesson::where('course_id', $course->id)->where('is_published', true)->with('unit:id,title')->get(['id', 'title', 'type', 'unit_id', 'position']);
        $number = 0;

        return LessonGate::nodes($lessons)->map(function ($node) use ($lessons, &$number) {
            $first = $lessons->firstWhere('id', $node['lesson_ids'][0]);
            $number++;

            return [
                'key' => $node['key'],
                'number' => $number,
                'title' => $node['unit_id'] ? $first->unit?->title : $first->title,
                'tasks' => count($node['lesson_ids']),
            ];
        });
    }

    private function authorizeClass(Request $request, ClassRoom $class): void
    {
        $user = $request->user();
        abort_unless($user->hasRole('admin') || $class->teacher_id === $user->id, 403);
    }
}
