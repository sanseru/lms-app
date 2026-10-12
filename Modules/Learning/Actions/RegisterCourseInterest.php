<?php

namespace Modules\Learning\Actions;

use App\Models\User;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseInterest;
use Modules\Notifications\Support\Notify;

/**
 * A student taps "I'm interested" on a course from the catalog.
 * Saved once per student and course; admins and the student's guardians are told,
 * so the course can follow up (e.g. offer a class or a trial).
 */
class RegisterCourseInterest
{
    public function handle(Course $course, User $student): CourseInterest
    {
        $interest = CourseInterest::firstOrCreate(['course_id' => $course->id, 'student_id' => $student->id]);

        if (! $interest->wasRecentlyCreated) {
            return $interest;
        }

        $key = "course-interest:{$course->id}:{$student->id}";

        Notify::send(
            User::role('admin')->get(),
            "{$student->name} is interested in {$course->title}",
            'Seen in the course catalog. Consider offering a class or a free trial.',
            route('learning.courses.show', $course),
            'sparkles',
            $key,
        );

        Notify::send(
            $student->guardians()->get(),
            "{$student->name} is interested in {$course->title}",
            'Your child asked about this course in the app. Contact us if you would like to know more.',
            null,
            'sparkles',
            $key,
        );

        return $interest;
    }
}
