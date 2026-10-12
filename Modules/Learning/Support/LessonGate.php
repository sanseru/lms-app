<?php

namespace Modules\Learning\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;

/**
 * Step-by-step lessons inside a level, as in the client prototype.
 *
 * The map shows one circle per numbered lesson (unit), or per item that is not in one.
 * A student may open a circle when every circle before it is done, or when a teacher
 * opened it early for one of the student's classes. In a level the student only reached
 * because a teacher opened lessons in it, just those opened lessons are available.
 */
class LessonGate
{
    public function __construct(private LevelAccess $levels) {}

    /**
     * Circles of a course in map order, each with the published item ids inside it.
     *
     * @return Collection<int, array{key: string, unit_id: ?int, lesson_ids: array<int, int>}>
     */
    public static function nodes(Collection $lessons): Collection
    {
        $nodes = [];
        foreach ($lessons->sortBy([['position', 'asc'], ['id', 'asc']]) as $lesson) {
            $key = $lesson->unit_id ? "u{$lesson->unit_id}" : "l{$lesson->id}";
            $nodes[$key] ??= ['key' => $key, 'unit_id' => $lesson->unit_id, 'lesson_ids' => []];
            $nodes[$key]['lesson_ids'][] = $lesson->id;
        }

        return collect(array_values($nodes));
    }

    /** Map keys a teacher opened for any class the student is in. */
    public function openKeys(User $student, int $courseId): array
    {
        return DB::table('class_lesson_opens')
            ->where('course_id', $courseId)
            ->whereIn('class_room_id', DB::table('class_students')->where('student_id', $student->id)->select('class_room_id'))
            ->pluck('node_key')->all();
    }

    /**
     * Item ids of a course the student cannot open yet.
     *
     * @return array<int, int>
     */
    public function lockedLessonIds(User $student, Course $course): array
    {
        $key = "learning.lesson-gate.{$student->id}.{$course->id}";
        $cache = request()->attributes;

        if (! $cache->has($key)) {
            $cache->set($key, $this->compute($student, $course));
        }

        return $cache->get($key);
    }

    public function isLocked(User $student, Lesson $lesson): bool
    {
        return in_array($lesson->id, $this->lockedLessonIds($student, $lesson->course), true);
    }

    private function compute(User $student, Course $course): array
    {
        $lessons = Lesson::query()->where('course_id', $course->id)->where('is_published', true)->get(['id', 'unit_id', 'position']);
        $done = DB::table('lesson_progress')->where('student_id', $student->id)->whereIn('lesson_id', $lessons->pluck('id'))->pluck('lesson_id')->flip();
        $opened = array_flip($this->openKeys($student, $course->id));
        $teacherOnly = $this->levels->openedByTeacherOnly($student, $course->id);

        $locked = [];
        $previousDone = true;
        foreach (self::nodes($lessons) as $node) {
            $open = isset($opened[$node['key']]) || (! $teacherOnly && $previousDone);
            if (! $open) {
                array_push($locked, ...$node['lesson_ids']);
            }
            $previousDone = $previousDone && collect($node['lesson_ids'])->every(fn ($id) => $done->has($id));
        }

        return $locked;
    }
}
