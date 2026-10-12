<?php

namespace Modules\Learning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Learning\Actions\StudentProgress;
use Modules\Learning\Models\LessonProgress;

/**
 * Archive (client prototype: "Completed lessons"): every lesson the student finished,
 * per level, so they can come back to it any time.
 */
class ArchiveController extends Controller
{
    public function __invoke(Request $request, StudentProgress $progress): Response
    {
        $student = $request->user();
        $finishedAt = LessonProgress::where('student_id', $student->id)->pluck('completed_at', 'lesson_id');

        $levels = $progress->forStudent($student)
            ->map(function (array $course) use ($finishedAt) {
                $units = collect($course['units'])->keyBy('id');
                $groups = [];

                // Same circles as the map: a numbered lesson, or an item on its own.
                foreach ($course['lessons'] as $lesson) {
                    $key = $lesson['unit_id'] ? "u{$lesson['unit_id']}" : "l{$lesson['id']}";
                    $groups[$key] ??= [
                        'key' => $key,
                        'title' => $lesson['unit_id'] ? $units->get($lesson['unit_id'])['title'] ?? 'Lesson' : $lesson['title'],
                        'is_unit' => (bool) $lesson['unit_id'],
                        'tasks' => [],
                    ];
                    $groups[$key]['tasks'][] = $lesson;
                }

                $done = collect($groups)->values()
                    ->map(fn ($g, $i) => $g + ['number' => $i + 1])
                    ->filter(fn ($g) => collect($g['tasks'])->every(fn ($t) => $t['done']))
                    ->map(fn ($g) => [
                        'key' => $g['key'],
                        'number' => $g['number'],
                        'title' => $g['title'],
                        'is_unit' => $g['is_unit'],
                        'first_task_id' => $g['tasks'][0]['id'],
                        'tasks' => count($g['tasks']),
                        'finished_at' => collect($g['tasks'])->map(fn ($t) => $finishedAt[$t['id']] ?? null)->filter()->max()?->translatedFormat('j M Y'),
                    ])
                    ->values();

                return [
                    'id' => $course['id'],
                    'title' => $course['title'],
                    'color' => $course['color'],
                    'program' => $course['program'],
                    'percent' => $course['percent'],
                    'lessons' => $done,
                ];
            })
            ->filter(fn ($level) => $level['lessons']->isNotEmpty())
            ->values();

        return Inertia::render('Learning::Archive/Index', ['levels' => $levels]);
    }
}
