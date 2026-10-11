<?php

namespace Modules\Learning\Providers;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavigationRegistry;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Policies\AssignmentSubmissionPolicy;
use Modules\Learning\Policies\CoursePolicy;
use Modules\Learning\Policies\LessonPolicy;

/**
 * Modul Belajar: kursus berisi materi, kuis (dinilai otomatis), dan tugas
 * (dinilai guru). Kursus dipasang ke kelas lewat kolom class_rooms.course_id.
 */
class LearningServiceProvider extends ModuleServiceProvider
{
    public function name(): string
    {
        return 'Learning';
    }

    public function permissions(): array
    {
        return [
            'learning.manage' => ['admin', 'teacher'],
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentSubmissionPolicy::class);

        ClassRoom::resolveRelationUsing('course', fn (ClassRoom $class) => $class->belongsTo(Course::class));
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Kursus & Materi', 'learning.courses.index', 'book-open', 'Belajar', 30, roles: ['admin', 'teacher'], active: 'learning.courses.*|learning.lessons.*'),
            new NavItem('Penilaian Tugas', 'learning.grading.index', 'file-pen', 'Belajar', 31, roles: ['admin', 'teacher']),
            new NavItem('Materi Saya', 'learning.courses.index', 'book-open', 'Belajar', 30, roles: ['student'], active: 'learning.courses.*|learning.lessons.*|learning.progress.*'),
            new NavItem('Progres Belajar', 'learning.progress.index', 'graduation-cap', 'Belajar', 30, roles: ['guardian'], active: 'learning.*'),
        );
    }
}
