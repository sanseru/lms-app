<?php

namespace Modules\Learning\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Models\Course;

/**
 * Which published courses (levels) a student may learn.
 *
 * - Every course used by one of the student's classes.
 * - Inside a program, every level up to the highest one their class uses
 *   (a student placed straight into Python Start 2 can still revisit Start 1).
 * - Step by step after that: the next level opens once every published item
 *   of the level before it is done. No class change is needed.
 *
 * Results are kept on the current request, so policies and pages can ask often.
 */
class LevelAccess
{
    /** @return array<int, int> course ids */
    public function courseIds(User $student): array
    {
        return $this->resolve($student)['ids'];
    }

    public function allows(User $student, int $courseId): bool
    {
        return in_array($courseId, $this->courseIds($student), true);
    }

    /**
     * Every level of the programs the student is in, with its state, for the course map.
     *
     * @return Collection<int, array{program_id: int, levels: array<int, array{id: int, title: string, unlocked: bool, completed: bool, total: int, done: int}>}>
     */
    public function programs(User $student): Collection
    {
        return collect($this->resolve($student)['programs']);
    }

    private function resolve(User $student): array
    {
        $key = "learning.level-access.{$student->id}";
        $cache = request()->attributes;

        if (! $cache->has($key)) {
            $cache->set($key, $this->compute($student));
        }

        return $cache->get($key);
    }

    private function compute(User $student): array
    {
        $enrolled = Course::query()
            ->where('status', 'published')
            ->whereHas('classes.students', fn ($q) => $q->whereKey($student->id))
            ->get(['id', 'program_id']);

        $ids = $enrolled->pluck('id')->all();
        $programIds = $enrolled->pluck('program_id')->filter()->unique()->values();

        if ($programIds->isEmpty()) {
            return ['ids' => $ids, 'programs' => []];
        }

        $levels = Course::query()
            ->where('status', 'published')
            ->whereIn('program_id', $programIds)
            ->orderBy('level_order')->orderBy('id')
            ->get(['id', 'program_id', 'title', 'level_order']);

        // Published items per level, and how many of them this student finished.
        $totals = DB::table('lessons')->whereIn('course_id', $levels->pluck('id'))->where('is_published', true)
            ->groupBy('course_id')->selectRaw('course_id, count(*) as n')->pluck('n', 'course_id');
        $done = DB::table('lesson_progress')
            ->join('lessons', 'lessons.id', '=', 'lesson_progress.lesson_id')
            ->where('lesson_progress.student_id', $student->id)
            ->where('lessons.is_published', true)
            ->whereIn('lessons.course_id', $levels->pluck('id'))
            ->groupBy('lessons.course_id')->selectRaw('lessons.course_id as course_id, count(*) as n')->pluck('n', 'course_id');

        $enrolledIds = $ids;
        $programs = [];
        foreach ($levels->groupBy('program_id') as $programId => $list) {
            $list = $list->values();

            // Highest level a class of theirs uses in this program.
            $highest = -1;
            foreach ($list as $i => $course) {
                if (in_array($course->id, $enrolledIds, true)) {
                    $highest = $i;
                }
            }

            $rows = [];
            foreach ($list as $i => $course) {
                $total = (int) ($totals[$course->id] ?? 0);
                $finished = (int) ($done[$course->id] ?? 0);
                $previous = $rows[$i - 1] ?? null;

                // Up to the class's level everything is open; after it, each level needs the previous one done.
                $unlocked = $i <= $highest || ($previous && $previous['unlocked'] && $previous['completed']);

                if ($unlocked) {
                    $ids[] = $course->id;
                }

                $rows[] = [
                    'id' => $course->id, 'title' => $course->title, 'unlocked' => $unlocked,
                    'completed' => $total > 0 && $finished >= $total, 'total' => $total, 'done' => min($finished, $total),
                ];
            }

            $programs[] = ['program_id' => (int) $programId, 'levels' => $rows];
        }

        return ['ids' => array_values(array_unique($ids)), 'programs' => $programs];
    }
}
