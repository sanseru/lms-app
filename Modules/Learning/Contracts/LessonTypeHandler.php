<?php

namespace Modules\Learning\Contracts;

use App\Models\User;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;

/**
 * An extra course content type from another module (e.g. coding exercises).
 * Modules register it via LessonTypes::register() in their service provider;
 * the Learning module then shows that type in the outline, progress and report.
 */
interface LessonTypeHandler
{
    public function key(): string;

    public function label(): string;

    /** Icon name in the Icon.vue component. */
    public function icon(): string;

    public function createUrl(Course $course): string;

    public function editUrl(Lesson $lesson): string;

    /** Inertia page that opens this content, e.g. "Coding::Exercises/Show". */
    public function showPage(): string;

    /** Extra props for the show page (besides course/outline data). */
    public function showData(Lesson $lesson, User $user, bool $manage, bool $learn): array;

    /**
     * The student's best score per item, for the report.
     *
     * @param  array<int>  $lessonIds
     * @return array<int, array{best: int|null, tries: int}>
     */
    public function scores(array $lessonIds, int $studentId): array;
}
