<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseUnit;
use Modules\Learning\Models\Lesson;

/**
 * Numbered lessons on the course map. Each one groups course items
 * (theory, quiz, assignment, coding) that students see as its tasks.
 */
class CourseUnitController extends Controller
{
    public function store(Request $request, Course $course): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:120']]);
        $course->units()->create($data);

        return back()->with('success', 'Lesson added. Now put tasks in it with the "Lesson" menu on each item.');
    }

    public function update(Request $request, CourseUnit $unit): RedirectResponse
    {
        $unit->update($request->validate(['title' => ['required', 'string', 'max:120']]));

        return back()->with('success', 'Lesson renamed.');
    }

    public function destroy(CourseUnit $unit): RedirectResponse
    {
        // Items inside are kept; they just no longer belong to a lesson (the foreign key sets null).
        $unit->delete();

        return back()->with('success', 'Lesson removed. Its tasks are kept in the course.');
    }

    /** Put an item into a lesson of the same course, or take it out (unit_id null). */
    public function assign(Request $request, Lesson $lesson): RedirectResponse
    {
        $data = $request->validate([
            'unit_id' => ['nullable', 'integer', Rule::exists('course_units', 'id')->where('course_id', $lesson->course_id)],
        ], [], ['unit_id' => 'lesson']);

        $lesson->forceFill(['unit_id' => $data['unit_id'] ?? null])->save();

        return back()->with('success', $lesson->unit_id ? 'Task moved into the lesson.' : 'Task taken out of its lesson.');
    }
}
