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
use Modules\Learning\Support\LessonTypes;

/**
 * Learning module: courses hold lessons, quizzes (auto-graded) and assignments
 * (graded by the teacher). A course is attached to a class via class_rooms.course_id.
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

    public function register(): void
    {
        $this->app->singleton(LessonTypes::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(AssignmentSubmission::class, AssignmentSubmissionPolicy::class);

        ClassRoom::resolveRelationUsing('course', fn (ClassRoom $class) => $class->belongsTo(Course::class, 'course_id'));
    }

    public function navigation(NavigationRegistry $nav): void
    {
        $nav->add(
            new NavItem('Course', 'learning.courses.index', 'book-open', 'Learning', 30, roles: ['admin', 'teacher'], active: 'learning.courses.*|learning.lessons.*'),
            new NavItem('Programs', 'learning.programs.index', 'layers', 'Learning', 29, roles: ['admin', 'teacher'], active: 'learning.programs.*'),
            new NavItem('Grading', 'learning.grading.index', 'file-pen', 'Learning', 31, roles: ['admin', 'teacher']),
            new NavItem('Archive', 'learning.archive', 'archive', 'Learning', 61, roles: ['student']),
            new NavItem('Course', 'learning.courses.index', 'book-open', 'Learning', 30, roles: ['student'], active: 'learning.courses.*|learning.lessons.*|learning.progress.*'),
            new NavItem('Learning progress', 'learning.progress.index', 'graduation-cap', 'Learning', 30, roles: ['guardian'], active: 'learning.*'),
        );
    }
}
