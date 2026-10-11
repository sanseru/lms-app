<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Classes\Models\ClassRoom;
use Modules\Coding\Models\CodeExercise;
use Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => test()->seed(RolesAndPermissionsSeeder::class))
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** Create a user with the given role. */
function userWithRole(string $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}

/*
|--------------------------------------------------------------------------
| Coding: ready-made class + course + exercise (shared by several test files)
|--------------------------------------------------------------------------
*/

function codingSetup(): array
{
    $teacher = userWithRole('teacher');
    $student = userWithRole('student');
    $course = Course::create(['title' => 'Python', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $class = ClassRoom::create([
        'code' => 'PY-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach($student->id);

    return [$teacher, $student, $course];
}

/** An input→output exercise that has already passed Test solution. */
function ioExercise(Course $course, array $overrides = []): Lesson
{
    $lesson = $course->lessons()->create(['title' => 'Square area', 'type' => 'code', 'position' => 1, 'is_published' => true]);
    $exercise = CodeExercise::create($overrides + [
        'lesson_id' => $lesson->id, 'language' => 'python', 'mode' => 'io', 'solution_code' => "s = int(input())\nprint(s * s)", 'starter_code' => 's = int(input())',
    ]);
    $exercise->tests()->create(['position' => 1, 'kind' => 'io', 'input' => '4', 'expected' => '16']);
    $exercise->tests()->create(['position' => 2, 'kind' => 'io', 'input' => '1.5', 'expected' => '2.25', 'is_hidden' => true, 'fail_message' => 'The side can be a decimal']);
    $exercise->update(['verified_hash' => $exercise->contentHash(), 'verified_at' => now()]);

    return $lesson;
}

function testIds(Lesson $lesson): array
{
    return CodeExercise::where('lesson_id', $lesson->id)->first()->tests()->pluck('id')->all();
}

/** Class + published course + enrolled student. */
function learningSetup(array $course = []): array
{
    $teacher = userWithRole('teacher');
    $student = userWithRole('student');
    $course = Course::create($course + ['title' => 'Python Start', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $class = ClassRoom::create([
        'code' => 'PYT-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach($student->id);

    return [$teacher, $student, $course, $class];
}

function makeQuiz(Course $course, array $attributes = []): Lesson
{
    $quiz = $course->lessons()->create($attributes + ['title' => 'Quiz 1', 'type' => 'quiz', 'position' => 1, 'pass_score' => 70]);
    $quiz->questions()->create(['position' => 1, 'prompt' => '1 + 1?', 'type' => 'single', 'points' => 1, 'options' => [['text' => '2', 'correct' => true], ['text' => '3', 'correct' => false]]]);
    $quiz->questions()->create(['position' => 2, 'prompt' => 'Even numbers?', 'type' => 'multiple', 'points' => 1, 'options' => [['text' => '2', 'correct' => true], ['text' => '3', 'correct' => false], ['text' => '4', 'correct' => true]]]);

    return $quiz->fresh('questions');
}
