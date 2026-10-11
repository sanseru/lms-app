<?php

namespace Modules\Coding\Support;

use App\Models\User;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeSubmission;
use Modules\Learning\Contracts\LessonTypeHandler;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;

class CodeLessonType implements LessonTypeHandler
{
    public function key(): string
    {
        return 'code';
    }

    public function label(): string
    {
        return 'Coding';
    }

    public function icon(): string
    {
        return 'code';
    }

    public function createUrl(Course $course): string
    {
        return route('coding.exercises.create', $course);
    }

    public function editUrl(Lesson $lesson): string
    {
        return route('coding.exercises.edit', $lesson);
    }

    public function showPage(): string
    {
        return 'Coding::Exercises/Show';
    }

    public function showData(Lesson $lesson, User $user, bool $manage, bool $learn): array
    {
        $exercise = CodeExercise::with('tests')->where('lesson_id', $lesson->id)->firstOrFail();
        $mine = $learn ? CodeSubmission::where('lesson_id', $lesson->id)->where('student_id', $user->id)->latest('id')->get() : collect();
        $passed = $mine->contains('status', 'passed');
        $failures = $mine->where('status', '!=', 'passed')->count();
        $solutionOpen = $manage || $passed || ($exercise->show_solution_after && $failures >= $exercise->show_solution_after);

        return [
            'exercise' => [
                'id' => $exercise->id,
                'language' => $exercise->language,
                'language_label' => CodeExercise::LANGUAGES[$exercise->language],
                'mode' => $exercise->mode,
                'mode_label' => CodeExercise::MODES[$exercise->mode],
                'starter_code' => $exercise->starter_code ?? '',
                'function_name' => $exercise->function_name,
                'hints' => $exercise->hints ?? [],
                'time_limit_ms' => $exercise->time_limit_ms,
                'case_sensitive' => $exercise->case_sensitive,
                'concept' => $exercise->concept,
                'level' => $exercise->level,
                'verified' => $exercise->isVerified(),
                'show_solution_after' => $exercise->show_solution_after,
                'parsons_lines' => $exercise->mode === 'parsons' ? $this->shuffled($exercise, $user) : null,
            ],
            'tests' => $manage ? $exercise->tests->map->toEditor() : $exercise->tests->map->toPublic(),
            'solution' => $solutionOpen ? $exercise->solution_code : null,
            'lastResult' => $mine->first() ? $mine->first()->toItem(true) + ['results' => $mine->first()->results] : null,
            'history' => $mine->take(10)->map->toItem()->values(),
            'passed' => $passed,
            'runtime' => ['driver' => config('coding.driver'), 'pyodide_url' => config('coding.pyodide_url')],
            'classroom' => $manage ? $this->classroom($lesson) : [],
        ];
    }

    public function scores(array $lessonIds, int $studentId): array
    {
        return CodeSubmission::query()
            ->where('student_id', $studentId)
            ->whereIn('lesson_id', $lessonIds)
            ->selectRaw('lesson_id, max(score) as best, count(*) as tries')
            ->groupBy('lesson_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->lesson_id => ['best' => (int) $row->best, 'tries' => (int) $row->tries]])
            ->all();
    }

    /** Untuk tutor saat kelas Zoom: kode terbaru dan nilai terbaik tiap siswa. */
    private function classroom(Lesson $lesson): array
    {
        return CodeSubmission::with('student')
            ->where('lesson_id', $lesson->id)
            ->latest('id')
            ->get()
            ->groupBy('student_id')
            ->map(fn ($rows) => [
                'student' => ['id' => $rows->first()->student->id, 'name' => $rows->first()->student->name, 'initials' => $rows->first()->student->initials()],
                'best' => $rows->max('score'),
                'tries' => $rows->count(),
                'passed' => $rows->contains('status', 'passed'),
                'latest' => $rows->first()->toItem(true),
            ])
            ->sortBy('student.name')
            ->values()
            ->all();
    }

    /** Urutan acak yang tetap untuk siswa yang sama (tidak berubah saat halaman dimuat ulang). */
    private function shuffled(CodeExercise $exercise, User $user): array
    {
        $lines = $exercise->parsonsLines();
        mt_srand(crc32($exercise->id.'-'.$user->id));
        $original = $lines;
        shuffle($lines);
        mt_srand();

        // Jangan sampai acakannya kebetulan sama dengan jawaban.
        if ($lines === $original && count($lines) > 1) {
            $lines[] = array_shift($lines);
        }

        return $lines;
    }
}
